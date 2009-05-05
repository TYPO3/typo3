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
// TODO Rename file name URIHelper to URIHelper
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
	public function __construct(array $arguments = array()) {
		$this->contentObject = t3lib_div::makeInstance('tslib_cObj');
	}

	/**
	 * Creates an URI by making use of the typolink mechanism.
	 *
	 * @param string $actionName Name of the action to be called
	 * @param array $arguments Additional arguments 
	 * @param string $controllerName Name of the target controller. If not set, current controller is used
	 * @param string $extensionName Name of the target extension. If not set, current extension is used
	 * @param array $options Further options (usually options of the typolink configuration)
	 * @return string the HTML code for the generated link
	 */
	// TODO Check the order of the parameters
	public function URIFor($actionName, $arguments = array(), $controllerName = NULL, $extensionName = NULL, $pageUid = NULL, array $options = array()) {
		$arguments['action'] = $actionName;
		$arguments['controller'] = ($controllerName !== NULL) ? $controllerName : $this->request->getControllerName();
		$prefixedExtensionKey = 'tx_' . strtolower($this->request->getControllerExtensionName()) . '_' . strtolower($this->request->getPluginKey());
		$prefixedArguments = array();
		foreach ($arguments as $argumentName => $argumentValue) {
			$key = $prefixedExtensionKey . '[' . $argumentName . ']';
			$prefixedArguments[$key] = $argumentValue;
		}
		$URIString = $this->typolinkURI($pageUid, $options, $prefixedArguments);
		return $URIString;
	}

	/**
	 * Get an URI from typolink_URL
	 * 
	 * @return The URI
	 */
	public function typolinkURI($parameter = NULL, array $options = array(), array $arguments = array()) {
		$typolinkConfiguration = array();
		$typolinkConfiguration = t3lib_div::array_merge_recursive_overrule($typolinkConfiguration, $options, 0, FALSE);
		if (($parameter === NULL) && ($options['parameter'] === NULL)) {
			$typolinkConfiguration['parameter'] = $GLOBALS['TSFE']->id;
		}
		if (count($arguments) > 0) {
			$typolinkConfiguration['additionalParams'] = '';
			foreach ($arguments as $argumentNameSpace => $argument) {
				$typolinkConfiguration['additionalParams'] .= '&' . $argumentNameSpace . '=' . rawurlencode($argument);
			}
		}
		return $this->contentObject->typoLink_URL($typolinkConfiguration);
	}
}
?>