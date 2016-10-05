<?php

/**
 * Haste utilities for Contao Open Source CMS
 *
 * Copyright (C) 2012-2016 Codefog & terminal42 gmbh
 *
 * @package    Haste
 * @link       http://github.com/codefog/contao-haste/
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Haste\Frontend;

use Contao\BackendTemplate;
use Contao\Module;

abstract class AbstractFrontendModule extends Module
{
    /**
     * @var bool
     */
    private $wildcard = true;

    /**
     * @inheritdoc
     */
    public function generate()
    {
        if ('BE' === TL_MODE && $this->showWildcard()) {
            return $this->generateWildcard();
        }

        return parent::generate();
    }

    /**
     * Generate wildcard template for backend output.
     *
     * @return string
     */
    protected function generateWildcard()
    {
        $objTemplate = new BackendTemplate('be_wildcard');

        $objTemplate->wildcard = '### ' . strtoupper($GLOBALS['TL_LANG']['FMD'][$this->type][0]) . ' ###';
        $objTemplate->title    = $this->headline;
        $objTemplate->id       = $this->id;
        $objTemplate->link     = $this->name;
        $objTemplate->href     = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

        return $objTemplate->parse();
    }

    /**
     * Returns whether the wildcard should be shown in the backend.
     *
     * @return bool
     */
    protected function showWildcard()
    {
        return $this->wildcard;
    }

    /**
     * Sets whether the wildcard should be shown in the backend.
     *
     * @param bool $wildcard
     */
    protected function setWildcard($wildcard)
    {
        $this->wildcard = $wildcard;
    }
}
