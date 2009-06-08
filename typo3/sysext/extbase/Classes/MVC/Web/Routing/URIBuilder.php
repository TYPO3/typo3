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
 * An URI Builder
 *
 * @package Extbase
 * @subpackage MVC
 * @version $Id$
 * @internal
 */
class Tx_Extbase_MVC_Web_Routing_URIBuilder {

	/**
	 * An instance of tslib_cObj
	 *
	 * @var	tslib_cObj
	 */
	protected $contentObject;

	/**
	 * @var Tx_Extbase_MVC_Request
	 */
	protected $request;

	/**
	 * Constructs this URI Helper
	 */
	public function __construct() {
		$this->contentObject = t3lib_div::makeInstance('tslib_cObj');
	}

	/**
	 * Sets the current request
	 *
	 * @param Tx_Extbase_MVC_Request $request
	 * @return void
	 */
	public function setRequest(Tx_Extbase_MVC_Request $request) {
		$this->request = $request;
	}

	/**
	 * Creates an URI by making use of the typolink mechanism.
	 *
	 * @param integer $pageUid uid of the target page
	 * @param string $actionName Name of the action to be called
	 * @param array $arguments Additional query parameters, will be "namespaced"
	 * @param string $controllerName Name of the target controller
	 * @param string $extensionName Name of the target extension, without underscores. If NULL current ExtensionName is used.
	 * @param string $pluginName Name of the target plugin.  If NULL current PluginName is used.
	 * @param integer $pageType type of the target page. See typolink.parameter
	 * @param boolean $noCache if TRUE, then no_cache=1 is appended to URI
	 * @param boolean $useCacheHash by default TRUE; if FALSE, disable the cHash
	 * @param string $section If specified, adds a given HTML anchor to the URI (#...)
	 * @param boolean $linkAccessRestrictedPages If TRUE, generates links for pages where the user does not have permission to see it
	 * @param string $additionalParams An additional params query string which will be appended to the URI
	 * @return string the typolink URI
	 * @internal
	 */
	public function URIFor($pageUid = NULL, $actionName = NULL, $arguments = array(), $controllerName = NULL, $extensionName = NULL, $pluginName = NULL, $pageType = 0, $noCache = FALSE, $useCacheHash = TRUE, $section = '', $linkAccessRestrictedPages = FALSE, $additionalParams = '') {
		if (is_array($arguments)) {
			foreach ($arguments as $argumentKey => $argumentValue) {
				if ($argumentValue instanceof Tx_Extbase_DomainObject_AbstractEntity) {
					$arguments[$argumentKey] = array('uid' => $argumentValue->getUid());
				}
			}
		}
		if ($actionName !== NULL) {
			$arguments['action'] = $actionName;
		}
		if ($controllerName !== NULL) {
			$arguments['controller'] = $controllerName;
		} else {
			$arguments['controller'] = $this->request->getControllerName();
		}
		if ($extensionName === NULL) {
			$extensionName = $this->request->getControllerExtensionName();
		}
		if ($pluginName === NULL) {
			$pluginName = $this->request->getPluginName();
		}
		$argumentPrefix = strtolower('tx_' . $extensionName . '_' . $pluginName);
		$prefixedArguments = (count($arguments) > 0) ? array($argumentPrefix => $arguments) : array();

		return $this->typolinkURI($pageUid, $prefixedArguments, $pageType, $noCache, $useCacheHash, $section, $linkAccessRestrictedPages, $additionalParams);
	}

	/**
	 * Get an URI from typolink_URL
	 *
	 * @param integer $pageUid uid of the target page
	 * @param array $arguments Additional query parameters, will be "namespaced"
	 * @param integer $pageType type of the target page. See typolink.parameter
	 * @param boolean $noCache if TRUE, then no_cache=1 is appended to URI
	 * @param boolean $useCacheHash by default TRUE; if FALSE, disable the cHash
	 * @param string $section If specified, adds a given HTML anchor to the URI (#...)
	 * @param boolean $linkAccessRestrictedPages If TRUE, generates links for pages where the user does not have permission to see it
	 * @param string $additionalParams An additional params query string which will be appended to the URI
	 * @return The URI
	 * @internal
	 */
	public function typolinkURI($pageUid = NULL, array $arguments = array(), $pageType = 0, $noCache = FALSE, $useCacheHash = TRUE, $section = '', $linkAccessRestrictedPages = FALSE, $additionalParams = '') {
		if ($pageUid === NULL) {
			$pageUid = $GLOBALS['TSFE']->id;
		}

		$typolinkConfiguration = array();
		$typolinkConfiguration['parameter'] = $pageUid;
		if ($pageType !== 0) {
			$typolinkConfiguration['parameter'] .= ',' . $pageType;
		}
		$typolinkConfiguration['additionalParams'] = '';
		if (count($arguments) > 0) {
			$typolinkConfiguration['additionalParams'] .= '&' . http_build_query($arguments, NULL, '&');
		}

		if ($noCache) {
			$typolinkConfiguration['no_cache'] = 1;
			// TODO: stdwrap
		}

		if ($useCacheHash) {
			$typolinkConfiguration['useCacheHash'] = 1;
		}

		if ($section !== '') {
			$typolinkConfiguration['section'] = $section;
			// TODO: stdwrap
		}

		if ($linkAccessRestrictedPages === TRUE) {
			$typolinkConfiguration['linkAccessRestrictedPages'] = $linkAccessRestrictedPages;
		}

		if (isset($options['additionalParams'])) {
			// TODO: Stdwrap
			// TODO FIX THIS: $typolinkConfiguration['additionalParams'] .= $this->contentObject->stdWrap($options['additionalParams'], isset($options['additionalParams.']) ? $options['additionalParams.'] : array());
			$typolinkConfiguration['additionalParams'] .= $additionalParams;
		}

		return $this->contentObject->typoLink_URL($typolinkConfiguration);
	}
}
?>