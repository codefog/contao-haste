<?php

namespace Haste\EventListener;

use Contao\ModuleModel;
use Haste\Util\AjaxReloadHelper;

class FrontendModuleListener
{
    /**
     * On get the frontend module
     *
     * @param ModuleModel $module
     * @param string      $buffer
     *
     * @return string
     */
    public function onGet(ModuleModel $module, $buffer)
    {
        return AjaxReloadHelper::updateFrontendModuleBuffer($module, $buffer);
    }
}
