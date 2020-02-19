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
if (!class_exists(HasteUpdater::class)) {
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
                    if ($oldTable !== $newTable && \Database::getInstance()->tableExists($oldTable, null, true)) {
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
            $tables = [];

            if (!method_exists(\System::class, 'getContainer')) {
                foreach (\ModuleLoader::getActive() as $module) {
                    $dir = TL_ROOT.'/system/modules/'.$module.'/dca';

                    if (!is_dir($dir)) {
                        continue;
                    }

                    foreach (scan($dir) as $file) {
                        if ('.php' !== substr($file, -4)) {
                            continue;
                        }

                        $tables[] = substr($file, 0, -4);
                    }
                }
            } else {
                $files = System::getContainer()
                    ->get('contao.resource_finder')
                    ->findIn('dca')
                    ->depth(0)
                    ->files()
                    ->name('*.php')
                ;

                foreach ($files as $file) {
                    $tables[] = $file->getBasename('.php');
                }
            }

            foreach (array_unique($tables) as $table) {
                \Controller::loadDataContainer($table);
            }
        }
    }
}

/**
 * Instantiate controller
 */
$updater = new HasteUpdater();
$updater->run();
