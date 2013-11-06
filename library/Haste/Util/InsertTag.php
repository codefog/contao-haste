<?php

namespace Haste\Util;

use Haste\Haste;

class InsertTag
{
    /**
     * Recursively replace insert tags
     * @param    array|string
     * @return   array|string
     */
    public static function replaceRecursively($varValue)
    {
        if (is_array($varValue)) {
            foreach ($varValue as $k => $v) {
                $varValue[$k] = static::replaceRecursively($v);
            }

            return $varValue;

        } elseif (is_object($varValue)) {
            return $varValue;
        }

        return Haste::getInstance()->call('replaceInsertTags', array($varValue, false));
    }

} 