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
                $operation['button_callback'] = $this->getDefaultButtonCallback($name, $settings['haste_ajax_operation']);

                // Make sure an icon is set to prevent DC_Table errors
                // (set to '' as the button_callback will return the correct icon)
                $operation['icon'] = '';

                $clickEventString = 'return Haste.toggleAjaxOperation(this, %s);';

                // Add the onclick attribute
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
        }
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


    /**
     * Gets the default button callback.
     *
     * @param string $name
     * @param array  $hasteAjaxOperationSettings
     *
     * @return \Closure
     */
    private function getDefaultButtonCallback($name, array $hasteAjaxOperationSettings)
    {
        return function (array $row, $href, $label, $title, $icon, $attributes) use ($name, $hasteAjaxOperationSettings) {

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
                $icon = $option[0]['icon'];
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

        \Controller::loadDataContainer($dc->table);
        $id = \Input::post('id');
        $currentValue = \Input::post('value');
        $operation = \Input::post('operation');

        $hasteAjaxOperationSettings = $GLOBALS['TL_DCA'][$dc->table]['list']['operations'][$operation]['haste_ajax_operation'];

        if (!isset($hasteAjaxOperationSettings)) {

            return;
        }

        // Response must contain an array like this:
        // ['nextValue'=>'nextValue', 'nextIcon'=>'pathToNextIcon']
        if (is_array($hasteAjaxOperationSettings['ajax_callback'])) {

            $response = (array) \System::importStatic($hasteAjaxOperationSettings['ajax_callback'][0])
                ->$hasteAjaxOperationSettings['ajax_callback'][1]($hasteAjaxOperationSettings, $dc, $id, $currentValue);
        }
        elseif (is_callable($hasteAjaxOperationSettings['ajax_callback'])) {

            $response = (array) $hasteAjaxOperationSettings['ajax_callback']($hasteAjaxOperationSettings, $dc, $id, $currentValue);
        }
        else {

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

            // Update DB
            \Database::getInstance()->prepare('UPDATE ' . $dc->table . ' SET ' . $hasteAjaxOperationSettings['field'] .'=? WHERE id=?')
                ->execute($options[$nextIndex]['value'], $id);

            $response = array(
                'nextValue' => $options[$nextIndex]['value'],
                'nextIcon'  => $options[$nextIndex]['icon']
            );
        }

        $response = new JsonResponse($response);
        $response->send();
    }
}
