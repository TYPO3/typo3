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
class Tx_Extbase_MVC_View_Helper_URIHelper extends Tx_Extbase_MVC_View_Helper_AbstractHelper implements t3lib_Singleton {

	/**
	 * An instance of tslib_cObj
	 *
	 * @var	tslib_cObj
	 */
	protected $contentObject;

	/**
	 * Constructs this URI Helper
	 */
	public function __construct() {
		$this->contentObject = t3lib_div::makeInstance('tslib_cObj');
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
	 * @param array $options Further options (usually options of the typolink configuration)
	 * @param integer $pageType type of the target page. See typolink.parameter
	 * @return string the typolink URI
	 */
	public function URIFor($pageUid, $actionName = NULL, $arguments = array(), $controllerName = NULL, $extensionName = NULL, $pluginName = NULL, array $options = array(), $pageType = 0) {
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
		}
		if ($extensionName === NULL) {
			$extensionName = $this->request->getControllerExtensionName();
		}
		if ($pluginName === NULL) {
			$pluginName = $this->request->getPluginKey();
		}
		$argumentPrefix = strtolower('tx_' . $extensionName . '_' . $pluginName);
		$prefixedArguments = (count($arguments) > 0) ? array($argumentPrefix => $arguments) : array();

		return $this->typolinkURI($pageUid, $prefixedArguments, $options, $pageType);
	}

	/**
	 * Get an URI from typolink_URL
	 * 
	 * @param integer $pageUid uid of the target page. If not set, the current page uid is used
	 * @param array $arguments query parameters
	 * @param array $options Further options (usually options of the typolink configuration)
	 * @param integer $pageType type of the target page. See typolink.parameter
	 * @return The URI
	 */
	public function typolinkURI($pageUid, array $arguments = array(), array $options = array(), $pageType = 0) {
		$typolinkConfiguration = array();
		$typolinkConfiguration['parameter'] = $pageUid;
		if ($pageType !== 0) {
			$typolinkConfiguration['parameter'] .= ',' . $pageType;
		}
		$typolinkConfiguration['additionalParams'] = '';
		if (count($arguments) > 0) {
			$typolinkConfiguration['additionalParams'] .= '&' . http_build_query($arguments, NULL, '&');
			if (!isset($options['no_cache'])) {
				$typolinkConfiguration['useCacheHash'] = 1;
			}
		}
		if (isset($options['additionalParams'])) {
			$typolinkConfiguration['additionalParams'] .= $this->contentObject->stdWrap($options['additionalParams'], isset($options['additionalParams.']) ? $options['additionalParams.'] : array());
			unset($options['additionalParams']);
			unset($options['additionalParams.']);
		}
		$typolinkConfiguration = t3lib_div::array_merge_recursive_overrule($typolinkConfiguration, $options);

		return $this->contentObject->typoLink_URL($typolinkConfiguration);
	}
}
?>

