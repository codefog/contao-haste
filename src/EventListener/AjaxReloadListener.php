<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\EventListener;

use Codefog\HasteBundle\AjaxReloadManager;
use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\ModuleModel;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class AjaxReloadListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly AjaxReloadManager $manager,
        private readonly Packages $packages,
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    #[AsHook('getContentElement')]
    public function onGetContentElement(ContentModel $model, string $buffer): string
    {
        // Subscribe the content element if the included frontend module has subscribed itself
        if ('module' === $model->type && $this->manager->isRegistered(AjaxReloadManager::TYPE_MODULE, (int) $model->module)) {
            $this->manager->subscribe(AjaxReloadManager::TYPE_CONTENT, (int) $model->id, $this->manager->getEvents(AjaxReloadManager::TYPE_MODULE, (int) $model->module));
        }

        // Subscribe the content element if the included content element has subscribed itself
        if ('alias' === $model->type && $this->manager->isRegistered(AjaxReloadManager::TYPE_CONTENT, (int) $model->cteAlias)) {
            $this->manager->subscribe(AjaxReloadManager::TYPE_CONTENT, (int) $model->id, $this->manager->getEvents(AjaxReloadManager::TYPE_CONTENT, (int) $model->cteAlias));
        }

        $event = $this->getEventFromCurrentRequest();
        $isAjax = null !== $event;
        $buffer = $this->manager->updateBuffer(AjaxReloadManager::TYPE_CONTENT, (int) $model->id, $buffer, $isAjax);

        if ($isAjax) {
            $this->manager->storeBuffer(AjaxReloadManager::TYPE_CONTENT, (int) $model->id, $event, $buffer);
        }

        return $buffer;
    }

    #[AsHook('getFrontendModule')]
    public function onGetFrontendModule(ModuleModel $model, string $buffer): string
    {
        $event = $this->getEventFromCurrentRequest();
        $isAjax = null !== $event;
        $buffer = $this->manager->updateBuffer(AjaxReloadManager::TYPE_MODULE, (int) $model->id, $buffer, $isAjax);

        if ($isAjax) {
            $this->manager->storeBuffer(AjaxReloadManager::TYPE_MODULE, (int) $model->id, $event, $buffer);
        }

        return $buffer;
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isFrontendMainRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        // Only handle GET requests
        if (!$request->isMethod(Request::METHOD_GET)) {
            return;
        }

        $response = $event->getResponse();

        // Only handle text/html responses
        if (!str_starts_with($response->headers->get('Content-Type', ''), 'text/html')) {
            return;
        }

        // Modify the regular response
        if ($this->manager->hasListeners() && !$request->headers->has('Haste-Ajax-Reload')) {
            // Vary on the header, so we don't have the same URL cached
            $response->headers->set('Vary', 'Haste-Ajax-Reload', false);

            // Add the necessary <script> tags
            $response->setContent(str_replace(
                '</body>',
                sprintf('<script src="%s"></script></body>', $this->packages->getUrl('ajax-reload.js', 'codefog_haste')),
                $response->getContent(),
            ));

            return;
        }

        // Return the requested buffers in an ajax response
        if ($request->headers->has('Haste-Ajax-Reload') && ($buffers = $this->manager->getBuffers()) !== []) {
            $response->setContent(json_encode($buffers));
            $response->headers->set('Content-Type', 'application/json');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ResponseEvent::class => 'onResponse',
        ];
    }

    /**
     * Get the event from the current request.
     */
    private function getEventFromCurrentRequest(): string|null
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->isXmlHttpRequest() || !$request->server->has('HTTP_HASTE_AJAX_RELOAD')) {
            return null;
        }

        return $request->server->get('HTTP_HASTE_AJAX_RELOAD');
    }
}
