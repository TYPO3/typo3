<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
	 * Rendering the cObject, FLUIDTEMPLATE
	 *
	 * Configuration properties:
	 * - file string+stdWrap The FLUID template file
	 * - layoutRootPaths array of filepath+stdWrap Root paths to layouts (fallback)
	 * - partialRootPaths array of filepath+stdWrap Root paths to partials (fallback)
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
	 * 10.partialRootPaths.10 = fileadmin/templates/partial/
	 * 10.variables {
	 *   mylabel = TEXT
	 *   mylabel.value = Label from TypoScript coming
	 * }
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string The HTML output
	 */
	public function render($conf = array()) {
		$parentView = $this->view;
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
		$content = $this->applyStandardWrapToRenderedContent($content, $conf);

		$this->view = $parentView;
		return $content;
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
			$this->view->setTemplatePathAndFilename(PATH_site . $templatePathAndFilename);
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
		$layoutPaths = array();
		if (isset($conf['layoutRootPath']) || isset($conf['layoutRootPath.'])) {
			$layoutRootPath = isset($conf['layoutRootPath.'])
				? $this->cObj->stdWrap($conf['layoutRootPath'], $conf['layoutRootPath.'])
				: $conf['layoutRootPath'];
			$layoutPaths[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($layoutRootPath);
		}
		if (isset($conf['layoutRootPaths.'])) {
			foreach ($conf['layoutRootPaths.'] as $key => $path) {
				$layoutPaths[$key] = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($path);
			}
		}
		if (!empty($layoutPaths)) {
			$this->view->setLayoutRootPaths($layoutPaths);
		}
	}

	/**
	 * Set partial root path if given in configuration
	 *
	 * @param array $conf Configuration array
	 * @return void
	 */
	protected function setPartialRootPath(array $conf) {
		$partialPaths = array();
		if (isset($conf['partialRootPath']) || isset($conf['partialRootPath.'])) {
			$partialRootPath = isset($conf['partialRootPath.'])
				? $this->cObj->stdWrap($conf['partialRootPath'], $conf['partialRootPath.'])
				: $conf['partialRootPath'];
			$partialPaths[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($partialRootPath);
		}
		if (isset($conf['partialRootPaths.'])) {
			foreach ($conf['partialRootPaths.'] as $key => $path) {
				$partialPaths[$key] = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($path);
			}
		}
		if (!empty($partialPaths)) {
			$this->view->setPartialRootPaths($partialPaths);
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
		if (isset($conf['settings.'])) {
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
