<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010 Steffen Kamper <steffen@typo3.org>
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
 * Contains TEMPLATE class object.
 *
 * $Id: class.tslib_content.php 7905 2010-06-13 14:42:33Z ohader $
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class tslib_content_FluidTemplate extends tslib_content_Abstract {

	/**
	 * Rendering the cObject, TEMPLATE
	 *
	 * @param	array		array of TypoScript properties
	 * @return	string		Output
	 * @see substituteMarkerArrayCached()
	 */
	function TEMPLATE($conf) {
		return $this->getContentObject('TEMPLATE')->render($conf);
	}

	/**
	 * Rendering the cObject, FLUIDTEMPLATE
	 *   configuration properties are:
	 *   - file	string+stdWrap	the FLUID template file
	 *   - extbase.pluginName, extbase.controllerExtensionName,
	 *   - extbase.controllerName, extbase.controllerActionName
	 *   - layoutRootPath	filepath+stdWrap	by default,
	 *   - partialRootPath	filepath+stdWrap	the
	 *   - variables	array of cObjects, the keys are the variable names in fluid
	 *
	 * an example would be
	 * 10 = FLUIDTEMPLATE
	 * 10.file = fileadmin/templates/mytemplate.html
	 * 10.partialRootPath = fileadmin/templates/partial/
	 * 10.variables {
	 *    mylabel = TEXT
	 *    mylabel.value = Label from TypoScript coming
	 * }
	 *
	 * @param	array		array of TypoScript properties
	 * @return	string		the HTML output
	 * @author	Steffen Ritter	<info@steffen-ritter.net>
	 * @author	Benjamin Mack	<benni@typo3.org>
	 */
	public function render($conf = array()) {
			// check if the needed extensions are installed
		if (!t3lib_extMgm::isLoaded('fluid')) {
			return 'You need to install "Fluid" in order to use the FLUIDTEMPLATE content element';
		}
		if (!t3lib_extMgm::isLoaded('extbase')) {
			return 'You need to install "Extbase" in order to use the FLUIDTEMPLATE content element';
		}

			// initialize the extbase autoloader,
			// see Extbase_Dispatcher->initializeClassLoader
		if (!class_exists('Tx_Extbase_Utility_ClassLoader')) {
			require(t3lib_extmgm::extPath('extbase') . 'Classes/Utility/ClassLoader.php');

			$classLoader = new Tx_Extbase_Utility_ClassLoader();
			spl_autoload_register(array($classLoader, 'loadClass'));
		}


		/**
		 * 1. initializing configuration parameters
		 **/

			// fetch the FLUID file
		$templateFile = $GLOBALS['TSFE']->tmpl->getFileName($this->cObj->stdWrap($conf['file'], $conf['file.']));
		$templatePath = dirname($templateFile) . '/';
		$layoutRootPath = $templatePath . 'Layouts';
		$partialRootPath = $templatePath . 'Partials';

			// override the default layout path via typoscript
		if (isset($conf['layoutRootPath']) || isset($conf['layoutRootPath.'])) {
			$layoutRootPath = $this->cObj->stdWrap($conf['layoutRootPath'], $conf['layoutRootPath.']);
			$layoutRootPath = t3lib_div::getFileAbsFileName($layoutRootPath);
		}

			// override the default partials path via typoscript
		if (isset($conf['partialRootPath']) || isset($conf['partialRootPath.'])) {
			$partialRootPath = $this->cObj->stdWrap($conf['partialRootPath'], $conf['partialRootPath.']);
			$partialRootPath = t3lib_div::getFileAbsFileName($partialRootPath);
		}


			// set some default variables for initializing Extbase
		if (isset($conf['extbase.']['pluginName'])) {
			$requestPluginName = $conf['extbase.']['pluginName'];
		} else {
			$requestPluginName = 'pi1';
		}

		if (isset($conf['extbase.']['controllerExtensionName'])) {
			$requestControllerExtensionName = $conf['extbase.']['controllerExtensionName'];
		} else {
			$requestControllerExtensionName = 'cms';
		}

		if (isset($conf['extbase.']['controllerName'])) {
			$requestControllerName = $conf['extbase.']['controllerName'];
		} else {
			$requestControllerName = 'cms';
		}

		if (isset($conf['extbase.']['controllerActionName'])) {
			$requestControllerActionName = $conf['extbase.']['controllerActionName'];
		} else {
			$requestControllerActionName = 'index';
		}


		/**
		 * 2. initializing Fluid classes,
		 * first, the controller context needs to be created
		 **/
		$objectManager = t3lib_div::makeInstance('Tx_Fluid_Compatibility_ObjectManager');

			// creating a request object
		$controllerContext = $objectManager->create('Tx_Extbase_MVC_Controller_ControllerContext');
		/**
		 * @var $request Tx_Extbase_MVC_Web_Request
		 */
		$request = t3lib_div::makeInstance('Tx_Extbase_MVC_Web_Request');
		$request->setPluginName($requestPluginName);
		$request->setControllerExtensionName($requestControllerExtensionName);
		$request->setControllerName($requestControllerName);
		$request->setControllerActionName($requestControllerActionName);
		$request->setRequestURI(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'));
		$request->setBaseURI(t3lib_div::getIndpEnv('TYPO3_SITE_URL'));

		/**
		 * @var $uriBuilder Tx_Extbase_MVC_Web_Routing_UriBuilder
		 */
		$uriBuilder = t3lib_div::makeInstance('Tx_Extbase_MVC_Web_Routing_UriBuilder');
		$uriBuilder->setRequest($request);

		$controllerContext->setRequest($request);
		$controllerContext->setUriBuilder($uriBuilder);

		/**
		 * @var $view Tx_Fluid_View_TemplateView
		 */
		$view = t3lib_div::makeInstance('Tx_Fluid_View_TemplateView');
		$view->setControllerContext($controllerContext);

			// setting the paths for the template and the layouts/partials
		$view->setTemplatePathAndFilename($templateFile);
		$view->setLayoutRootPath($layoutRootPath);
		$view->setPartialRootPath($partialRootPath);

			// In FLOW3, solved through Object Lifecycle methods,
			// v4 needs to call it explicitely
		$view->initializeView();


		/**
		 * 3. variable replacement
		 */
		$reservedVariables = array('data', 'current');
			// accumulate the variables to be replaced
			// and loop them through cObjGetSingle
		$variables = (array) $conf['variables.'];
		foreach ($variables as $variableName => $cObjType) {
			if(!in_array($variableName, $reservedVariables)) {
				if (!is_array($cObjType)) {
					$view->assign($variableName, $this->cObj->cObjGetSingle($cObjType, $variables[$variableName . '.']));
				}
			} else {
				throw new InvalidArgumentException('Cannot use reserved name "' . $variableName . '" as variable name in FLUIDTEMPLATE');
			}
		}
		$view->assign('data', $this->cObj->data);
		$view->assign('current', $this->cObj->data[$this->cObj->currentValKey]);

		/**
		 * 4. render the content
		 */
		$content = $view->render();
		return $this->cObj->stdWrap($content, $conf['stdWrap.']);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_fluidtemplate.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_fluidtemplate.php']);
}

?>
