<?php

/**
 * haste extension for Contao Open Source CMS
 * 
 * Copyright (C) 2011-2013 Codefog
 * 
 * @package haste
 * @link    http://codefog.pl
 * @author  Kamil Kuzminski <kamil.kuzminski@codefog.pl>
 * @author  Yanick Witschi <yanick.witschi@terminal42.ch>
 * @author  Andreas Schempp <andreas.schempp@terminal42.ch>
 * @license LGPL
 */


/**
 * Register classes
 */
ClassLoader::addClasses(array
(
	'Contao\HasteForm' => 'system/modules/haste/HasteForm.php'
));
