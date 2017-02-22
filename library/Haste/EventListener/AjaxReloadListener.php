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
        return ReloadHelper::updateContentElementBuffer($element, $buffer);
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
        return ReloadHelper::updateFrontendModuleBuffer($module, $buffer);
    }

    /**
     * On generate the page
     */
    public function onGeneratePage()
    {
        if (!ReloadHelper::hasListeners()) {
            return;
        }

        $GLOBALS['TL_JAVASCRIPT'][] = Debug::uncompressedFile('system/modules/haste/assets/ajax-reload.min.js');

        if (Environment::get('isAjaxRequest') && ($events = Input::get('haste_ajax_reload'))) {
            ReloadHelper::dispatch(trimsplit(',', $events))->send();
        }
    }
}
