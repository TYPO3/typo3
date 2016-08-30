<?php
namespace TYPO3\CMS\Core\Imaging\IconProvider;

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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Class BitmapIconProvider provides icons that are classic <img> tags using bitmaps as source
 */
class BitmapIconProvider implements IconProviderInterface
{
    /**
     * @param Icon $icon
     * @param array $options
     */
    public function prepareIconMarkup(Icon $icon, array $options = [])
    {
        $icon->setMarkup($this->generateMarkup($icon, $options));
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

        if (StringUtility::beginsWith($source, 'EXT:') || !StringUtility::beginsWith($source, '/')) {
            $source = GeneralUtility::getFileAbsFileName($source);
        }
        $source = PathUtility::getAbsoluteWebPath($source);

        return '<img src="' . htmlspecialchars($source) . '" width="' . $icon->getDimension()->getWidth() . '" height="' . $icon->getDimension()->getHeight() . '" />';
    }
}
