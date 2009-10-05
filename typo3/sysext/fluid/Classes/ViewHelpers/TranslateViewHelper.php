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
 * Translate a key from locallang. The files are loaded from the folder
 * "Resources/Private/Language/".
 *
 * @package TYPO3
 * @subpackage Fluid
 * @version $Id:$
 */
class Tx_Fluid_ViewHelpers_TranslateViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Translate a given key or use the tag body as default.
	 *
	 * @param string $key The locallang key
	 * @param boolean $htmlEscape TRUE if the result should be htmlescaped
	 * @return string The translated key or tag body if key doesn't exist
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($key, $htmlEscape = TRUE) {
		$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		$value = Tx_Extbase_Utility_Localization::translate($key, $extensionName);
		if ($value === NULL) {
			$value = $this->renderChildren();
		} elseif ($htmlEscape) {
			$value = htmlspecialchars($value);
		}
		return $value;
	}
}

?>
