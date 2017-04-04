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
 * Class HasteUpdater
 *
 * Provide methods to handle Haste updates.
 */
class HasteUpdater
{

    /**
     * Run the controller
     */
    public function run()
    {
        $this->updateRelationsTables();
    }

    /**
     * Update the relations tables
     *
     * The relation tables should always be combined with two table names
     * that are sorted alphabetically, which was not the case before 4.5.5.
     */
    public function updateRelationsTables()
    {
        $this->loadDataContainers();

        foreach ((array) $GLOBALS['TL_DCA'] as $tableName => $dca) {
            foreach ((array) $dca['fields'] as $fieldName => $fieldData) {
                $relation = \Haste\Model\Relations::getRelation($tableName, $fieldName);

                if ($relation === false) {
                    continue;
                }

                $oldTable = $relation['reference_table'] . '_' . str_replace('tl_', '', $relation['related_table']);
                $newTable = $relation['table'];

                // Rename the table
                if (\Database::getInstance()->tableExists($oldTable, null, true) && $oldTable != $newTable) {
                    if (\Database::getInstance()->tableExists($newTable, null, true)) {
                        \System::log("Haste updater: Could not rename $oldTable to $newTable automatically because $newTable already exists! You have to migrate the data manually!", __METHOD__, TL_ERROR);
                    } else {
                        \Database::getInstance()->query("RENAME TABLE $oldTable TO $newTable");
                        \System::log("Haste updater: renamed relations table $oldTable to $newTable", __METHOD__, TL_GENERAL);
                    }
                }
            }
        }
    }

    /**
     * Load all data containers
     */
    protected function loadDataContainers()
    {
        foreach (\ModuleLoader::getActive() as $module) {
            $dir = 'system/modules/' . $module . '/dca';

            if (!is_dir(TL_ROOT . '/' . $dir)) {
                continue;
            }

            foreach (scan(TL_ROOT . '/' . $dir) as $file) {
                if (substr($file, -4) != '.php') {
                    continue;
                }

                \Controller::loadDataContainer(substr($file, 0, -4));
            }
        }
    }
}

/**
 * Instantiate controller
 */
$updater = new HasteUpdater();
$updater->run();
