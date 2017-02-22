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
     * @param ContentModel $element
     * @param string       $buffer
     *
     * @return string
     */
    public function onGetContentElement(ContentModel $element, $buffer)
    {
        $buffer = ReloadHelper::updateContentElementBuffer($element, $buffer);

        if (($events = $this->getEvents()) !== null) {
            ReloadHelper::storeContentElementResponse($events, $element, $buffer);
        }

        return $buffer;
    }

    /**
     * On get the frontend module
     *
     * @param ModuleModel $module
     * @param string      $buffer
     *
     * @return string
     */
    public function onGetFrontendModule(ModuleModel $module, $buffer)
    {
        $buffer = ReloadHelper::updateFrontendModuleBuffer($module, $buffer);

        if (($events = $this->getEvents()) !== null) {
            ReloadHelper::storeFrontendModuleResponse($events, $module, $buffer);
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
