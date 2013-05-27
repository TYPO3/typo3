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
 *
 *
 * @version $Id$
 * @package Fluid
 * @subpackage Core\Rendering
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_Core_Rendering_HtmlSpecialCharsPostProcessor implements Tx_Fluid_Core_Rendering_ObjectAccessorPostProcessorInterface {

	/**
	 * Process an Object Accessor by wrapping it into HTML.
	 * NOT part of public API.
	 *
	 * @param mixed $object the object that is currently rendered
	 * @param boolean $enabled TRUE if post processing is currently enabled.
	 * @return mixed $object the original object. If not within arguments and of type string, the value is htmlspecialchar'ed
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function process($object, $enabled) {
		if ($enabled === TRUE && is_string($object)) {
			return htmlspecialchars($object);
		}
		return $object;
	}
}
?>