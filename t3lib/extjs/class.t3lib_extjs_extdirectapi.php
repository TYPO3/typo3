<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Sebastian Kurfürst <sebastian@typo3.org>
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
 * @author	Sebastian Kurfürst <sebastian@typo3.org>
 * @author	Stefan Galinski <stefan.galinski@gmail.com>
 * @package	TYPO3
 */
class t3lib_extjs_ExtDirectApi {
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
	 * @param array $ajaxParams ajax parameters
	 * @param TYPO3AJAX $ajaxObj typo3ajax instance
	 * @return void
	 */
	public function getAPI($ajaxParams, TYPO3AJAX $ajaxObj) {
		$ajaxObj->setContent(array());
	}

	/**
	 * Get the API for a given nameapace
	 *
	 * @throws InvalidArgumentException
	 * @param  array $filterNamespaces
	 * @return string
	 */
	public function getApiPhp(array $filterNamespaces) {
		$javascriptNamespaces = $this->getExtDirectApi($filterNamespaces);
			// return the generated javascript API configuration
		if (count($javascriptNamespaces)) {
			return '
				if (!Ext.isObject(Ext.app.ExtDirectAPI)) {
					Ext.app.ExtDirectAPI = {};
				}
				Ext.apply(Ext.app.ExtDirectAPI, ' .
					json_encode($javascriptNamespaces) . ');
			';
		} else {
			$errorMessage = $this->getNamespaceError($filterNamespaces);
			throw new InvalidArgumentException(
				$errorMessage,
				1297645190
			);
		}
	}


	/**
	 * Generates the API that is configured inside the ExtDirect configuration
	 * array "$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect']".
	 *
	 * @param array $filerNamespace namespace that should be loaded like array('TYPO3.Backend')
	 * @return array javascript API configuration
	 */
	protected function generateAPI(array $filterNamespaces) {
		$javascriptNamespaces = array();
		if (is_array($this->settings)) {
			foreach ($this->settings as $javascriptName => $className) {
				$splittedJavascriptName = explode('.', $javascriptName);
				$javascriptObjectName = array_pop($splittedJavascriptName);
				$javascriptNamespace = implode('.', $splittedJavascriptName);

					// only items inside the wanted namespace
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

				$serverObject = t3lib_div::getUserObj($className, FALSE);
				$javascriptNamespaces[$javascriptNamespace]['actions'][$javascriptObjectName] = array();
				foreach (get_class_methods($serverObject) as $methodName) {
					$reflectionMethod = new ReflectionMethod($serverObject, $methodName);
					$numberOfParameters = $reflectionMethod->getNumberOfParameters();
					$docHeader = $reflectionMethod->getDocComment();
					$formHandler = (strpos($docHeader, '@formHandler') !== FALSE);

					$javascriptNamespaces[$javascriptNamespace]['actions'][$javascriptObjectName][] = array(
						'name' => $methodName,
						'len' => $numberOfParameters,
						'formHandler' => $formHandler
					);
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
			$url = t3lib_div::locationHeaderUrl('?eID=ExtDirect&action=route&namespace=');
		} else {
			$url = t3lib_div::locationHeaderUrl('ajax.php?ajaxID=ExtDirect::route&namespace=');
		}
		$url .= rawurlencode($namespace);

		return $url;
	}

	/**
	 * Generates the API or reads it from cache
	 *
	 * @param  array $filterNamespaces
	 * @param bool $checkGetParam
	 * @return string $javascriptNamespaces
	 */
	protected function getExtDirectApi(array $filterNamespaces) {
			// Check GET-parameter no_cache and extCache setting
		$noCache = isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['extCache']) && (
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['extCache'] === 0 ||
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['extCache'] === '0'
		);
		$noCache = t3lib_div::_GET('no_cache') ? TRUE : $noCache;

			// look up into the cache
		$cacheIdentifier = 'ExtDirectApi';
		$cacheHash = md5($cacheIdentifier . implode(',', $filterNamespaces) . t3lib_div::getIndpEnv('TYPO3_SSL') .
			 serialize($this->settings) . TYPO3_MODE . t3lib_div::getIndpEnv('HTTP_HOST'));

			// with no_cache always generate the javascript content
		$cacheContent = $noCache ? '' : t3lib_pageSelect::getHash($cacheHash);

			// generate the javascript content if it wasn't found inside the cache and cache it!
		if (!$cacheContent) {
			$javascriptNamespaces = $this->generateAPI($filterNamespaces);
			if (count($javascriptNamespaces)) {
				t3lib_pageSelect::storeHash(
					$cacheHash,
					serialize($javascriptNamespaces),
					$cacheIdentifier
				);
			}
		} else {
			$javascriptNamespaces = unserialize($cacheContent);
		}

		return $javascriptNamespaces;
	}

	/**
	 * Generates the error message
	 *
	 * @param  array $filterNamespaces
	 * @return string $errorMessage
	 */
	protected function getNamespaceError(array $filterNamespaces) {
		if (count($filterNamespaces)) {
				// namespace error
			$errorMessage = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:ExtDirect.namespaceError'),
									__CLASS__, implode(',', $filterNamespaces)
			);
		}
		else {
				// no namespace given
			$errorMessage = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:ExtDirect.noNamespace'),
									__CLASS__
			);
		}

		return $errorMessage;
	}

	/**
	 * Looks if the given namespace is present in $filterNamespaces
	 *
	 * @param  string $namespace
	 * @param array $filterNamespaces
	 * @return bool
	 */
	protected function findNamespace($namespace, array $filterNamespaces) {
		if ($filterNamespaces === array('TYPO3')) {
			return TRUE;
		}
		$found = FALSE;
		foreach ($filterNamespaces as $filter) {
			if (t3lib_div::isFirstPartOfStr($filter, $namespace)) {
				$found = TRUE;
				break;
			}
		}
		return $found;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/extjs/class.t3lib_extjs_extdirectapi.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/extjs/class.t3lib_extjs_extdirectapi.php']);
}

?>