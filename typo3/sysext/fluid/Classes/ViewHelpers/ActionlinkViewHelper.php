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
class Tx_Fluid_ViewHelpers_ActionlinkViewHelper extends Tx_Fluid_Core_TagBasedViewHelper {
	/**
	 * @var	Tx_ExtBase_MVC_Web_URIHelper
	 */
	protected $URIHelper;

	public function __construct(array $arguments = array()) {
		$this->URIHelper = t3lib_div::makeInstance('Tx_ExtBase_MVC_Web_URIHelper');
	}

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
		// TODO CH: Implement some logic wether to set useCacheHash
		$uri = $this->URIHelper->URIFor($view->getRequest(), $action, $arguments, $controller, $page, $extensionKey, $anchor, TRUE);
		return '<a href="' . $uri . '" ' . $this->renderTagAttributes() . '>' . $this->renderChildren() . '</a>';
	}
}
?>