<?php
namespace TYPO3\CMS\Styleguide\UserFunctions;

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
 * A user function rendering a type=user TCA type used in user_1
 */
class TypeUser1 {

	/**
	 * @param array $parameters
	 * @param $parentObject
	 * @return string
	 */
	public function render(array $parameters, $parentObject) {
		$html = array();
		$html[] = '<div style="border: 1px dashed ' . $parameters['parameters']['color'] . '" >';
		$html[] = '<h2>Own form field using a parameter</h2>';
		$html[] = '<input'
			. ' type="input"'
			. ' name="' . $parameters['itemFormElName'] . '"'
			. ' value="'.htmlspecialchars($parameters['itemFormElValue']) . '"'
			. ' onchange="'.htmlspecialchars(implode('', $parameters['fieldChangeFunc'])).'"' . $parameters['onFocus']
			. ' />';
		$html[] = '</div>';
		return implode(LF, $html);
	}
}
