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

namespace TYPO3\CMS\Core\Imaging\IconProvider;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class BitmapIconProvider provides icons that are classic <img> tags using bitmaps as source
 */
class BitmapIconProvider implements IconProviderInterface
{
    const MARKUP_IDENTIFIER_INLINE = 'inline';

    /**
     * @param Icon $icon
     * @param array $options
     */
    public function prepareIconMarkup(Icon $icon, array $options = [])
    {
        $icon->setMarkup($this->generateMarkup($icon, $options));
        $icon->setAlternativeMarkup(self::MARKUP_IDENTIFIER_INLINE, $this->generateInlineMarkup($icon, $options));
    }

    /**
     * @param Icon $icon
     * @param array $options
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function generateMarkup(Icon $icon, array $options)
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException('[' . $icon->getIdentifier() . '] The option "source" is required and must not be empty', 1440754980);
        }

        $source = $options['source'];

        return '<img src="' . htmlspecialchars($this->getPublicPath($source)) . '" width="' . $icon->getDimension()->getWidth() . '" height="' . $icon->getDimension()->getHeight() . '" alt="" />';
    }

    /**
     * Calculate public path of image file
     *
     * @param string $source
     * @return string
     */
    protected function getPublicPath(string $source): string
    {
        if (PathUtility::isExtensionPath($source)) {
            return PathUtility::getPublicResourceWebPath($source);
        }
        // TODO: deprecate non extension resources in icon API
        return PathUtility::getAbsoluteWebPath(PathUtility::isAbsolutePath($source) ? $source : GeneralUtility::getFileAbsFileName($source));
    }

    /**
     * @param Icon $icon
     * @param array $options
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function generateInlineMarkup(Icon $icon, array $options)
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException('The option "source" is required and must not be empty', 1471460676);
        }

        $source = $options['source'];

        $filePath = PathUtility::isAbsolutePath($source) ? $source : GeneralUtility::getFileAbsFileName($source);

        if (!file_exists($filePath)) {
            return '';
        }

        return sprintf(
            '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %2$d" width="%1$d" height="%2$d"><image width="%1$d" height="%1$d" xlink:href="%3$s"/></svg>',
            $icon->getDimension()->getWidth(),
            $icon->getDimension()->getHeight(),
            $this->getPublicPath($source)
        );
    }
}
