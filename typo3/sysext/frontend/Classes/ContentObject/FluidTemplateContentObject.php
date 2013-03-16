<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010-2013 Steffen Kamper <steffen@typo3.org>
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
 * @author Steffen Ritter <info@steffen-ritter.net>
 * @author Benjamin Mack <benni@typo3.org>
 */
class FluidTemplateContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * @var \TYPO3\CMS\Fluid\View\StandaloneView
	 */
	protected $view = NULL;

	/**
	 * Constructor
	 */
	public function __construct(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer) {
		parent::__construct($contentObjectRenderer);
	}

	/**
	 * Rendering the cObject, FLUIDTEMPLATE
	 *
	 * Configuration properties:
	 * - file string+stdWrap The FLUID template file
	 * - layoutRootPath filepath+stdWrap Root path to layouts
	 * - partialRootPath filepath+stdWrap Root path to partial
	 * - variable array of cObjects, the keys are the variable names in fluid
	 * - extbase.pluginName
	 * - extbase.controllerExtensionName
	 * - extbase.controllerName
	 * - extbase.controllerActionName
	 *
	 * Example:
	 * 10 = FLUIDTEMPLATE
	 * 10.template = FILE
	 * 10.template.file = fileadmin/templates/mytemplate.html
	 * 10.partialRootPath = fileadmin/templates/partial/
	 * 10.variables {
	 *   mylabel = TEXT
	 *   mylabel.value = Label from TypoScript coming
	 * }
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string The HTML output
	 */
	public function render($conf = array()) {
		$this->initializeStandaloneViewInstance();

		if (!is_array($conf)) {
			$conf = array();
		}

		$this->setTemplate($conf);
		$this->setLayoutRootPath($conf);
		$this->setPartialRootPath($conf);
		$this->setFormat($conf);
		$this->setExtbaseVariables($conf);
		$this->assignSettings($conf);
		$this->assignContentObjectVariables($conf);
		$this->assignContentObjectDataAndCurrent($conf);

		$content = $this->renderFluidView();

		return $this->applyStandardWrapToRenderedContent($content, $conf);
	}

	/**
	 * Creating standalone view instance must not be done in construct() as
	 * it can lead to a nasty cache issue since content object instances
	 * are not always re-created by the content object rendered for every
	 * usage, but can be re-used. Thus, we need a fresh instance of
	 * StandaloneView every time render() is called.
	 *
	 * @return void
	 */
	protected function initializeStandaloneViewInstance() {
		$this->view = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
	}

	/**
	 * Set template
	 *
	 * @param array $conf With possibly set file resource
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	protected function setTemplate(array $conf) {
		// Fetch the Fluid template
		if (!empty($conf['template']) && !empty($conf['template.'])) {
			$templateSource = $this->cObj->cObjGetSingle($conf['template'], $conf['template.']);
			$this->view->setTemplateSource($templateSource);
		} else {
			$file = isset($conf['file.']) ? $this->cObj->stdWrap($conf['file'], $conf['file.']) : $conf['file'];
			/** @var $templateService \TYPO3\CMS\Core\TypoScript\TemplateService */
			$templateService = $GLOBALS['TSFE']->tmpl;
			$templatePathAndFilename = $templateService->getFileName($file);
			$this->view->setTemplatePathAndFilename($templatePathAndFilename);
		}
	}

	/**
	 * Set layout root path if given in configuration
	 *
	 * @param array $conf Configuration array
	 * @return void
	 */
	protected function setLayoutRootPath(array $conf) {
		// Override the default layout path via typoscript
		$layoutRootPath = isset($conf['layoutRootPath.']) ? $this->cObj->stdWrap($conf['layoutRootPath'], $conf['layoutRootPath.']) : $conf['layoutRootPath'];
		if ($layoutRootPath) {
			$layoutRootPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($layoutRootPath);
			$this->view->setLayoutRootPath($layoutRootPath);
		}
	}

	/**
	 * Set partial root path if given in configuration
	 *
	 * @param array $conf Configuration array
	 * @return void
	 */
	protected function setPartialRootPath(array $conf) {
		$partialRootPath = isset($conf['partialRootPath.']) ? $this->cObj->stdWrap($conf['partialRootPath'], $conf['partialRootPath.']) : $conf['partialRootPath'];
		if ($partialRootPath) {
			$partialRootPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($partialRootPath);
			$this->view->setPartialRootPath($partialRootPath);
		}
	}

	/**
	 * Set different format if given in configuration
	 *
	 * @param array $conf Configuration array
	 * @return void
	 */
	protected function setFormat(array $conf) {
		$format = isset($conf['format.']) ? $this->cObj->stdWrap($conf['format'], $conf['format.']) : $conf['format'];
		if ($format) {
			$this->view->setFormat($format);
		}
	}

	/**
	 * Set some extbase variables if given
	 *
	 * @param array $conf Configuration array
	 * @return void
	 */
	protected function setExtbaseVariables(array $conf) {
		/** @var $request \TYPO3\CMS\Extbase\Mvc\Request */
		$requestPluginName = isset($conf['extbase.']['pluginName.']) ? $this->cObj->stdWrap($conf['extbase.']['pluginName'], $conf['extbase.']['pluginName.']) : $conf['extbase.']['pluginName'];
		if ($requestPluginName) {
			$this->view->getRequest()->setPluginName($requestPluginName);
		}
		$requestControllerExtensionName = isset($conf['extbase.']['controllerExtensionName.']) ? $this->cObj->stdWrap($conf['extbase.']['controllerExtensionName'], $conf['extbase.']['controllerExtensionName.']) : $conf['extbase.']['controllerExtensionName'];
		if ($requestControllerExtensionName) {
			$this->view->getRequest()->setControllerExtensionName($requestControllerExtensionName);
		}
		$requestControllerName = isset($conf['extbase.']['controllerName.']) ? $this->cObj->stdWrap($conf['extbase.']['controllerName'], $conf['extbase.']['controllerName.']) : $conf['extbase.']['controllerName'];
		if ($requestControllerName) {
			$this->view->getRequest()->setControllerName($requestControllerName);
		}
		$requestControllerActionName = isset($conf['extbase.']['controllerActionName.']) ? $this->cObj->stdWrap($conf['extbase.']['controllerActionName'], $conf['extbase.']['controllerActionName.']) : $conf['extbase.']['controllerActionName'];
		if ($requestControllerActionName) {
			$this->view->getRequest()->setControllerActionName($requestControllerActionName);
		}
	}

	/**
	 * Assign rendered content objects in variables array to view
	 *
	 * @param array $conf Configuration array
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	protected function assignContentObjectVariables(array $conf) {
		$reservedVariables = array('data', 'current');
		// Accumulate the variables to be replaced and loop them through cObjGetSingle
		$variables = (array)$conf['variables.'];
		foreach ($variables as $variableName => $cObjType) {
			if (is_array($cObjType)) {
				continue;
			}
			if (!in_array($variableName, $reservedVariables)) {
				$this->view->assign(
					$variableName,
					$this->cObj->cObjGetSingle($cObjType, $variables[$variableName . '.'])
				);
			} else {
				throw new \InvalidArgumentException(
					'Cannot use reserved name "' . $variableName . '" as variable name in FLUIDTEMPLATE.',
					1288095720
				);
			}
		}
	}

	/**
	 * Set any TypoScript settings to the view. This is similar to a
	 * default MVC action controller in extbase.
	 *
	 * @param array $conf Configuration
	 * @return void
	 */
	protected function assignSettings(array $conf) {
		if (array_key_exists('settings.', $conf)) {
			/** @var $typoScriptService \TYPO3\CMS\Extbase\Service\TypoScriptService */
			$typoScriptService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Service\\TypoScriptService');
			$settings = $typoScriptService->convertTypoScriptArrayToPlainArray($conf['settings.']);
			$this->view->assign('settings', $settings);
		}
	}

	/**
	 * Assign content object renderer data and current to view
	 *
	 * @param array $conf Configuration
	 * @return void
	 */
	protected function assignContentObjectDataAndCurrent(array $conf) {
		$this->view->assign('data', $this->cObj->data);
		$this->view->assign('current', $this->cObj->data[$this->cObj->currentValKey]);
	}

	/**
	 * Render fluid standalone view
	 *
	 * @return string
	 */
	protected function renderFluidView() {
		return $this->view->render();
	}

	/**
	 * Apply standard wrap to content
	 *
	 * @param string $content Rendered HTML content
	 * @param array $conf Configuration array
	 * @return string Standard wrapped content
	 */
	protected function applyStandardWrapToRenderedContent($content, array $conf) {
		if (isset($conf['stdWrap.'])) {
			$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		}
		return $content;
	}

}
?>