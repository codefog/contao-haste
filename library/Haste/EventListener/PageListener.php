<?php

namespace Haste\EventListener;

use Contao\Environment;
use Contao\Input;
use Haste\Ajax\ReloadHelper;
use Haste\Util\Debug;

class PageListener
{
    /**
     * On generate the page
     */
    public function onGenerate()
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
