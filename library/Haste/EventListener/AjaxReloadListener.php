<?php

namespace Haste\EventListener;

use Contao\ContentModel;
use Contao\Environment;
use Contao\Input;
use Contao\ModuleModel;
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
            && ReloadHelper::isRegistered(ReloadHelper::TYPE_FRONTEND_MODULE, $model->module)
        ) {
            ReloadHelper::subscribe(
                ReloadHelper::TYPE_CONTENT_ELEMENT,
                $model->id,
                ReloadHelper::getEvents(ReloadHelper::TYPE_FRONTEND_MODULE, $model->module)
            );
        }

        //
        if ($model->type === 'alias') {
            // @todo
        }

        $buffer = ReloadHelper::updateBuffer(ReloadHelper::TYPE_CONTENT_ELEMENT, $model->id, $buffer);

        if (($events = $this->getEvents()) !== null) {
            ReloadHelper::storeResponse(ReloadHelper::TYPE_CONTENT_ELEMENT, $model->id, $events, $buffer);
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
        $buffer = ReloadHelper::updateBuffer(ReloadHelper::TYPE_FRONTEND_MODULE, $model->id, $buffer);

        if (($events = $this->getEvents()) !== null) {
            ReloadHelper::storeResponse(ReloadHelper::TYPE_FRONTEND_MODULE, $model->id, $events, $buffer);
        }

        return $buffer;
    }

    /**
     * On generate the page
     */
    public function onGeneratePage()
    {
        if (($response = ReloadHelper::getResponse()) !== null) {
            $response->send();
        }

        if (ReloadHelper::hasListeners()) {
            $GLOBALS['TL_JAVASCRIPT'][] = Debug::uncompressedFile('system/modules/haste/assets/ajax-reload.min.js');
        }
    }

    /**
     * Get the events
     *
     * @return array|null
     */
    private function getEvents()
    {
        if (!Environment::get('isAjaxRequest') || !($events = Input::get('haste_ajax_reload'))) {
            return null;
        }

        return trimsplit(',', $events);
    }
}
