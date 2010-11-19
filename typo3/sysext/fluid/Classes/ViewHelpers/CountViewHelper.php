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
 * This ViewHelper counts elements of the specified array or countable object.
 *
 * = Examples =
 *
 * <code title="Count array elements">
 * <f:count subject="{0:1, 1:2, 2:3, 3:4}" />
 * </code>
 * <output>
 * 4
 * </output>
 *
 * <code title="inline notation">
 * {objects -> f:count()}
 * </code>
 * <output>
 * 10 (depending on the number of items in {objects})
 * </output>
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Tx_Fluid_ViewHelpers_CountViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Counts the items of a given property.
	 *
	 * @param array $subject The array or ObjectStorage to iterated over
	 * @return integer The number of elements
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function render($subject = NULL) {
		if ($subject === NULL) {
			$subject = $this->renderChildren();
		}
		if (is_object($subject) && !$subject instanceof Countable) {
			throw new Tx_Fluid_Core_ViewHelper_Exception('CountViewHelper only supports arrays and objects implementing Countable interface. Given: "' . get_class($subject) . '"', 1279808078);
		}
		return count($subject);
	}
}

?>
