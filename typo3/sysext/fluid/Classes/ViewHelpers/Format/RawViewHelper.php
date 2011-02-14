<?php
/*
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
 * Renders the value (or - if omitted - the child nodes) without applying fluid interceptors
 * This is useful if you want to output raw HTML code that is not processed by htmlentities()
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.raw value="{someContent}" />
 * </code>
 * <output>
 * <p>content</p>
 * (depending on the value of {someContent})
 * </output>
 *
 * <code title="Inline notation">
 * {someContent -> f:format.raw()}
 * </code>
 * <output>
 * <p>content</p>
 * (depending on the value of {someContent})
 * </output>
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Tx_Fluid_ViewHelpers_Format_RawViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Disable Fluid interceptors for this ViewHelper
	 * @var boolean
	 */
	protected $escapingInterceptorEnabled = FALSE;

	/**
	 * @param mixed $value The value to output
	 * @return string
	 */
	public function render($value = NULL) {
		if ($value === NULL) {
			return $this->renderChildren();
		} else {
			return $value;
		}
	}

}
?>
