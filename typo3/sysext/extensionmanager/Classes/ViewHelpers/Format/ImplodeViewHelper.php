<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers\Format;

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
 * View Helper for imploding arrays
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class ImplodeViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Implodes a string
	 *
	 * @param array $implode
	 * @param string $delimiter
	 * @return string the altered string.
	 * @api
	 */
	public function render(array $implode, $delimiter = ', ') {
		return implode($delimiter, $implode);
	}

}
