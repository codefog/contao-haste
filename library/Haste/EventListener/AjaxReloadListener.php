<?php

namespace Haste\EventListener;

use Contao\ContentModel;
use Contao\Environment;
use Contao\ModuleModel;
use Contao\Template;
use Haste\Ajax\ReloadHelper;
use Haste\Util\Debug;

class AjaxReloadListener
{
    /**
     * On get the content element
     *
     * @param ContentModel $model
     * @param string       $buffer
     *
     * @return string
     */
    public function onGetContentElement(ContentModel $model, $buffer)
    {
        // Subscribe the content element if the included frontend module has subscribed itself
        if ($model->type === 'module'
            && ReloadHelper::isRegistered(ReloadHelper::getUniqid(ReloadHelper::TYPE_MODULE, $model->module))
        ) {
            ReloadHelper::subscribe(
                ReloadHelper::getUniqid(ReloadHelper::TYPE_CONTENT, $model->id),
                ReloadHelper::getEvents(ReloadHelper::getUniqid(ReloadHelper::TYPE_MODULE, $model->module))
            );
        }

        // Subscribe the content element if the included content element has subscribed itself
        if ($model->type === 'alias'
            && ReloadHelper::isRegistered(ReloadHelper::getUniqid(ReloadHelper::TYPE_CONTENT, $model->cteAlias))
        ) {
            ReloadHelper::subscribe(
                ReloadHelper::getUniqid(ReloadHelper::TYPE_CONTENT, $model->id),
                ReloadHelper::getEvents(ReloadHelper::getUniqid(ReloadHelper::TYPE_CONTENT, $model->cteAlias))
            );
        }

        $event  = $this->getEvent();
        $isAjax = $event !== null;
        $buffer = ReloadHelper::updateBuffer(
            ReloadHelper::getUniqid(ReloadHelper::TYPE_CONTENT, $model->id),
            $buffer,
            $isAjax
        );

        if ($isAjax) {
            ReloadHelper::storeResponse(
                ReloadHelper::getUniqid(ReloadHelper::TYPE_CONTENT, $model->id),
                $event,
                $buffer
            );
        }

        return $buffer;
    }

    /**
     * On get the frontend module
     *
     * @param ModuleModel $model
     * @param string      $buffer
     *
     * @return string
     */
    public function onGetFrontendModule(ModuleModel $model, $buffer)
    {
        $event  = $this->getEvent();
        $isAjax = $event !== null;
        $buffer = ReloadHelper::updateBuffer(
            ReloadHelper::getUniqid(ReloadHelper::TYPE_MODULE, $model->id),
            $buffer,
            $isAjax
        );

        if ($isAjax) {
            ReloadHelper::storeResponse(
                ReloadHelper::getUniqid(ReloadHelper::TYPE_MODULE, $model->id),
                $event,
                $buffer
            );
        }

        return $buffer;
    }

    /**
     * On modify the frontend page. Handle the request for entries included via insert tags,
     * e.g. via page layout or content elements
     *
     * @param string $buffer
     * @param string $template
     *
     * @return string
     */
    public function onModifyFrontendPage($buffer, $template)
    {
        if (stripos($template, 'fe_') === 0) {
            if (($response = ReloadHelper::getResponse()) !== null) {
                $response->send();
            }

            if (ReloadHelper::hasListeners()) {
                $buffer = str_replace(
                    '</body>',
                    sprintf(
                        '<script src="%s"></script></body>',
                        Debug::uncompressedFile('system/modules/haste/assets/ajax-reload.min.js')
                    ),
                    $buffer
                );
            }
        }

        return $buffer;
    }

    /**
     * Get the event
     *
     * @return string|null
     */
    private function getEvent()
    {
        if (!Environment::get('isAjaxRequest') || !($event = $_SERVER['HTTP_HASTE_AJAX_RELOAD'])) {
            return null;
        }

        return $event;
    }
}
