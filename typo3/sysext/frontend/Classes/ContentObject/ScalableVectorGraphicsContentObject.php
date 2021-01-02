<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Frontend\ContentObject;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Contains SVG content object.
 */
class ScalableVectorGraphicsContentObject extends AbstractContentObject
{
    /**
     * Rendering the cObject, SVG
     *
     * @param array $conf Array of TypoScript properties
     * @return string
     */
    public function render($conf = []): string
    {
        $renderMode = $this->cObj->stdWrapValue('renderMode', $conf ?? []);

        if ($renderMode === 'inline') {
            return $this->renderInline($conf);
        }

        return $this->renderObject($conf);
    }

    /**
     * @param array $conf
     *
     * @return string
     */
    protected function renderInline(array $conf): string
    {
        $src = $this->resolveAbsoluteSourcePath($conf);
        [$width, $height, $isDefaultWidth, $isDefaultHeight] = $this->getDimensions($conf);

        $content = '';
        if (file_exists($src)) {
            $svgContent = (string)file_get_contents($src);
            $svgContent = preg_replace('/<script[\s\S]*?>[\s\S]*?<\/script>/i', '', $svgContent) ?? '';
            // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
            $previousValueOfEntityLoader = null;
            if (PHP_MAJOR_VERSION < 8) {
                $previousValueOfEntityLoader = libxml_disable_entity_loader();
            }
            $svgElement = simplexml_load_string($svgContent);
            if (PHP_MAJOR_VERSION < 8) {
                libxml_disable_entity_loader($previousValueOfEntityLoader);
            }

            $domXml = dom_import_simplexml($svgElement);
            if (!$isDefaultWidth) {
                $domXml->setAttribute('width', $width);
            }
            if (!$isDefaultHeight) {
                $domXml->setAttribute('height', $height);
            }
            // remove xml version tag
            $content = $domXml->ownerDocument->saveXML($domXml->ownerDocument->documentElement);
        } else {
            $value = $this->cObj->stdWrapValue('value', $conf ?? []);
            if (!empty($value)) {
                $content = [];
                $content[] = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="' . (int)$width . '" height="' . (int)$height . '">';
                $content[] = $value;
                $content[] = '</svg>';
                $content = implode(LF, $content);
            }
        }
        if (isset($conf['stdWrap.'])) {
            $content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
        }
        return $content;
    }

    /**
     * Render the SVG as <object> tag
     * @param array $conf
     *
     * @return string
     */
    protected function renderObject(array $conf): string
    {
        $src = $this->resolveAbsoluteSourcePath($conf);
        [$width, $height] = $this->getDimensions($conf);

        $src = $src === '' ? null : PathUtility::getAbsoluteWebPath($src);

        $content = [];
        if ($src) {
            $content[] = '<!--[if IE]>';
            $content[] = '  <object src="' . htmlspecialchars($src) . '" classid="image/svg+xml" width="' . (int)$width . '" height="' . (int)$height . '">';
            $content[] = '<![endif]-->';
            $content[] = '<!--[if !IE]>-->';
            $content[] = '  <object data="' . htmlspecialchars($src) . '" type="image/svg+xml" width="' . (int)$width . '" height="' . (int)$height . '">';
            $content[] = '<!--<![endif]-->';
            $content[] = '</object>';
        }
        $content = implode(LF, $content);
        if (isset($conf['stdWrap.'])) {
            $content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
        }
        return $content;
    }

    /**
     * @param array $conf
     *
     * @return string
     */
    protected function resolveAbsoluteSourcePath(array $conf): string
    {
        $src = (string)$this->cObj->stdWrapValue('src', $conf ?? []);
        return GeneralUtility::getFileAbsFileName($src);
    }

    /**
     * @param array $conf
     *
     * @return array
     */
    protected function getDimensions(array $conf): array
    {
        $isDefaultWidth = false;
        $isDefaultHeight = false;
        $width = $this->cObj->stdWrapValue('width', $conf ?? []);
        $height = $this->cObj->stdWrapValue('height', $conf ?? []);

        if (empty($width)) {
            $isDefaultWidth = true;
            $width = 600;
        }
        if (empty($height)) {
            $isDefaultHeight = true;
            $height = 400;
        }

        return [$width, $height, $isDefaultWidth, $isDefaultHeight];
    }
}
