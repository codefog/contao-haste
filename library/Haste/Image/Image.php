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

namespace Haste\Image;

class Image
{

    /**
     * Apply a watermark to an image
     * @param string
     * @param string
     * @param string
     * @param string
     */
    public static function addWatermark($image, $watermark, $position='br', $target=null)
    {
        $image = urldecode($image);

        if (!is_file(TL_ROOT . '/' . $image) || !is_file(TL_ROOT . '/' . $watermark))
        {
            return $image;
        }

        $objFile = new \File($image);
        $strCacheName = 'system/html/' . $objFile->filename . '-' . substr(md5($watermark . '-' . $position . '-' . $objFile->mtime), 0, 8) . '.' . $objFile->extension;

        // Return the path of the new image if it exists already
        if (is_file(TL_ROOT . '/' . $strCacheName))
        {
            return $strCacheName;
        }

        // !HOOK: override image watermark routine
        if (isset($GLOBALS['TL_HOOKS']['watermarkImage']) && is_array($GLOBALS['TL_HOOKS']['watermarkImage']))
        {
            foreach ($GLOBALS['TL_HOOKS']['watermarkImage'] as $callback)
            {
                $objCallback = \System::importStatic($callback[0]);
                $return = $objCallback->$callback[1]($image, $watermark, $position, $target);

                if (is_string($return))
                {
                    return $return;
                }
            }
        }

        $arrGdinfo = gd_info();

        // Load image
        switch ($objFile->extension)
        {
            case 'gif':
                if ($arrGdinfo['GIF Read Support'])
                {
                    $strImage = imagecreatefromgif(TL_ROOT . '/' . $image);
                }
                break;

            case 'jpg':
            case 'jpeg':
                if ($arrGdinfo['JPG Support'] || $arrGdinfo['JPEG Support'])
                {
                    $strImage = imagecreatefromjpeg(TL_ROOT . '/' . $image);
                }
                break;

            case 'png':
                if ($arrGdinfo['PNG Support'])
                {
                    $strImage = imagecreatefrompng(TL_ROOT . '/' . $image);
                }
                break;
        }

        // Image could not be read
        if (!$strImage)
        {
            return $image;
        }

        $objWatermark = new \File($watermark);

        // Load watermark
        switch ($objWatermark->extension)
        {
            case 'gif':
                if ($arrGdinfo['GIF Read Support'])
                {
                    $strWatermark = imagecreatefromgif(TL_ROOT . '/' . $watermark);
                }
                break;

            case 'jpg':
            case 'jpeg':
                if ($arrGdinfo['JPG Support'] || $arrGdinfo['JPEG Support'])
                {
                    $strWatermark = imagecreatefromjpeg(TL_ROOT . '/' . $watermark);
                }
                break;

            case 'png':
                if ($arrGdinfo['PNG Support'])
                {
                    $strWatermark = imagecreatefrompng(TL_ROOT . '/' . $watermark);
                }
                break;
        }

        // Image could not be read
        if (!$strWatermark)
        {
            return $image;
        }

        switch ($position)
        {
            case 'tl':
                $x = 0;
                $y = 0;
                break;

            case 'tc':
                $x = ($objFile->width/2) - ($objWatermark->width/2);
                $y = 0;
                break;

            case 'tr':
                $x = $objFile->width - $objWatermark->width;
                $y = 0;
                break;

            case 'cc':
                $x = ($objFile->width/2) - ($objWatermark->width/2);
                $y = ($objFile->height/2) - ($objWatermark->height/2);
                break;

            case 'bl':
                $x = 0;
                $y = $objFile->height - $objWatermark->height;
                break;

            case 'bc':
                $x = ($objFile->width/2) - ($objWatermark->width/2);
                $y = $objFile->height - $objWatermark->height;
                break;

            case 'br':
            default:
                $x = $objFile->width - $objWatermark->width;
                $y = $objFile->height - $objWatermark->height;
                break;
        }

        imagecopy($strImage, $strWatermark, $x, $y, 0, 0, $objWatermark->width, $objWatermark->height);

        // Fallback to PNG if GIF ist not supported
        if ($objFile->extension == 'gif' && !$arrGdinfo['GIF Create Support'])
        {
            $objFile->extension = 'png';
        }

        // Create the new image
        switch ($objFile->extension)
        {
            case 'gif':
                imagegif($strImage, TL_ROOT . '/' . $strCacheName);
                break;

            case 'jpg':
            case 'jpeg':
                imagejpeg($strImage, TL_ROOT . '/' . $strCacheName, (!$GLOBALS['TL_CONFIG']['jpgQuality'] ? 80 : $GLOBALS['TL_CONFIG']['jpgQuality']));
                break;

            case 'png':
                imagepng($strImage, TL_ROOT . '/' . $strCacheName);
                break;
        }

        // Destroy the temporary images
        imagedestroy($strImage);
        imagedestroy($strWatermark);

        // Resize the original image
        if ($target)
        {
            $objFiles = \Files::getInstance();
            $objFiles->copy($strCacheName, $target);

            return $target;
        }

        // Set the file permissions when the Safe Mode Hack is used
        if ($GLOBALS['TL_CONFIG']['useFTP'])
        {
            $objFiles = \Files::getInstance();
            $objFiles->chmod($strCacheName, 0644);
        }

        // Return the path to new image
        return $strCacheName;
    }
}
