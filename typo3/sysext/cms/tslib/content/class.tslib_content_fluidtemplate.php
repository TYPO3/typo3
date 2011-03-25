<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010-2011 Steffen Kamper <steffen@typo3.org>
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
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 * @author Bastian Waidelich <bastian@typo3.org>
 */
class tslib_content_FluidTemplate extends tslib_content_Abstract {

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
	 * @author	Steffen Ritter		<info@steffen-ritter.net>
	 * @author	Benjamin Mack		<benni@typo3.org>
	 * @author	Bastian Waidelich	<bastian@typo3.org>
	 */
	public function render($conf = array()) {
			// check if the needed extensions are installed
		if (!t3lib_extMgm::isLoaded('fluid')) {
			return 'You need to install "Fluid" in order to use the FLUIDTEMPLATE content element';
		}

		/**
		 * 1. initializing Fluid StandaloneView and setting configuration parameters
		 **/
		$view = t3lib_div::makeInstance('Tx_Fluid_View_StandaloneView');
			// fetch the Fluid template
		$file = isset($conf['file.'])
			? $this->cObj->stdWrap($conf['file'], $conf['file.'])
			: $conf['file'];
		$templatePathAndFilename = $GLOBALS['TSFE']->tmpl->getFileName($file);
		$view->setTemplatePathAndFilename($templatePathAndFilename);

			// override the default layout path via typoscript
		$layoutRootPath = isset($conf['layoutRootPath.'])
			? $this->cObj->stdWrap($conf['layoutRootPath'], $conf['layoutRootPath.'])
			: $conf['layoutRootPath'];
		if($layoutRootPath) {
			$layoutRootPath = t3lib_div::getFileAbsFileName($layoutRootPath);
			$view->setLayoutRootPath($layoutRootPath);
		}

			// override the default partials path via typoscript
		$partialRootPath = isset($conf['partialRootPath.'])
			? $this->cObj->stdWrap($conf['partialRootPath'], $conf['partialRootPath.'])
			: $conf['partialRootPath'];
		if($partialRootPath) {
			$partialRootPath = t3lib_div::getFileAbsFileName($partialRootPath);
			$view->setPartialRootPath($partialRootPath);
		}

			// override the default format
		$format = isset($conf['format.'])
			? $this->cObj->stdWrap($conf['format'], $conf['format.'])
			: $conf['format'];
		if ($format) {
			$view->setFormat($format);
		}

			// set some default variables for initializing Extbase
		$requestPluginName = isset($conf['extbase.']['pluginName.'])
			? $this->cObj->stdWrap($conf['extbase.']['pluginName'], $conf['extbase.']['pluginName.'])
			: $conf['extbase.']['pluginName'];
		if($requestPluginName) {
			$view->getRequest()->setPluginName($requestPluginName);
		}

		$requestControllerExtensionName = isset($conf['extbase.']['controllerExtensionName.'])
			? $this->cObj->stdWrap($conf['extbase.']['controllerExtensionName'], $conf['extbase.']['controllerExtensionName.'])
			: $conf['extbase.']['controllerExtensionName'];
		if($requestControllerExtensionName) {
			$view->getRequest()->setControllerExtensionName($requestControllerExtensionName);
		}

		$requestControllerName = isset($conf['extbase.']['controllerName.'])
			? $this->cObj->stdWrap($conf['extbase.']['controllerName'], $conf['extbase.']['controllerName.'])
			: $conf['extbase.']['controllerName'];
		if($requestControllerName) {
			$view->getRequest()->setControllerName($requestControllerName);
		}

		$requestControllerActionName = isset($conf['extbase.']['controllerActionName.'])
			? $this->cObj->stdWrap($conf['extbase.']['controllerActionName'], $conf['extbase.']['controllerActionName.'])
			: $conf['extbase.']['controllerActionName'];
		if($requestControllerActionName) {
			$view->getRequest()->setControllerActionName($requestControllerActionName);
		}

		/**
		 * 2. variable assignment
 		 */
		$reservedVariables = array('data', 'current');
			// accumulate the variables to be replaced
			// and loop them through cObjGetSingle
		$variables = (array) $conf['variables.'];
		foreach ($variables as $variableName => $cObjType) {
			if (is_array($cObjType)) {
				continue;
			}
			if(!in_array($variableName, $reservedVariables)) {
				$view->assign($variableName, $this->cObj->cObjGetSingle($cObjType, $variables[$variableName . '.']));
			} else {
				throw new InvalidArgumentException('Cannot use reserved name "' . $variableName . '" as variable name in FLUIDTEMPLATE.', 1288095720);
			}
		}
		$view->assign('data', $this->cObj->data);
		$view->assign('current', $this->cObj->data[$this->cObj->currentValKey]);

		/**
		 * 3. render the content
		 */
		$theValue = $view->render();

		if(isset($conf['stdWrap.'])) {
			$theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
		}

		return $theValue;

	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_fluidtemplate.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_fluidtemplate.php']);
}

?>