<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Sebastian Kurfuerst <sebastian@typo3.org>
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
 * @author	Sebastian Kurfuerst <sebastian@typo3.org>
 * @author	Stefan Galinski <stefan.galinski@gmail.com>
 * @package	TYPO3
 */
class t3lib_ExtJs_ExtDirectAPI {
	/**
	 * Parses the ExtDirect configuration array "$GLOBALS['TYPO3_CONF_VARS']['BE']['ExtDirect']"
	 * and feeds the given typo3ajax instance with the resulting informations. The get parameter
	 * "namespace" will be used to filter the configuration.
	 *
	 * This method makes usage of the reflection mechanism to fetch the methods inside the
	 * defined classes together with their amount of parameters. This informations are building
	 * the API and are required by ExtDirect.
	 *
	 * @param array $ajaxParams ajax parameters
	 * @param TYPO3AJAX $ajaxObj typo3ajax instance
	 * @return void
	 */
	public function getAPI($ajaxParams, TYPO3AJAX $ajaxObj) {
		$filterNamespace = t3lib_div::_GET('namespace');

		$javascriptNamespaces = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['BE']['ExtDirect'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['BE']['ExtDirect'] as $javascriptName => $className) {
				$splittedJavascriptName = explode('.', $javascriptName);
				$javascriptObjectName = array_pop($splittedJavascriptName);
				$javascriptNamespace = implode('.', $splittedJavascriptName);

				// only items inside the wanted namespace
				if (strpos($javascriptNamespace, $filterNamespace) !== 0) {
					continue;
				}

				if (!isset($javascriptNamespaces[$javascriptNamespace])) {
					$javascriptNamespaces[$javascriptNamespace] = array(
					    'url' => 'ajax.php?ajaxID=ExtDirect::route&namespace=' . rawurlencode($javascriptNamespace),
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

					$javascriptNamespaces[$javascriptNamespace]['actions'][$javascriptObjectName][] = array(
						'name' => $methodName,
						'len' => $numberOfParameters
					);	
				}
			}
		}

		if (count($javascriptNamespaces)) {
			$setup = '
				if (typeof Ext.app.ExtDirectAPI != "object") {
					Ext.app.ExtDirectAPI = {};
				}
			';

			$ajaxObj->setContent($javascriptNamespaces);
			$ajaxObj->setContentFormat('javascript');
			$ajaxObj->setJavascriptCallbackWrap($setup . 'Ext.app.ExtDirectAPI = Object.extend(Ext.app.ExtDirectAPI, |);');
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_extjs_extdirectapi.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_extjs_extdirectapi.php']);
}

?>
