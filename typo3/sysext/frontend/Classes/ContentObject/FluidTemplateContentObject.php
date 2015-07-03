<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/*
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

use TYPO3\CMS\Extbase\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Contains FLUIDTEMPLATE class object
 */
class FluidTemplateContentObject extends AbstractContentObject {

	/**
	 * @var StandaloneView
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
	 * - dataProcessing array of data processors which are classes to manipulate $data
	 * - extbase.pluginName
	 * - extbase.controllerExtensionName
	 * - extbase.controllerName
	 * - extbase.controllerActionName
	 *
	 * Example:
	 * 10 = FLUIDTEMPLATE
	 * 10.templateName = MyTemplate
	 * 10.templateRootPaths.10 = EXT:site_configuration/Resources/Private/Templates/
	 * 10.partialRootPaths.10 = EXT:site_configuration/Resources/Private/Patials/
	 * 10.layoutRootPaths.10 = EXT:site_configuration/Resources/Private/Layouts/
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

		$this->setFormat($conf);
		$this->setTemplate($conf);
		$this->setLayoutRootPath($conf);
		$this->setPartialRootPath($conf);
		$this->setExtbaseVariables($conf);
		$this->assignSettings($conf);
		$variables = $this->getContentObjectVariables($conf);
		$variables = $this->processData($conf, $variables);

		$this->view->assignMultiple($variables);

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
		$this->view = GeneralUtility::makeInstance(StandaloneView::class);
	}

	/**
	 * Set template
	 *
	 * @param array $conf With possibly set file resource
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	protected function setTemplate(array $conf) {
		// Fetch the Fluid template by templateName
		if (!empty($conf['templateName']) && !empty($conf['templateRootPaths.']) && is_array($conf['templateRootPaths.'])) {
			$templateRootPaths = array();
			foreach ($conf['templateRootPaths.'] as $key => $path) {
				$templateRootPaths[$key] = GeneralUtility::getFileAbsFileName($path);
			}
			$this->view->setTemplateRootPaths($templateRootPaths);
			$templateName = isset($conf['templateName.']) ? $this->cObj->stdWrap($conf['templateName'], $conf['templateName.']) : $conf['templateName'];
			$this->view->setTemplate($templateName);
		// Fetch the Fluid template by template cObject
		} elseif (!empty($conf['template']) && !empty($conf['template.'])) {
			$templateSource = $this->cObj->cObjGetSingle($conf['template'], $conf['template.']);
			$this->view->setTemplateSource($templateSource);
		// Fetch the Fluid template by file stdWrap
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
			$layoutPaths[] = GeneralUtility::getFileAbsFileName($layoutRootPath);
		}
		if (isset($conf['layoutRootPaths.'])) {
			foreach ($conf['layoutRootPaths.'] as $key => $path) {
				$layoutPaths[$key] = GeneralUtility::getFileAbsFileName($path);
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
			$partialPaths[] = GeneralUtility::getFileAbsFileName($partialRootPath);
		}
		if (isset($conf['partialRootPaths.'])) {
			foreach ($conf['partialRootPaths.'] as $key => $path) {
				$partialPaths[$key] = GeneralUtility::getFileAbsFileName($path);
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
	 * Check for the availability of processors, defined in TypoScript, and use them for data processing
	 *
	 * @param array $configuration Configuration array
	 * @param array $variables the variables to be processed
	 * @return array the processed data and variables as key/value store
	 * @throws \UnexpectedValueException
	 */
	protected function processData(array $configuration, array $variables) {
		if (
			!empty($configuration['dataProcessing.'])
			&& is_array($configuration['dataProcessing.'])
		) {
			$processors = $configuration['dataProcessing.'];
			$processorKeys = \TYPO3\CMS\Core\TypoScript\TemplateService::sortedKeyList($processors);

			foreach ($processorKeys as $key) {
				$className = $processors[$key];
				$processor = GeneralUtility::makeInstance($className);

				if (!$processor instanceof DataProcessorInterface) {
					throw new \UnexpectedValueException(
						'$processor with class name "' . $className . '" ' .
						'must implement interface "' . DataProcessorInterface::class . '"',
						1427455377
					);
				}

				$processorConfiguration = isset($processors[$key . '.']) ? $processors[$key . '.'] : array();

				$variables = $processor->process(
					$this->cObj,
					$configuration,
					$processorConfiguration,
					$variables
				);
			}
		}
		return $variables;
	}

	/**
	 * Compile rendered content objects in variables array ready to assign to the view
	 *
	 * @param array $conf Configuration array
	 * @return array the variables to be assigned
	 * @throws \InvalidArgumentException
	 */
	protected function getContentObjectVariables(array $conf) {
		$variables = array();
		$reservedVariables = array('data', 'current');
		// Accumulate the variables to be process and loop them through cObjGetSingle
		$variablesToProcess = (array)$conf['variables.'];
		foreach ($variablesToProcess as $variableName => $cObjType) {
			if (is_array($cObjType)) {
				continue;
			}
			if (!in_array($variableName, $reservedVariables)) {
				$variables[$variableName] = $this->cObj->cObjGetSingle($cObjType, $variablesToProcess[$variableName . '.']);
			} else {
				throw new \InvalidArgumentException(
					'Cannot use reserved name "' . $variableName . '" as variable name in FLUIDTEMPLATE.',
					1288095720
				);
			}
		}
		$variables['data'] = $this->cObj->data;
		$variables['current'] = $this->cObj->data[$this->cObj->currentValKey];
		return $variables;
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
			/** @var $typoScriptService TypoScriptService */
			$typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
			$settings = $typoScriptService->convertTypoScriptArrayToPlainArray($conf['settings.']);
			$this->view->assign('settings', $settings);
		}
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
