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
 * Grouped loop view helper.
 * Loops through the specified values
 *
 * = Examples =
 *
 * <code title="Simple">
 * <f:groupedFor each="{0: {name: 'apple', color: 'green'}, 1: {name: 'cherry', color: 'red'}, 2: {name: 'banana', color: 'yellow'}, 3: {name: 'strawberry', color: 'red'}}" as="fruitsOfThisColor" groupBy="color">
 *   <f:for each="{fruitsOfThisColor}" as="fruit">
 *     {fruit.name}
 *   </f:for>
 * </f:groupedFor>
 * </code>
 *
 * Output:
 * apple cherry strawberry banana
 *
 * <code title="Two dimensional list">
 * <ul>
 *   <f:groupedFor each="{0: {name: 'apple', color: 'green'}, 1: {name: 'cherry', color: 'red'}, 2: {name: 'banana', color: 'yellow'}, 3: {name: 'strawberry', color: 'red'}}" as="fruitsOfThisColor" groupBy="color" groupKey="color">
 *     <li>
 *       {color} fruits:
 *       <ul>
 *         <f:for each="{fruitsOfThisColor}" as="fruit" key="label">
 *           <li>{label}: {fruit.name}</li>
 *         </f:for>
 *       </ul>
 *     </li>
 *   </f:groupedFor>
 * </ul>
 * </code>
 *
 * Output:
 * <ul>
 *   <li>green fruits
 *     <ul>
 *       <li>0: apple</li>
 *     </ul>
 *   </li>
 *   <li>red fruits
 *     <ul>
 *       <li>1: cherry</li>
 *     </ul>
 *     <ul>
 *       <li>3: strawberry</li>
 *     </ul>
 *   </li>
  *   <li>yellow fruits
 *     <ul>
 *       <li>2: banana</li>
 *     </ul>
 *   </li>
 * </ul>
 *
 * @version $Id: GroupedForViewHelper.php 1734 2009-11-25 21:53:57Z stucki $
 * @package Fluid
 * @subpackage ViewHelpers
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_GroupedForViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Iterates through elements of $each and renders child nodes
	 *
	 * @param array $each The array or SplObjectStorage to iterated over
	 * @param string $as The name of the iteration variable
	 * @param string $groupBy Group by this property
	 * @param string $groupKey The name of the variable to store the current group
	 * @return string Rendered string
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function render($each, $as, $groupBy, $groupKey = 'groupKey') {
		$output = '';
		if ($each === NULL) {
			return '';
		}
		if (is_object($each)) {
			if (!$each instanceof Traversable) {
				throw new Tx_Fluid_Core_ViewHelper_Exception('GroupedForViewHelper only supports arrays and objects implementing Traversable interface' , 1253108907);
			}
			$each = $this->convertToArray($each);
		}
		$groups = array();
		foreach ($each as $keyValue => $singleElement) {
			if (is_array($singleElement)) {
				$currentGroupKey = isset($singleElement[$groupBy]) ? $singleElement[$groupBy] : NULL;
			} elseif (is_object($singleElement)) {
				$currentGroupKey = Tx_Extbase_Reflection_ObjectAccess::getProperty($singleElement, $groupBy);
			} else {
				throw new Tx_Fluid_Core_ViewHelper_Exception('GroupedForViewHelper only supports multi-dimensional arrays and objects' , 1253120365);
			}
			$groups[$currentGroupKey][$keyValue] = $singleElement;
		}
		foreach ($groups as $currentGroupKey => $group) {
			$this->templateVariableContainer->add($groupKey, $currentGroupKey);
			$this->templateVariableContainer->add($as, $group);
			$output .= $this->renderChildren();
			$this->templateVariableContainer->remove($groupKey);
			$this->templateVariableContainer->remove($as);
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
