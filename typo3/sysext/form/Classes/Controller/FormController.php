<?php
namespace TYPO3\CMS\Form\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Main controller for Forms.  All requests come through this class
 * and are routed to the model and view layers for processing.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class FormController {

	/**
	 * The TypoScript array
	 *
	 * @var array
	 */
	protected $typoscript = array();

	/**
	 * @var \TYPO3\CMS\Form\Domain\Factory\TypoScriptFactory
	 */
	protected $typoscriptFactory;

	/**
	 * @var \TYPO3\CMS\Form\Localization
	 */
	protected $localizationHandler;

	/**
	 * @var \TYPO3\CMS\Form\Request
	 */
	protected $requestHandler;

	/**
	 * @var \TYPO3\CMS\Form\Utility\ValidatorUtility
	 */
	protected $validate;

	/**
	 * Initialisation
	 *
	 * @param array $typoscript TS configuration for this cObject
	 * @return void
	 */
	public function initialize(array $typoscript) {
		$this->typoscriptFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Domain\\Factory\\TypoScriptFactory');
		$this->localizationHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Localization');
		$this->requestHandler = $this->typoscriptFactory->setRequestHandler($typoscript);
		$this->validate = $this->typoscriptFactory->setRules($typoscript);
		$this->typoscript = $typoscript;
	}

	/**
	 * Renders the application defined cObject FORM
	 * which overrides the TYPO3 default cObject FORM
	 *
	 * First we make a COA_INT out of it, because it does not need to be cached
	 * Then we send a FORM_INT to the COA_INT
	 * When this is read, it will call the FORM class again.
	 *
	 * It simply calls execute because this function name is not really descriptive
	 * but is needed by the core of TYPO3
	 *
	 * @param string $typoScriptObjectName Name of the object
	 * @param array $typoScript TS configuration for this cObject
	 * @param string $typoScriptKey A string label used for the internal debugging tracking.
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject reference
	 * @return string HTML output
	 */
	public function cObjGetSingleExt($typoScriptObjectName, array $typoScript, $typoScriptKey, \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject) {
		$content = '';
		if ($typoScriptObjectName === 'FORM') {
			if ($contentObject->data['CType'] === 'mailform') {
				$bodytext = $contentObject->data['bodytext'];
				/** @var $typoScriptParser \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser */
				$typoScriptParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
				$typoScriptParser->parse($bodytext);
				$mergedTypoScript = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule((array) $typoScriptParser->setup, (array) $typoScript);
				// Disables content elements since TypoScript is handled that could contain insecure settings:
				$mergedTypoScript[\TYPO3\CMS\Form\Domain\Factory\TypoScriptFactory::PROPERTY_DisableContentElement] = TRUE;
			}
			$newTypoScript = array(
				'10' => 'FORM_INT',
				'10.' => $mergedTypoScript
			);
			$content = $contentObject->COBJ_ARRAY($newTypoScript, 'INT');
			// Only apply stdWrap to TypoScript that was NOT created by the wizard:
			if (isset($typoScript['stdWrap.'])) {
				$content = $contentObject->stdWrap($content, $typoScript['stdWrap.']);
			}
		} elseif ($typoScriptObjectName === 'FORM_INT') {
			$this->initialize($typoScript);
			$content = $this->execute();
		}
		return $content;
	}

	/**
	 * Build the models and views and renders the output from the views
	 *
	 * @return string HTML Output
	 */
	public function execute() {
		// Form
		if ($this->showForm()) {
			$content = $this->renderForm();
		} elseif ($this->showConfirmation()) {
			$content = $this->renderConfirmation();
		} else {
			$content = $this->doPostProcessing();
		}
		return $content;
	}

	/**
	 * Check if the form needs to be displayed
	 *
	 * This is TRUE when nothing has been submitted,
	 * when data has been submitted but the validation rules do not fit
	 * or when the user returns from the confirmation screen.
	 *
	 * @return boolean TRUE when form needs to be shown
	 */
	protected function showForm() {
		$show = FALSE;
		$submittedByPrefix = $this->requestHandler->getByMethod();
		if (
			$submittedByPrefix === NULL ||
			!empty($submittedByPrefix) && !$this->validate->isValid() ||
			!empty($submittedByPrefix) && $this->validate->isValid() &&
			$this->requestHandler->getPost('confirmation-false', NULL) !== NULL
		) {
			$show = TRUE;
		}
		return $show;
	}

	/**
	 * Render the form
	 *
	 * @return string The form HTML
	 */
	protected function renderForm() {
		$this->requestHandler->destroySession();
		$form = $this->typoscriptFactory->buildModelFromTyposcript($this->typoscript);
		/** @var $view \TYPO3\CMS\Form\View\Form\FormView */
		$view = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\View\\Form\\FormView', $form);
		return $view->get();
	}

	/**
	 * Check if the confirmation message needs to be displayed
	 *
	 * This is TRUE when data has been submitted,
	 * the validation rules are valid,
	 * the confirmation screen has been configured in TypoScript
	 * and the confirmation screen has not been submitted
	 *
	 * @return boolean TRUE when confirmation screen needs to be shown
	 */
	protected function showConfirmation() {
		$show = FALSE;
		if (isset($this->typoscript['confirmation']) && $this->typoscript['confirmation'] == 1 && $this->requestHandler->getPost('confirmation-true', NULL) === NULL) {
			$show = TRUE;
		}
		return $show;
	}

	/**
	 * Render the confirmation screen
	 *
	 * Stores the submitted data in a session
	 *
	 * @return string The confirmation screen HTML
	 */
	protected function renderConfirmation() {
		$form = $this->typoscriptFactory->buildModelFromTyposcript($this->typoscript);
		$this->requestHandler->storeSession();
		$confirmationTyposcript = array();
		if (isset($this->typoscript['confirmation.'])) {
			$confirmationTyposcript = $this->typoscript['confirmation.'];
		}
		/** @var $view \TYPO3\CMS\Form\View\Confirmation\ConfirmationView */
		$view = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\View\\Confirmation\\ConfirmationView', $form, $confirmationTyposcript);
		return $view->get();
	}

	/**
	 * Do the post processing
	 *
	 * Destroys the session because it is not needed anymore
	 *
	 * @return string The post processing HTML
	 */
	protected function doPostProcessing() {
		$form = $this->typoscriptFactory->buildModelFromTyposcript($this->typoscript);
		$postProcessorTypoScript = array();
		if (isset($this->typoscript['postProcessor.'])) {
			$postProcessorTypoScript = $this->typoscript['postProcessor.'];
		}
		/** @var $postProcessor \TYPO3\CMS\Form\PostProcess\PostProcessor */
		$postProcessor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\PostProcess\\PostProcessor', $form, $postProcessorTypoScript);
		$content = $postProcessor->process();
		$this->requestHandler->destroySession();
		return $content;
	}

}

?>