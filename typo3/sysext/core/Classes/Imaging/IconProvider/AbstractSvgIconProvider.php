<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Imaging\IconProvider;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Abstract class for all SVG-based icon providers
 *
 * @internal
 */
abstract class AbstractSvgIconProvider implements IconProviderInterface
{
    public const MARKUP_IDENTIFIER_INLINE = 'inline';

    abstract protected function generateMarkup(Icon $icon, array $options): string;
    abstract protected function generateInlineMarkup(array $options): string;

    public function prepareIconMarkup(Icon $icon, array $options = []): void
    {
        $icon->setMarkup($this->generateMarkup($icon, $options));
        $icon->setAlternativeMarkup(self::MARKUP_IDENTIFIER_INLINE, $this->generateInlineMarkup($options));
    }

    /**
     * Calculate public path of SVG file
     */
    protected function getPublicPath(string $source): string
    {
        if (PathUtility::isExtensionPath($source)) {
            return PathUtility::getPublicResourceWebPath($source);
        }
        // TODO: deprecate non extension resources in icon API
        return PathUtility::getAbsoluteWebPath(PathUtility::isAbsolutePath($source) ? $source : GeneralUtility::getFileAbsFileName($source));
    }

    protected function getInlineSvg(string $source): string
    {
        if (!file_exists($source)) {
            return '';
        }

        $svgContent = file_get_contents($source);
        if ($svgContent === false) {
            return '';
        }
        $svgContent = (string)preg_replace('/<script[\s\S]*?>[\s\S]*?<\/script>/i', '', $svgContent);
        $svgElement = simplexml_load_string($svgContent);
        if ($svgElement === false) {
            return '';
        }

        // remove xml version tag
        $domXml = dom_import_simplexml($svgElement);
        return $domXml->ownerDocument->saveXML($domXml->ownerDocument->documentElement);
    }
}
