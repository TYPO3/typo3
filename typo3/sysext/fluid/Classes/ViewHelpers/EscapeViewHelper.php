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
 * The EscapeViewHelper is used to escape variable content in various ways. By
 * default HTML is the target.
 *
 * = Examples =
 *
 * <code title="HTML">
 * <f:escape>{text}</f:escape>
 * </code>
 *
 * Output:
 * Text with & " ' < > * replaced by HTML entities (htmlspecialchars applied).
 *
 * <code title="Entities">
 * <f:escape type="entities">{text}</f:escape>
 * </code>
 *
 * Output:
 * Text with all possible chars replaced by HTML entities (htmlentities applied).
 *
 * <code title="URL">
 * <f:escape type="url">{text}</f:escape>
 * </code>
 *
 * Output:
 * Text encoded for URL use (rawurlencode applied).
 *
 * @version $Id: EscapeViewHelper.php 3751 2010-01-22 15:56:47Z k-fish $
 * @package Fluid
 * @subpackage ViewHelpers
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_EscapeViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Escapes special characters with their escaped counterparts as needed.
	 *
	 * @param string $value
	 * @param string $type The type, one of html, entities, url
	 * @param string $encoding
	 * @return string the altered string.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function render($value = NULL, $type = 'html', $encoding = NULL) {
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

	/**
	 * Resolve the default encoding. If none is set in Frontend or Backend, uses UTF-8.
	 * 
	 * @return string the encoding
	 */
	protected function resolveDefaultEncoding() {
		if (TYPO3_MODE === 'BE') {
			$encoding = strtoupper($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);

			if ($encoding === NULL) {
				$encoding = 'UTF-8';
			}
			return $encoding;
		} else {
			return strtoupper($GLOBALS['TSFE']->renderCharset);
		}
	}
}
?>