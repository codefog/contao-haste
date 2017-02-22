<?php

namespace Haste\Ajax;

use Contao\ContentModel;
use Contao\Controller;
use Contao\ModuleModel;
use Haste\Http\Response\JsonResponse;
use Haste\Http\Response\Response;

class ReloadHelper
{
    /**
     * Content element listeners
     * @var array
     */
    private static $elements = [];

    /**
     * Module listeners
     * @var array
     */
    private static $modules = [];

    /**
     * Subscribe the content element
     *
     * @param int    $id
     * @param array  $events
     * @param string $inColumn
     */
    public static function subscribeContentElement($id, array $events, $inColumn = 'main')
    {
        $id = (int)$id;

        foreach ($events as $event) {
            if (!static::$elements[$event][$id]) {
                static::$elements[$event][$id] = ['id' => $id, 'column' => $inColumn];
            }
        }
    }

    /**
     * Subscribe the frontend module
     *
     * @param int    $id
     * @param array  $events
     * @param string $inColumn
     */
    public static function subscribeFrontendModule($id, array $events, $inColumn = 'main')
    {
        $id = (int)$id;

        foreach ($events as $event) {
            if (!static::$modules[$event][$id]) {
                static::$modules[$event][$id] = ['id' => $id, 'column' => $inColumn];
            }
        }
    }

    /**
     * Return true if there are listeners
     *
     * @return bool
     */
    public static function hasListeners()
    {
        return count(static::$modules) > 0 || count(static::$elements) > 0;
    }

    /**
     * Dispatch the events
     *
     * @param array $events
     *
     * @return Response
     */
    public static function dispatch(array $events)
    {
        $items = [];

        foreach ($events as $event) {
            if (!static::$modules[$event] && !static::$elements[$event]) {
                return new Response(sprintf('The event "%s" is not in the registry', $event), 400);
            }

            // Generate content elements
            if (static::$elements[$event]) {
                foreach (static::$elements[$event] as $id => $element) {
                    $key = 'ce_'.$id;

                    if (!$items[$key]) {
                        $items[$key] = [
                            'id'     => $key,
                            'buffer' => Controller::getContentElement($element['id'], $element['column']),
                        ];
                    }
                }
            }

            // Generate frontend modules
            if (static::$modules[$event]) {
                foreach (static::$modules[$event] as $id => $module) {
                    $key = 'mod_'.$id;

                    if (!$items[$key]) {
                        $items[$key] = [
                            'id'     => 'mod_'.$id,
                            'buffer' => Controller::getFrontendModule($module['id'], $module['column']),
                        ];
                    }
                }
            }
        }

        return new JsonResponse(array_values($items));
    }

    /**
     * Update the frontend module buffer
     *
     * @param ModuleModel $model
     * @param string      $buffer
     *
     * @return string
     */
    public static function updateFrontendModuleBuffer(ModuleModel $model, $buffer)
    {
        $events = [];

        foreach (static::$modules as $event => $modules) {
            foreach ($modules as $module) {
                if ($module['id'] === (int)$model->id) {
                    $events[] = $event;
                }
            }
        }

        if (count($events) > 0) {
            $buffer = static::addDataAttributes($buffer, 'mod_'.$model->id, $events);
        }

        return $buffer;
    }

    /**
     * Update the content element buffer
     *
     * @param ContentModel $model
     * @param string       $buffer
     *
     * @return string
     */
    public static function updateContentElementBuffer(ContentModel $model, $buffer)
    {
        if ($model->type === 'module' && ($module = ModuleModel::findByPk($model->module)) !== null) {
            return static::updateFrontendModuleBuffer($module, $buffer);
        }

        $events = [];

        foreach (static::$elements as $event => $elements) {
            foreach ($elements as $element) {
                if ($element['id'] === (int)$model->id) {
                    $events[] = $event;
                }
            }
        }

        if (count($events) > 0) {
            $buffer = static::addDataAttributes($buffer, 'ce_'.$model->id, $events);
        }

        return $buffer;
    }

    /**
     * Add the data attributes
     *
     * @param string $buffer
     * @param string $id
     * @param array  $events
     *
     * @return string
     */
    private static function addDataAttributes($buffer, $id, array $events)
    {
        $dom = new \DOMDocument();

        // Temporarily suppress the HTML5 tag errors (such as for <section>)
        // @see http://stackoverflow.com/a/9149241/3628692
        libxml_use_internal_errors(true);
        $dom->loadHTML($buffer);
        libxml_use_internal_errors(false);

        // Find the first tag
        $node = $dom->getElementsByTagName('body')->item(0)->childNodes->item(0);

        if ($node !== null) {
            $attribute        = $dom->createAttribute('data-haste-ajax-id');
            $attribute->value = $id;
            $node->appendChild($attribute);

            $attribute        = $dom->createAttribute('data-haste-ajax-listeners');
            $attribute->value = implode(' ', $events);
            $node->appendChild($attribute);
        }

        return $dom->saveHTML($node);
    }
}
