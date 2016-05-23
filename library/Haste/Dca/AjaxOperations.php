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


namespace Haste\Dca;
use Haste\Http\Response\JsonResponse;
use Haste\Util\Debug;

/**
 * Class AjaxOperations
 *
 * Eases working with ajax options so tedious toggle callbacks can be omitted.
 */
class AjaxOperations
{
    /**
     * Execute AJAX post actions to toggle.
     *
     * @param string         $action
     * @param \DataContainer $dc
     */
    public function executePostActions($action, \DataContainer $dc)
    {
        if ($action !== 'hasteAjaxOperation') {

            return;
        }

        $id = $dc->id = \Input::post('id');
        $currentValue = \Input::post('value');
        $operation = \Input::post('operation');

        $hasteAjaxOperationSettings = $GLOBALS['TL_DCA'][$dc->table]['list']['operations'][$operation]['haste_ajax_operation'];

        if (!isset($hasteAjaxOperationSettings)) {

            return;
        }

        // Check permissions
        if (!$this->checkPermission($dc->table, $hasteAjaxOperationSettings)) {

            \System::log(
                sprintf('Not enough permissions to toggle field %s::%s',
                    $dc->table,
                    $hasteAjaxOperationSettings['field']
                ),
                __METHOD__,
                TL_ERROR
            );

            \Controller::redirect('contao/main.php?act=error');
        }

        // Initialize versioning
        $versions = new \Versions($dc->table, $id);
        $versions->initialize();

        // Determine next value and icon
        $options = $this->getOptions($hasteAjaxOperationSettings);
        $nextIndex = 0;

        foreach ($options as $k => $option) {
            if ($option['value'] == $currentValue) {
                $nextIndex = $k + 1;
            }
        }

        // Make sure that if $nextIndex does not exist it's the first
        if (!isset($options[$nextIndex])) {
            $nextIndex = 0;
        }

        $value = $options[$nextIndex]['value'];
        $value = $this->executeSaveCallback($dc, $value, $hasteAjaxOperationSettings);

        // Update DB
        \Database::getInstance()->prepare('UPDATE ' . $dc->table . ' SET ' . $hasteAjaxOperationSettings['field'] .'=? WHERE id=?')
            ->execute($value, $id);

        $versions->create();
        if ($GLOBALS['TL_DCA'][$dc->table]['config']['enableVersioning']) {
            \System::log(
                sprintf('A new version of record "%s.id=%s" has been created',
                    $dc->table,
                    $id
                ),
                __METHOD__,
                TL_GENERAL
            );
        }
        
        $response = array(
            'nextValue' => $options[$nextIndex]['value'],
            'nextIcon'  => $options[$nextIndex]['icon']
        );

        $response = new JsonResponse($response);
        $response->send();
    }

    /**
     * Modifies the DCA.
     *
     * @param string $table
     */
    public function modifyDca($table)
    {
        if (TL_MODE !== 'BE'
            || !isset($GLOBALS['TL_DCA'][$table]['list']['operations'])
            || !is_array($GLOBALS['TL_DCA'][$table]['list']['operations'])
        ) {
            return;
        }

        foreach ($GLOBALS['TL_DCA'][$table]['list']['operations'] as $name => $settings) {
            if (!isset($settings['haste_ajax_operation'])) {
                continue;
            }

            $operation = &$GLOBALS['TL_DCA'][$table]['list']['operations'][$name];

            // Add the JavaScript
            $GLOBALS['TL_JAVASCRIPT'][] = Debug::uncompressedFile('system/modules/haste/assets/haste.min.js') . '|static';

            // Add default button callback to display the correct initial state
            // but only add it if not already present
            if (!isset($settings['button_callback'])) {
                $operation['button_callback'] = $this->getDefaultButtonCallback($name, $table, $settings['haste_ajax_operation']);

                // Make sure an icon is set to prevent DC_Table errors
                // (set to '' as the button_callback will return the correct icon)
                $operation['icon'] = '';

                // Add the onclick attribute
                $this->addOnClickAttribute($operation);
            }
        }
    }

    /**
     * Adds the "onclick" attribute to the operation DCA.
     *
     * @param $operation
     */
    private function addOnClickAttribute(&$operation)
    {
        $clickEventString = 'return Haste.toggleAjaxOperation(this, %s);';

        if (!isset($operation['attributes'])) {
            $operation['attributes'] = sprintf('onclick="%s"', $clickEventString);
        } else {
            // onclick attribute already present
            if (strpos($operation['attributes'], 'onclick="') !== false) {
                $operation['attributes'] = str_replace(
                    'onclick="',
                    'onclick="' . $clickEventString,
                    $operation['attributes']
                );
            } else {
                $operation['attributes'] = $clickEventString . $operation['attributes'];
            }
        }
    }

    /**
     * Checks if user has the permissions for the field.
     *
     * @param       $table
     * @param array $hasteAjaxOperationSettings
     *
     * @return bool
     */
    private function checkPermission($table, array $hasteAjaxOperationSettings)
    {
        $hasPermission = true;

        if ($GLOBALS['TL_DCA'][$table]['fields'][$hasteAjaxOperationSettings['field']]['exclude']
            && !\BackendUser::getInstance()->hasAccess($table . '::' . $hasteAjaxOperationSettings['field'], 'alexf')
        ) {

            $hasPermission = false;
        }

        if (is_array($hasteAjaxOperationSettings['check_permission_callback'])) {

            \System::importStatic($hasteAjaxOperationSettings['check_permission_callback'][0])
                ->{$hasteAjaxOperationSettings['check_permission_callback'][1]}($table, $hasteAjaxOperationSettings, $hasPermission);
        }
        elseif (is_callable($hasteAjaxOperationSettings['check_permission_callback'])) {

            $hasteAjaxOperationSettings['check_permission_callback']($table, $hasteAjaxOperationSettings, $hasPermission);
        }

        return $hasPermission;
    }

    /**
     * Executes the save_callback.
     *
     * @param \DataContainer $dc
     * @param mixed          $value
     * @param array          $hasteAjaxOperationSettings
     *
     * @return mixed
     */
    private function executeSaveCallback(\DataContainer $dc, $value, array $hasteAjaxOperationSettings)
    {
        $field = $hasteAjaxOperationSettings['field'];

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['save_callback'])) {
            foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['save_callback'] as $callback) {
                if (is_array($callback)) {

                    $value = \System::importStatic($callback[0])->{$callback[1]}($value, $dc);
                }
                elseif (is_callable($callback)) {
                    $value = $callback($value, $dc);
                }
            }
        }

        return $value;
    }

    /**
     * Gets the default button callback.
     *
     * @param string $name
     * @param string $table
     * @param array  $hasteAjaxOperationSettings
     *
     * @return \Closure
     */
    private function getDefaultButtonCallback($name, $table, array $hasteAjaxOperationSettings)
    {
        return function (array $row, $href, $label, $title, $icon, $attributes) use ($name, $table, $hasteAjaxOperationSettings) {

            // If the user doesn't have access, hide the button
            if (!$this->checkPermission($table, $hasteAjaxOperationSettings)) {

                return '';
            }

            $value = $row[$hasteAjaxOperationSettings['field']];
            $options = $this->getOptions($hasteAjaxOperationSettings);
            $icon = null;

            foreach ($options as $k => $option) {
                if ($option['value'] == $value) {
                    $icon = $option['icon'];
                }
            }

            // Default is the first value in the options array
            if (null === $icon) {
                $icon = $options[0]['icon'];
            }

            return sprintf('<a data-haste-ajax-operation-value="%s" data-haste-ajax-operation-name="%s" href="%s" title="%s"%s>%s</a> ',
                $value,
                $name,
                \Backend::addToUrl($href),
                specialchars($title),
                $attributes,
                \Image::getHtml($icon, $label)
            );
        };
    }

    /**
     * Gets the possible options for that operation
     * Must be an array in the following format:
     *  [
     *      [
     *          'value'     => '',
     *          'icon'      => 'invisible.gif'
     *      ],
     *      [
     *          'value'     => '1',
     *          'icon'      => 'visible.gif'
     *      ]
     * ]
     *
     * whereas "value" stands for the value to be stored and "icon"
     * for the path to the icon for that option.
     *
     * @param array $hasteAjaxOperationSettings
     *
     * @return array
     */
    private function getOptions(array $hasteAjaxOperationSettings)
    {
        return (array) $hasteAjaxOperationSettings['options'];
    }
}
