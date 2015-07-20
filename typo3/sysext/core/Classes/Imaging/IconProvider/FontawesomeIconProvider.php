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
class FontawesomeIconProvider implements IconProviderInterface {

	/**
	 * @param Icon $icon
	 * @param array $options
	 */
	public function prepareIconMarkup(Icon $icon, array $options = array()) {
		$icon->setMarkup($this->generateMarkup($icon, $options));
	}

	/**
	 * @param Icon $icon
	 * @param array $options
	 *
	 * @return string
	 */
	protected function generateMarkup(Icon $icon, array $options) {
		$additionalClasses = (!empty($options['additionalClasses'])) ? ' ' . $options['additionalClasses'] : '';
		return '<span class="icon-unify"><i class="fa fa-' . htmlspecialchars($options['name']) . htmlspecialchars($additionalClasses) . '"></i></span>';
	}
}
