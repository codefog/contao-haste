<?php

/**
 * Haste utilities for Contao Open Source CMS
 *
 * Copyright (C) 2012-2013 Codefog & terminal42 gmbh
 *
 * @package    Haste
 * @link       http://github.com/codefog/contao-haste/
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Haste\EventListener;

class TemplateHelperListener
{
    /**
     * @param \Template $template
     */
    public function onParseTemplate(\Template $template)
    {
        $template->hasteTemplateHelpers = new TemplateHelpers();
    }
}
