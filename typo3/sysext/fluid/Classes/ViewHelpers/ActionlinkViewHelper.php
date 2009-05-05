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
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * @param string $actionName Target action
	 * @param array $arguments Arguments
	 * @param string $controllerName Target controller
	 * @param string $prefixedExtensionKey Target Extension Key
	 * @param integer $pageUid Target page uid
	 * @param array $options typolink options
	 * @return string Rendered string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($actionName = NULL, array $arguments = array(), $controllerName = NULL, $prefixedExtensionKey = NULL, $pageUid = NULL, array $options = array()) {
		// TODO CH: Implement some logic wether to set useCacheHash
		$uriHelper = $this->variableContainer->get('view')->getViewHelper('Tx_Extbase_MVC_View_Helper_URIHelper');
		$uri = $uriHelper->URIFor($actionName, $arguments, $controllerName, $prefixedExtensionKey, $pageUid, $options);
		$this->tag->addAttribute('href', $uri);
		$this->tag->setContent($this->renderChildren(), FALSE);

		return $this->tag->render();
	}
}
?>