<?php

namespace Haste\EventListener;

use Contao\Environment;
use Contao\Input;
use Haste\Util\AjaxReloadHelper;

class PageListener
{
    /**
     * On generate the page
     */
    public function onGenerate()
    {
        if (!AjaxReloadHelper::hasListeners()) {
            return;
        }

        $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/haste/assets/ajax-reload.min.js';

        if (Environment::get('isAjaxRequest') && ($event = Input::get('haste_ajax_reload'))) {
            AjaxReloadHelper::dispatch($event)->send();
        }
    }
}
