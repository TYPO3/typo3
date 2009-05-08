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
 * Action link view helper.
 * Used to create links to actions of the current or other Extbase controllers.
 *
 * = Examples =
 *
 * <code title="Basic action link">
 * <f:actionlink actionName="foo">some link</f:actionlink>
 * </code>
 * 
 * Output:
 * <a href="index.php?id=123&amp;tx_myextension_plugin[action]=foo&amp;tx_myextension_plugin[controller]=Default&amp;cHash=123456">
 * (depending on your typolink-settings, current page and encryption key)
 * 
 * <code title="More parameters">
 * <f:actionlink actionName="foo" pageUid="2" pageType="1" controllerName="bar" extensionName="OtherExtension" pluginName="somePlugin" options="{no_cache: 1, additionalParams: '&print=1'}">some link</f:actionlink>
 * </code>
 *
 * Output:
 * <a href="index.php?id=123&type=1&no_cache=1&tx_otherextension_someplugin[action]=foo&tx_otherextension_someplugin[controller]=bar&print=1">some link</a>
 * (depending on your typolink-settings)
 * 
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
	 * @param string $controllerName Target controller. If NULL current controllerName is used
	 * @param string $extensionName Target Extension Name (without "tx_" prefix and no underscores). If NULL the current extension name is used
	 * @param string $pluginName Target plugin. If empty, the current plugin name is used
	 * @param array $options typolink options
	 * @param integer $pageUid Target page uid. If NULL, the current page uid is used
	 * @param integer $pageType type of the target page. See typolink.parameter
	 * @return string Rendered link
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($actionName, array $arguments = array(), $controllerName = NULL, $extensionName = NULL, $pluginName = NULL, array $options = array(), $pageUid = NULL, $pageType = 0) {
		if ($controllerName === NULL) {
			$request = $this->variableContainer->get('view')->getRequest();
			$controllerName = $request->getControllerName();
		}
		if ($pageUid === NULL) {
			$pageUid = $GLOBALS['TSFE']->id;
		}
		$uriHelper = $this->variableContainer->get('view')->getViewHelper('Tx_Extbase_MVC_View_Helper_URIHelper');
		$uri = $uriHelper->URIFor($pageUid, $actionName, $arguments, $controllerName, $extensionName, $pluginName, $options, $pageType);
		$this->tag->addAttribute('href', $uri);
		$this->tag->setContent($this->renderChildren(), FALSE);

		return $this->tag->render();
	}
}
?>