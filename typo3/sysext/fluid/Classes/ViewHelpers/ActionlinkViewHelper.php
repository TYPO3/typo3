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
	 * @var	Tx_Extbase_MVC_Web_URIHelper
	 */
	protected $URIHelper;

	public function __construct(array $arguments = array()) {
		$this->URIHelper = t3lib_div::makeInstance('Tx_Extbase_MVC_View_Helper_URIHelper');
	}

	/**
	 * Render.
	 *
	 * @param string $pageUid Target page UID
	 * @param string $action Target action name
	 * @param string $controller Target controller name
	 * @param string $extension Target extension name
	 * @param string $anchor Anchor name
	 * @param array $arguments Additional arguments
	 * @return string Rendered string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function render($page = NULL, $action = NULL, $controller = NULL, $extension = NULL, $anchor = NULL, array $arguments = array()) {
		// TODO CH: Implement some logic wether to set useCacheHash
		$uri = $this->URIHelper->URIFor($action, $arguments, $controller, $extension, $page, array('section' => $anchor, 'useCacheHash' => 0));
		return '<a href="' . $uri . '" ' . $this->renderTagAttributes() . '>' . $this->renderChildren() . '</a>';
	}
}
?>