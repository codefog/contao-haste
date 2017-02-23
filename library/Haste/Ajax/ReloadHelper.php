<?php

namespace Haste\Ajax;

use Contao\ContentModel;
use Haste\Http\Response\JsonResponse;

class ReloadHelper
{
    const TYPE_CONTENT_ELEMENT = 'ce';
    const TYPE_FRONTEND_MODULE = 'fmd';

    /**
     * Listeners
     * @var array
     */
    private static $listeners = [];

    /**
     * Response
     * @var array
     */
    private static $response = [];

    /**
     * Subscribe the listener
     *
     * @param string $type
     * @param int    $id
     * @param array  $events
     */
    public static function subscribe($type, $id, array $events)
    {
        list($type, $id) = static::validateTypeAndId($type, $id);

        foreach ($events as $event) {
            if (!static::$listeners[$type][$event] || !in_array($id, static::$listeners[$type][$event], true)) {
                static::$listeners[$type][$event][] = $id;
            }
        }
    }

    /**
     * Store the response if applicable
     *
     * @param string $type
     * @param int    $id
     * @param array  $events
     * @param string $buffer
     */
    public static function storeResponse($type, $id, array $events, $buffer)
    {
        list($type, $id) = static::validateTypeAndId($type, $id);

        foreach ($events as $event) {
            if (!static::$listeners[$type][$event]) {
                continue;
            }

            foreach (static::$listeners[$type][$event] as $v) {
                $key = static::getKey($type, $id);

                if ($v !== $id || static::$response[$key]) {
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
     * Update the buffer
     *
     * @param string $type
     * @param int    $id
     * @param string $buffer
     *
     * @return string
     */
    public static function updateBuffer($type, $id, $buffer)
    {
        list($type, $id) = static::validateTypeAndId($type, $id);

        if (!static::$listeners[$type]) {
            return $buffer;
        }

        $events = [];

        foreach (static::$listeners[$type] as $event => $entries) {
            if (in_array((int)$id, $entries, true)) {
                $events[] = $event;
            }
        }

        if (count($events) > 0) {
            $buffer = static::addDataAttributes($buffer, static::getKey($type, $id), $events);
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
        return count(static::$listeners) > 0;
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
     * Validate the type and ID
     *
     * @param string $type
     * @param int    $id
     *
     * @return array
     */
    private static function validateTypeAndId($type, $id)
    {
        if ($type === self::TYPE_CONTENT_ELEMENT
            && ($element = ContentModel::findByPk($id)) !== null
            && $element->type === 'module'
        ) {
            $type = self::TYPE_FRONTEND_MODULE;
            $id   = (int)$element->module;
        }

        return [$type, (int)$id];
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
     * Get the key
     *
     * @param string $type
     * @param int    $id
     *
     * @return string
     */
    private static function getKey($type, $id)
    {
        return $type.$id;
    }
}
