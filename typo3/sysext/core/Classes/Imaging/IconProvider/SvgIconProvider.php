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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class SvgIconProvider provides icons that are classic <img> tags using vectors as source
 */
class SvgIconProvider extends AbstractSvgIconProvider
{
    /**
     * @param Icon $icon
     * @param array $options
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function generateMarkup(Icon $icon, array $options): string
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException('[' . $icon->getIdentifier() . '] The option "source" is required and must not be empty', 1460976566);
        }

        $source = $options['source'];
        return '<img src="' . htmlspecialchars($this->getPublicPath($source)) . '" width="' . $icon->getDimension()->getWidth() . '" height="' . $icon->getDimension()->getHeight() . '" alt="" />';
    }

    /**
     * @param array $options
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function generateInlineMarkup(array $options): string
    {
        if (empty($options['source'])) {
            throw new \InvalidArgumentException('The option "source" is required and must not be empty', 1460976610);
        }

        $source = $options['source'];

        if (PathUtility::isExtensionPath($source) || !PathUtility::isAbsolutePath($source)) {
            $source = GeneralUtility::getFileAbsFileName($source);
        }

        return $this->getInlineSvg($source);
    }
}
