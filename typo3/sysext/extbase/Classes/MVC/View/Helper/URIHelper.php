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
class Tx_ExtBase_MVC_View_Helper_URIHelper implements t3lib_Singleton {
	/**
	 * An instance of tslib_cObj
	 *
	 * @var	tslib_cObj
	 */
	protected $contentObject;

	public function __construct(array $arguments = array()) {
		$this->contentObject = t3lib_div::makeInstance('tslib_cObj');
	}

	/**
	 * Get an URI for a controller / action / extension on a specific page within the scope
	 * of the current request.
	 * @return The URI
	 */
	public function URIFor($request, $actionName = '', $arguments = array(), $controllerName = '', $page = '', $extensionKey = '', $anchor = '', $useCacheHash = TRUE) {
		$prefixedExtensionKey = 'tx_' . strtolower($request->getExtensionName()) . '_' . strtolower($request->getPluginKey());

		$arguments['action'] = $actionName;
		$arguments['controller'] = ($controllerName !== '') ? $controllerName : $request->getControllerName();
		$prefixedArguments = array();
		foreach ($arguments as $argumentName => $argumentValue) {
			$key = $prefixedExtensionKey . '[' . $argumentName . ']';
			$prefixedArguments[$key] = $argumentValue;
		}

		return $this->typolinkURI($page, $anchor, $useCacheHash, $prefixedArguments);
	}

	/**
	 * Get an URI from typolink
	 * @return The URI
	 */
	public function typolinkURI($page = '', $anchor = '', $useCacheHash = TRUE, $arguments = array()) {
		if ($page === '') {
			$page = $GLOBALS['TSFE']->id;
		}

		$typolinkConfiguration = array(
			'parameter' => $page
		);

		if (count($arguments) > 0) {
			foreach ($arguments as $argumentNameSpace => $argument) {
				$typolinkConfiguration['additionalParams'] .= '&' . $argumentNameSpace . '=' . rawurlencode($argument);
			}
		}
		if ($anchor) {
			$typolinkConfiguration['section'] = $anchor;
		}
		if ($useCacheHash) {
			$typolinkConfiguration['useCacheHash'] = 1;
		} else {
			$typolinkConfiguration['useCacheHash'] = 0;
		}

		return $this->contentObject->typoLink_URL($typolinkConfiguration);
	}
}
?>