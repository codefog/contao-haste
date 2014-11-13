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

namespace Haste\IO\Writer;

class ModelWriter extends AbstractWriter
{

    /**
     * Model class
     * @var string
     */
    protected $strModel;

    /**
     * Construct model writer
     * @param   string
     */
    public function __construct($strModel)
    {
        if (!class_exists($strModel) || !is_a($strModel, 'Model', true)) {
            throw new \InvalidArgumentException('Class "' . $strModel . '" is not a model');
        }

        $this->strModel = $strModel;
    }


    /**
     * Prepare the file
     * @param   Traversable
     * @return  bool
     */
    protected function prepare(\Traversable $objReader)
    {
        return true;
    }

    /**
     * Write row to database
     * @param   array
     * @return  bool
     */
    protected function writeRow(array $arrData)
    {
        if (!is_array($arrData)) {
            return false;
        }

        /** @type \Model $objModel */
        $objModel = new $this->strModel();
        $objModel->setRow($arrData);
        $objModel->save();

        return true;
    }

    /**
     * Nothing to finish here
     */
    protected function finish() {}
}
