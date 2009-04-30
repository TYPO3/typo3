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

require_once(PATH_t3lib . 'interfaces/interface.t3lib_singleton.php');
require_once(PATH_tslib . 'class.tslib_content.php');

/**
 * @package
 * @subpackage
 * @version $Id:$
 */
// TODO Rename file name URIHelper to UriHelper
class Tx_Extbase_MVC_View_Helper_UriHelper extends Tx_Extbase_MVC_View_Helper_AbstractHelper implements t3lib_Singleton {

	/**
	 * An instance of tslib_cObj
	 *
	 * @var	tslib_cObj
	 */
	protected $contentObject;

	/**
	 * Constructs this URI Helper
	 */
	public function __construct(array $arguments = array()) {
		$this->contentObject = t3lib_div::makeInstance('tslib_cObj');
	}

	/**
	 * Creates an URI by making use of the typolink mechanism.
	 *
	 * @param string $actionName Name of the action to be called
	 * @param array $arguments Additional arguments
	 * @param string $controllerName Name of the target controller. If not set, current controller is used
	 * @param string $pageUid 
	 * @param array $options Further options
	 * @return string the HTML code for the generated link
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriFor($actionName, $arguments = array(), $controllerName = '', $parameter = 0, $anchor = '', $useCacheHash = TRUE) {
		$prefixedExtensionKey = 'tx_' . strtolower($this->request->getControllerExtensionKey()) . '_' . strtolower($this->request->getPluginKey());

		$arguments['action'] = $actionName;
		$arguments['controller'] = ($controllerName !== '') ? $controllerName : $this->request->getControllerName();
		$prefixedArguments = array();
		foreach ($arguments as $argumentName => $argumentValue) {
			$key = $prefixedExtensionKey . '[' . $argumentName . ']';
			$prefixedArguments[$key] = $argumentValue;
		}

		return $this->typolinkUri($pageUid, $anchor, $useCacheHash, $prefixedArguments);
	}

	/**
	 * Get an URI from typolink
	 * @return The URI
	 */
	public function typolinkUri($pageUid = 0, $anchor = '', $useCacheHash = TRUE, $arguments = array()) {
		if ($pageUid === 0) {
			$pageUid = $GLOBALS['TSFE']->id;
		}

		$typolinkConfiguration = array(
			'parameter' => $pageUid
		);

		if (count($arguments) > 0) {
			foreach ($arguments as $argumentNameSpace => $argument) {
				$typolinkConfiguration['additionalParams'] .= '&' . $argumentNameSpace . '=' . rawurlencode($argument);
			}
		}
		if (!empty($anchor)) {
			$typolinkConfiguration['section'] = $anchor;
		}
		if ($useCacheHash === TRUE) {
			$typolinkConfiguration['useCacheHash'] = 1;
		} else {
			$typolinkConfiguration['useCacheHash'] = 0;
		}

		return $this->contentObject->typoLink_URL($typolinkConfiguration);
	}
}
?>