<?php

namespace Haste\Ajax;

use Contao\ContentModel;
use Contao\Model;
use Contao\ModuleModel;
use Haste\Http\Response\JsonResponse;

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
     * Response
     * @var array
     */
    private static $response = [];

    /**
     * Subscribe the content element
     *
     * @param int   $id
     * @param array $events
     */
    public static function subscribeContentElement($id, array $events)
    {
        $id = (int)$id;

        foreach ($events as $event) {
            if (!static::$elements[$event] || !in_array($id, static::$elements[$event], true)) {
                static::$elements[$event][] = $id;
            }
        }
    }

    /**
     * Store the content element in the response if applicable
     *
     * @param array        $events
     * @param ContentModel $element
     * @param string       $buffer
     */
    public static function storeContentElementResponse(array $events, ContentModel $element, $buffer)
    {
        if ($element->type === 'module' && ($module = ModuleModel::findByPk($element->module)) !== null) {
            static::storeFrontendModuleResponse($events, $module, $buffer);

            return;
        }

        static::storeResponse($events, static::$elements, $element, $buffer);
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
            if (in_array((int)$model->id, $elements, true)) {
                $events[] = $event;
            }
        }

        if (count($events) > 0) {
            $buffer = static::addDataAttributes($buffer, static::getModelKey($model), $events);
        }

        return $buffer;
    }

    /**
     * Subscribe the frontend module
     *
     * @param int   $id
     * @param array $events
     */
    public static function subscribeFrontendModule($id, array $events)
    {
        $id = (int)$id;

        foreach ($events as $event) {
            if (!static::$modules[$event] || !in_array($id, static::$modules[$event], true)) {
                static::$modules[$event][] = $id;
            }
        }
    }

    /**
     * Store the frontend module in the response if applicable
     *
     * @param array       $events
     * @param ModuleModel $module
     * @param string      $buffer
     */
    public static function storeFrontendModuleResponse(array $events, ModuleModel $module, $buffer)
    {
        static::storeResponse($events, static::$modules, $module, $buffer);
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
            if (in_array((int)$model->id, $modules, true)) {
                $events[] = $event;
            }
        }

        if (count($events) > 0) {
            $buffer = static::addDataAttributes($buffer, static::getModelKey($model), $events);
        }

        return $buffer;
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
     * Get the response
     *
     * @return JsonResponse|null
     */
    public static function getResponse()
    {
        if (count(static::$response) < 1) {
            return null;
        }

        return new JsonResponse(array_values(static::$response));
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
        // Remove the HTML comments which break the JS logic
        $buffer = preg_replace('/<!--(.*)-->/', '', $buffer);

        // Add the necessary attributes to the first wrapping element
        $buffer = preg_replace(
            '/<([^>!]+)>/',
            sprintf('<$1 data-haste-ajax-id="%s" data-haste-ajax-listeners="%s">', $id, implode(' ', $events)),
            $buffer,
            1
        );

        // Trim the buffer to avoid JS break
        return trim($buffer);
    }

    /**
     * Store the response
     *
     * @param array  $events
     * @param array  $elements
     * @param Model  $model
     * @param string $buffer
     */
    private static function storeResponse(array $events, array $elements, Model $model, $buffer)
    {
        foreach ($events as $event) {
            if (!$elements[$event]) {
                continue;
            }

            foreach ($elements[$event] as $id) {
                if ($id !== (int)$model->id) {
                    continue;
                }

                $key = static::getModelKey($model);

                if (static::$response[$key]) {
                    continue;
                }

                static::$response[$key] = [
                    'id'     => $key,
                    'buffer' => $buffer,
                ];
            }
        }
    }

    /**
     * Get the model key
     *
     * @param Model $model
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    private static function getModelKey(Model $model)
    {
        switch (true) {
            case $model instanceof ContentModel:
                return 'ce_'.$model->id;

            case $model instanceof ModuleModel:
                return 'mod_'.$model->id;

            default:
                throw new \InvalidArgumentException(sprintf('The model "%s" is not supported', get_class($model)));
        }
    }
}
