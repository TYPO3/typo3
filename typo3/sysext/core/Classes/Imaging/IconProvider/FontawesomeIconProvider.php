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

/**
 * Class FontawesomeIconProvider
 */
class FontawesomeIconProvider implements IconProviderInterface
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
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function generateMarkup(Icon $icon, array $options)
    {
        if (empty($options['name'])) {
            throw new \InvalidArgumentException('The option "name" is required and must not be empty', 1440754978);
        }
        if (preg_match('/^[a-zA-Z0-9\\-]+$/', $options['name']) !== 1) {
            throw new \InvalidArgumentException('The option "name" must only contain characters a-z, A-Z, 0-9 or -', 1440754979);
        }

        return '<span class="icon-unify"><i class="fa fa-' . htmlspecialchars($options['name']) . '"></i></span>';
    }
}
