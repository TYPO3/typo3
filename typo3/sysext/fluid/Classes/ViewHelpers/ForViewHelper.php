<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Loop view helper which can be used to interate over array.
 * Implements what a basic foreach()-PHP-method does.
 *
 * = Examples =
 *
 * <code title="Simple Loop">
 * <f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo">{foo}</f:for>
 * </code>
 * <output>
 * 1234
 * </output>
 *
 * <code title="Output array key">
 * <ul>
 *   <f:for each="{fruit1: 'apple', fruit2: 'pear', fruit3: 'banana', fruit4: 'cherry'}" as="fruit" key="label">
 *     <li>{label}: {fruit}</li>
 *   </f:for>
 * </ul>
 * </code>
 * <output>
 * <ul>
 *   <li>fruit1: apple</li>
 *   <li>fruit2: pear</li>
 *   <li>fruit3: banana</li>
 *   <li>fruit4: cherry</li>
 * </ul>
 * </output>
 *
 * @version $Id: ForViewHelper.php 1734 2009-11-25 21:53:57Z stucki $
 * @package Fluid
 * @subpackage ViewHelpers
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_ForViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Iterates through elements of $each and renders child nodes
	 *
	 * @param array $each The array or SplObjectStorage to iterated over
	 * @param string $as The name of the iteration variable
	 * @param string $key The name of the variable to store the current array key
	 * @param boolean $reverse If enabled, the iterator will start with the last element and proceed reversely
	 * @return string Rendered string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function render($each, $as, $key = '', $reverse = FALSE) {
		$output = '';
		if ($each === NULL) {
			return '';
		}
		if (is_object($each)) {
			if (!$each instanceof Traversable) {
				throw new Tx_Fluid_Core_ViewHelper_Exception('ForViewHelper only supports arrays and objects implementing Traversable interface' , 1248728393);
			}
			$each = $this->convertToArray($each);
		}

		if ($reverse === TRUE) {
			$each = array_reverse($each);
		}

		$output = '';
		foreach ($each as $keyValue => $singleElement) {
			$this->templateVariableContainer->add($as, $singleElement);
			if ($key !== '') {
				$this->templateVariableContainer->add($key, $keyValue);
			}
			$output .= $this->renderChildren();
			$this->templateVariableContainer->remove($as);
			if ($key !== '') {
				$this->templateVariableContainer->remove($key);
			}
		}
		return $output;
	}

	/**
	 * Turns the given object into an array.
	 * The object has to implement the Traversable interface
	 *
	 * @param Traversable $object The object to be turned into an array. If the object implements Iterator the key will be preserved.
	 * @return array The resulting array
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function convertToArray(Traversable $object) {
		$array = array();
		foreach ($object as $keyValue => $singleElement) {
			$array[$keyValue] = $singleElement;
		}
		return $array;
	}
}

?>
