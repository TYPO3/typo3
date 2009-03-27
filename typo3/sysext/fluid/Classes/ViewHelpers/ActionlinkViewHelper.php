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
 * @package
 * @subpackage
 * @version $Id:$
 */
class Tx_Fluid_ViewHelpers_ActionlinkViewHelper extends Tx_Fluid_ViewHelpers_TypolinkViewHelper {

	/**
	 * Render.
	 *
	 * @param string $page Target page
	 * @param string $action Target action
	 * @param string $controller Target controller
	 * @param string $extensionKey Target Extension Key
	 * @param string $anchor Anchor
	 * @param array $arguments Arguments
	 * @return string Rendered string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function render($page = '', $action = '', $controller = '', $extensionKey = '', $anchor = '', $arguments = array()) {
		$view = $this->variableContainer->get('view');

		$prefixedExtensionKey = 'tx_' . strtolower($view->getRequest()->getExtensionName()) . '_' . strtolower($view->getRequest()->getControllerName());

		$arguments['action'] = $action;
		$prefixedArguments = array();
		foreach ($arguments as $argumentName => $argumentValue) {
			$key = $prefixedExtensionKey . '[' . $argumentName . ']';
			$prefixedArguments[$key] = $argumentValue;
		}

		return parent::render($page, $anchor, TRUE, $prefixedArguments);
	}
}
?>