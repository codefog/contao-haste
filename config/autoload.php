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


/**
 * Register namespace if loaded from extension repository
 */
if (class_exists('NamespaceClassLoader')) {
    NamespaceClassLoader::add('Haste', 'system/modules/haste/library');
}
