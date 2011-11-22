<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
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
 * The EscapeViewHelper is used to escape variable content in various ways. By
 * default HTML is the target.
 *
 * = Examples =
 *
 * <code title="HTML">
 * <f:escape>{text}</f:escape>
 * </code>
 * <output>
 * Text with & " ' < > * replaced by HTML entities (htmlspecialchars applied).
 * </output>
 *
 * <code title="Entities">
 * <f:escape type="entities">{text}</f:escape>
 * </code>
 * <output>
 * Text with all possible chars replaced by HTML entities (htmlentities applied).
 * </output>
 *
 * <code title="URL">
 * <f:escape type="url">{text}</f:escape>
 * </code>
 * <output>
 * Text encoded for URL use (rawurlencode applied).
 * </output>
 *
 * @deprecated since Extbase 1.4.0; will be removed in Extbase 1.6.0. Please use the <f:format.*> ViewHelpers instead.
 */
class Tx_Fluid_ViewHelpers_EscapeViewHelper extends Tx_Fluid_ViewHelpers_Format_AbstractEncodingViewHelper {

	/**
	 * Escapes special characters with their escaped counterparts as needed.
	 *
	 * @param string $value
	 * @param string $type The type, one of html, entities, url
	 * @param string $encoding
	 * @return string the altered string.
	 */
	public function render($value = NULL, $type = 'html', $encoding = NULL) {
		t3lib_div::logDeprecatedFunction('<f:escape> is deprecated. Please use the <f:format.*> ViewHelpers instead.');
		if ($value === NULL) {
			$value = $this->renderChildren();
		}

		if (!is_string($value)) {
			return $value;
		}

		if ($encoding === NULL) {
			$encoding = $this->resolveDefaultEncoding();
		}

		switch ($type) {
			case 'html':
				return htmlspecialchars($value, ENT_COMPAT, $encoding);
			break;
			case 'entities':
				return htmlentities($value, ENT_COMPAT, $encoding);
			break;
			case 'url':
				return rawurlencode($value);
			default:
				return $value;
			break;
		}
	}
}
?>