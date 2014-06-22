<?php
namespace TYPO3\CMS\Install\ViewHelpers;

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
 * Render value of a constant
 */
class ConstantViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Render a constant
	 *
	 * @param string $name Name of the constant
	 * @return string Value of constant
	 */
	public function render($name) {
		return constant($name);
	}
}
