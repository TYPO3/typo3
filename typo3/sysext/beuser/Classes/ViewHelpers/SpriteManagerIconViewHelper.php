<?php
namespace TYPO3\CMS\Beuser\ViewHelpers;

/**
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

/**
 * Displays sprite icon identified by iconName key
 *
 * @author Felix Kopp <felix-source@phorax.com>
 */
class SpriteManagerIconViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Prints sprite icon html for $iconName key
	 *
	 * @param string $iconName
	 * @param array $options
	 * @return string
	 */
	public function render($iconName, $options = array()) {
		return \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($iconName, $options);
	}

}
