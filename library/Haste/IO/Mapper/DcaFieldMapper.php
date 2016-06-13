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

namespace Haste\IO\Mapper;

use Haste\Haste;

class DcaFieldMapper extends ArrayMapper
{

    /**
     * Construct mapper from DCA fields
     *
     * @param string $strTable
     *
     * @throws \Exception
     */
    public function __construct($strTable)
    {
        if (!is_array($GLOBALS['TL_DCA'][$strTable])) {
            \Controller::loadDataContainer($strTable);
        }

        if (!is_array($GLOBALS['TL_DCA'][$strTable]['fields'])) {
            throw new \Exception('DCA for table "' . $strTable . '" does not have any fields.');
        }

        $this->arrMap = array();

        // Build the mapper
        foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $k => $v) {
            if (!is_array($v['eval']) || !array_key_exists('haste_csv_position', $v['eval'])) {
                continue;
            }

            $this->arrMap[$k] = $v['eval']['haste_csv_position'];
        }
    }
}
