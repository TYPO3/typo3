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

/**
 * Abstract class for all SVG-based icon providers
 *
 * @internal
 */
abstract class AbstractSvgIconProvider
{
    public const MARKUP_IDENTIFIER_INLINE = 'inline';

    abstract protected function generateMarkup(Icon $icon, array $options): string;
    abstract protected function generateInlineMarkup(array $options): string;

    /**
     * @param Icon $icon
     * @param array $options
     */
    public function prepareIconMarkup(Icon $icon, array $options = []): void
    {
        $icon->setMarkup($this->generateMarkup($icon, $options));
        $icon->setAlternativeMarkup(self::MARKUP_IDENTIFIER_INLINE, $this->generateInlineMarkup($options));
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
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = null;
        if (PHP_MAJOR_VERSION < 8) {
            $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        }
        $svgElement = simplexml_load_string($svgContent);
        if (PHP_MAJOR_VERSION < 8) {
            libxml_disable_entity_loader($previousValueOfEntityLoader);
        }
        if ($svgElement === false) {
            return '';
        }

        // remove xml version tag
        $domXml = dom_import_simplexml($svgElement);
        return $domXml->ownerDocument->saveXML($domXml->ownerDocument->documentElement);
    }
}
