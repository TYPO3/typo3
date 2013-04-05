<?php
namespace TYPO3\CMS\Core\ExtDirect;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Sebastian Kurfürst <sebastian@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Ext Direct API Generator
 *
 * @author Sebastian Kurfürst <sebastian@typo3.org>
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class ExtDirectApi {

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Constructs this object.
	 */
	public function __construct() {
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'])) {
			$this->settings = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'];
		}
	}

	/**
	 * Parses the ExtDirect configuration array "$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect']"
	 * and feeds the given typo3ajax instance with the resulting information. The get parameter
	 * "namespace" will be used to filter the configuration.
	 *
	 * This method makes usage of the reflection mechanism to fetch the methods inside the
	 * defined classes together with their amount of parameters. This information are building
	 * the API and are required by ExtDirect. The result is cached to improve the overall
	 * performance.
	 *
	 * @param array $ajaxParams Ajax parameters
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj typo3ajax instance
	 * @return void
	 */
	public function getAPI($ajaxParams, \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj) {
		$ajaxObj->setContent(array());
	}

	/**
	 * Get the API for a given nameapace
	 *
	 * @param array $filterNamespaces
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function getApiPhp(array $filterNamespaces) {
		$javascriptNamespaces = $this->getExtDirectApi($filterNamespaces);
		// Return the generated javascript API configuration
		if (count($javascriptNamespaces)) {
			return '
				if (!Ext.isObject(Ext.app.ExtDirectAPI)) {
					Ext.app.ExtDirectAPI = {};
				}
				Ext.apply(Ext.app.ExtDirectAPI, ' . json_encode($javascriptNamespaces) . ');
			';
		} else {
			$errorMessage = $this->getNamespaceError($filterNamespaces);
			throw new \InvalidArgumentException($errorMessage, 1297645190);
		}
	}

	/**
	 * Generates the API that is configured inside the ExtDirect configuration
	 * array "$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect']".
	 *
	 * @param array $filerNamespace Namespace that should be loaded like array('TYPO3.Backend')
	 * @return array Javascript API configuration
	 */
	protected function generateAPI(array $filterNamespaces) {
		$javascriptNamespaces = array();
		if (is_array($this->settings)) {
			foreach ($this->settings as $javascriptName => $configuration) {
				$splittedJavascriptName = explode('.', $javascriptName);
				$javascriptObjectName = array_pop($splittedJavascriptName);
				$javascriptNamespace = implode('.', $splittedJavascriptName);
				// Only items inside the wanted namespace
				if (!$this->findNamespace($javascriptNamespace, $filterNamespaces)) {
					continue;
				}
				if (!isset($javascriptNamespaces[$javascriptNamespace])) {
					$javascriptNamespaces[$javascriptNamespace] = array(
						'url' => $this->getRoutingUrl($javascriptNamespace),
						'type' => 'remoting',
						'actions' => array(),
						'namespace' => $javascriptNamespace
					);
				}
				if (is_array($configuration)) {
					$className = $configuration['callbackClass'];
					$serverObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($className, FALSE);
					$javascriptNamespaces[$javascriptNamespace]['actions'][$javascriptObjectName] = array();
					foreach (get_class_methods($serverObject) as $methodName) {
						$reflectionMethod = new \ReflectionMethod($serverObject, $methodName);
						$numberOfParameters = $reflectionMethod->getNumberOfParameters();
						$docHeader = $reflectionMethod->getDocComment();
						$formHandler = strpos($docHeader, '@formHandler') !== FALSE;
						$javascriptNamespaces[$javascriptNamespace]['actions'][$javascriptObjectName][] = array(
							'name' => $methodName,
							'len' => $numberOfParameters,
							'formHandler' => $formHandler
						);
					}
				}
			}
		}
		return $javascriptNamespaces;
	}

	/**
	 * Returns the convenient path for the routing Urls based on the TYPO3 mode.
	 *
	 * @param string $namespace
	 * @return string
	 */
	public function getRoutingUrl($namespace) {
		$url = '';
		if (TYPO3_MODE === 'FE') {
			$url = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl('?eID=ExtDirect&action=route&namespace=');
		} else {
			$url = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . 'ajax.php?ajaxID=ExtDirect::route&namespace=');
		}
		$url .= rawurlencode($namespace);
		return $url;
	}

	/**
	 * Generates the API or reads it from cache
	 *
	 * @param array $filterNamespaces
	 * @param boolean $checkGetParam
	 * @return string $javascriptNamespaces
	 */
	protected function getExtDirectApi(array $filterNamespaces) {
		$noCache = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('no_cache') ? TRUE : FALSE;
		// Look up into the cache
		$cacheIdentifier = 'ExtDirectApi';
		$cacheHash = md5($cacheIdentifier . implode(',', $filterNamespaces) . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL') . serialize($this->settings) . TYPO3_MODE . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST'));
		// With no_cache always generate the javascript content
		$cacheContent = $noCache ? '' : \TYPO3\CMS\Frontend\Page\PageRepository::getHash($cacheHash);
		// Generate the javascript content if it wasn't found inside the cache and cache it!
		if (!$cacheContent) {
			$javascriptNamespaces = $this->generateAPI($filterNamespaces);
			if (count($javascriptNamespaces)) {
				\TYPO3\CMS\Frontend\Page\PageRepository::storeHash($cacheHash, serialize($javascriptNamespaces), $cacheIdentifier);
			}
		} else {
			$javascriptNamespaces = unserialize($cacheContent);
		}
		return $javascriptNamespaces;
	}

	/**
	 * Generates the error message
	 *
	 * @param array $filterNamespaces
	 * @return string $errorMessage
	 */
	protected function getNamespaceError(array $filterNamespaces) {
		if (count($filterNamespaces)) {
			// Namespace error
			$errorMessage = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:ExtDirect.namespaceError'), __CLASS__, implode(',', $filterNamespaces));
		} else {
			// No namespace given
			$errorMessage = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:ExtDirect.noNamespace'), __CLASS__);
		}
		return $errorMessage;
	}

	/**
	 * Looks if the given namespace is present in $filterNamespaces
	 *
	 * @param string $namespace
	 * @param array $filterNamespaces
	 * @return boolean
	 */
	protected function findNamespace($namespace, array $filterNamespaces) {
		if ($filterNamespaces === array('TYPO3')) {
			return TRUE;
		}
		$found = FALSE;
		foreach ($filterNamespaces as $filter) {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($filter, $namespace)) {
				$found = TRUE;
				break;
			}
		}
		return $found;
	}

}


?>
