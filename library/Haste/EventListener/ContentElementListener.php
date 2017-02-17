<?php

namespace Haste\EventListener;

use Contao\ContentModel;
use Haste\Util\AjaxReloadHelper;

class ContentElementListener
{
    /**
     * On get the content element
     *
     * @param ContentModel $element
     * @param string       $buffer
     *
     * @return string
     */
    public function onGet(ContentModel $element, $buffer)
    {
        return AjaxReloadHelper::updateContentElementBuffer($element, $buffer);
    }
}
