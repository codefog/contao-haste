<?php

namespace Haste\Ajax;

use Haste\Http\Response\JsonResponse;

class ReloadHelper
{
    const TYPE_CONTENT = 'ce';
    const TYPE_MODULE = 'fmd';

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
     * @param string $id
     * @param array  $events
     */
    public static function subscribe($id, array $events)
    {
        foreach ($events as $event) {
            if (!static::$listeners[$event] || !in_array($id, static::$listeners[$event], true)) {
                static::$listeners[$event][] = $id;
            }
        }
    }

    /**
     * Store the response if applicable
     *
     * @param string $id
     * @param string $event
     * @param string $buffer
     */
    public static function storeResponse($id, $event, $buffer)
    {
        if (isset(static::$listeners[$event])
            && !isset(static::$response[$id])
            && in_array($id, static::$listeners[$event], true)
        ) {
            static::$response[$id] = $buffer;
        }
    }

    /**
     * Update the buffer
     *
     * @param string $id
     * @param string $buffer
     * @param bool   $isAjax
     *
     * @return string
     */
    public static function updateBuffer($id, $buffer, $isAjax = false)
    {
        $events = [];

        foreach (static::$listeners as $event => $entries) {
            if (in_array($id, $entries, true)) {
                $events[] = $event;
            }
        }

        if (count($events) > 0) {
            $buffer = static::addDataAttributes($buffer, $id, $events, $isAjax);
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

        return new JsonResponse(static::$response);
    }

    /**
     * Return true if the listener is registered
     *
     * @param string $id
     *
     * @return bool
     */
    public static function isRegistered($id)
    {
        foreach (static::$listeners as $entries) {
            if (in_array($id, $entries, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the events
     *
     * @param string $id
     *
     * @return array
     */
    public static function getEvents($id)
    {
        $events = [];

        foreach (static::$listeners as $event => $entries) {
            if (in_array($id, $entries, true)) {
                $events[] = $event;
            }
        }

        return array_unique($events);
    }

    /**
     * Get the unique ID
     *
     * @param string $type
     * @param int    $id
     *
     * @return string
     */
    public static function getUniqid($type, $id)
    {
        if (!in_array($type, [self::TYPE_CONTENT, self::TYPE_MODULE], true)) {
            throw new \InvalidArgumentException(sprintf('The type "%s" is not supported', $type));
        }

        return $type.$id;
    }

    /**
     * Add the data attributes
     *
     * @param string $buffer
     * @param string $id
     * @param array  $events
     * @param bool   $isAjax
     *
     * @return string
     */
    private static function addDataAttributes($buffer, $id, array $events, $isAjax = false)
    {
        // Merge the data attributes if already present
        preg_replace_callback(
            '/\s?data-haste-ajax-id="[^"]*" data-haste-ajax-listeners="([^"]*)"/',
            function ($matches) use (&$events) {
                $events = array_merge($events, trimsplit(' ', $matches[1]));

                return '';
            },
            $buffer
        );

        // Remove the HTML comments on AJAX request so they don't appear doubled in the DOM
        if ($isAjax) {
            $buffer = preg_replace('/<!--(.*)-->/', '', $buffer);
        }

        // Add the necessary attributes to the first wrapping element
        $buffer = preg_replace(
            '/<([^>!]+)>/',
            sprintf(
                '<$1 data-haste-ajax-id="%s" data-haste-ajax-listeners="%s">',
                $id,
                implode(' ', array_unique($events))
            ),
            $buffer,
            1
        );

        // Trim the buffer to avoid JS break
        return trim($buffer);
    }
}
