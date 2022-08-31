<?php

declare(strict_types=1);

namespace Codefog\HasteBundle;

use Contao\StringUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\ResetInterface;

class AjaxReloadManager implements ResetInterface
{
    public const TYPE_CONTENT = 'ce';
    public const TYPE_MODULE = 'fmd';

    private array $buffers = [];
    private array $listeners = [];

    /**
     * Subscribe the listener.
     */
    public function subscribe(string $type, int $id, array $events): void
    {
        $uniqid = $this->getUniqid($type, $id);

        foreach ($events as $event) {
            if (!isset($this->listeners[$event]) || !\in_array($uniqid, $this->listeners[$event], true)) {
                $this->listeners[$event][] = $uniqid;
            }
        }
    }

    /**
     * Store the buffer if applicable.
     */
    public function storeBuffer(string $type, int $id, string $event, string $buffer): void
    {
        $uniqid = $this->getUniqid($type, $id);

        if (isset($this->listeners[$event]) && !isset($this->buffers[$uniqid]) && \in_array($uniqid, $this->listeners[$event], true)) {
            $this->buffers[$uniqid] = $buffer;
        }
    }

    /**
     * Update the HTML buffer.
     */
    public function updateBuffer(string $type, int $id, string $buffer, bool $isAjax = false): string
    {
        $events = [];
        $uniqid = $this->getUniqid($type, $id);

        foreach ($this->listeners as $event => $entries) {
            if (\in_array($uniqid, $entries, true)) {
                $events[] = $event;
            }
        }

        if (\count($events) > 0) {
            $buffer = static::addDataAttributes($buffer, $uniqid, $events, $isAjax);
        }

        return $buffer;
    }

    /**
     * Return true if there are listeners.
     */
    public function hasListeners(): bool
    {
        return \count($this->listeners) > 0;
    }

    /**
     * Get the response.
     */
    public function getResponse(): ?Response
    {
        if (0 === \count($this->buffers)) {
            return null;
        }

        $response = new JsonResponse($this->buffers);
        $response->headers->set('Vary', 'Accept');

        return $response;
    }

    /**
     * Return true if the listener is registered.
     */
    public function isRegistered(string $type, int $id): bool
    {
        $uniqid = $this->getUniqid($type, $id);

        foreach ($this->listeners as $entries) {
            if (\in_array($uniqid, $entries, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the events.
     */
    public function getEvents(string $type, int $id): array
    {
        $events = [];
        $uniqid = $this->getUniqid($type, $id);

        foreach ($this->listeners as $event => $entries) {
            if (\in_array($uniqid, $entries, true)) {
                $events[] = $event;
            }
        }

        return array_unique($events);
    }

    /**
     * Reset the service state on kernel shutdown.
     */
    public function reset(): void
    {
        $this->buffers = [];
        $this->listeners = [];
    }

    /**
     * Get the unique ID.
     */
    private function getUniqid(string $type, int $id): string
    {
        if (!\in_array($type, [self::TYPE_CONTENT, self::TYPE_MODULE], true)) {
            throw new \InvalidArgumentException(sprintf('The type "%s" is not supported', $type));
        }

        return $type.$id;
    }

    /**
     * Add the HTML "data-" attributes to the buffer.
     */
    private function addDataAttributes(string $buffer, string $uniqid, array $events, bool $isAjax = false): string
    {
        // Merge the data attributes if already present
        preg_replace_callback(
            '/\s?data-haste-ajax-id="[^"]*" data-haste-ajax-listeners="([^"]*)"/',
            static function ($matches) use (&$events) {
                $events = array_merge($events, StringUtil::trimsplit(' ', $matches[1]));

                return '';
            },
            $buffer
        );

        // Remove the HTML comments on AJAX request, so they don't appear doubled in the DOM
        if ($isAjax) {
            $buffer = preg_replace('/<!--(.*)-->/', '', $buffer);
        }

        // Add the necessary attributes to the first wrapping element
        $buffer = preg_replace(
            '/<([^>!]+)>/',
            sprintf('<$1 data-haste-ajax-id="%s" data-haste-ajax-listeners="%s">', $uniqid, implode(' ', array_unique($events))),
            $buffer,
            1
        );

        // Trim the buffer to avoid JS break
        return trim($buffer);
    }
}
