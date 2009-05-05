<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: ForViewHelper.php 2177 2009-04-22 22:52:02Z bwaidelich $
 */

/**
 * Loop view helper
 * 
 * = Examples =
 *
 * <code title="Simple">
 * <f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo">{foo}</f:for>
 * </code>
 * 
 * Output:
 * 1234
 * 
 * <code title="Output array key">
 * <ul>
 *   <f:for each="{fruit1: 'apple', fruit2: 'pear', fruit3: 'banana', fruit4: 'cherry'}" as="fruit" key="label">
 *     <li>{label}: {fruit}</li>
 *   </f:for>
 * </ul>
 * </code>
 * 
 * Output:
 * <ul>
 *   <li>fruit1: apple</li>
 *   <li>fruit2: pear</li>
 *   <li>fruit3: banana</li>
 *   <li>fruit4: cherry</li>
 * </ul>
 * 
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: ForViewHelper.php 2177 2009-04-22 22:52:02Z bwaidelich $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_ForViewHelper extends Tx_Fluid_Core_AbstractViewHelper {

	/**
	 * Iterates through elements of $each and renders child nodes 
	 *
	 * @param array $each The array to be iterated over
	 * @param string $as The name of the iteration variable
	 * @param string $key The name of the variable to store the current array key
	 * @return string Rendered string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($each, $as, $key = '') {
		if (empty($each)) {
			return '';
		}
		$output = '';
		foreach ($each as $keyValue => $singleElement) {
			$this->variableContainer->add($as, $singleElement);
			if ($key !== '') {
				$this->variableContainer->add($key, $keyValue);
			}
			$output .= $this->renderChildren();
			$this->variableContainer->remove($as);
			if ($key !== '') {
				$this->variableContainer->remove($key);
			}
		}
		return $output;
	}
}

?>
