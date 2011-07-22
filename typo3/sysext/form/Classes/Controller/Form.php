<?php
declare(encoding = 'utf-8');

/***************************************************************
*  Copyright notice
*
*  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
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
 * @category Controller
 * @package TYPO3
 * @subpackage form
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @license http://www.gnu.org/copyleft/gpl.html
 * @version $Id$
 */
class tx_form_controller {

	/**
	 * The TypoScript array
	 *
	 * @var array
	 */
	protected $typoscript = array();

	/**
	 * @var tx_form_domain_factory_typoscript
	 */
	protected $typoscriptFactory;

	/**
	 * @var tx_form_system_localization
	 */
	protected $localizationHandler;

	/**
	 * @var tx_form_system_request
	 */
	protected $requestHandler;

	/**
	 * @var tx_form_system_validate
	 */
	protected $validate;

	/**
	 * Initialisation
	 *
	 * @param array $typoscript TS configuration for this cObject
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function initialize($typoscript) {
		t3lib_div::makeInstance(
			'tx_form_system_localization',
			'EXT:form/Resources/Private/Language/locallang_controller.xml'
		);

		$this->typoscriptFactory = t3lib_div::makeInstance('tx_form_domain_factory_typoscript');
		$this->localizationHandler = t3lib_div::makeInstance('tx_form_system_localization');
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
	 * @param string $typoscriptObjectName Name of the object
	 * @param array $typoscript TS configuration for this cObject
	 * @param string $typoscriptKey A string label used for the internal debugging tracking.
	 * @param tslib_cObj $contentObject reference
	 * @return string HTML output
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function cObjGetSingleExt(
		$typoscriptObjectName,
		$typoscript,
		$typoscriptKey,
		tslib_cObj $contentObject
	) {
		$content = '';

		if ($typoscriptObjectName === 'FORM') {
			if ($contentObject->data['CType'] === 'mailform') {
				$bodytext = $contentObject->data['bodytext'];
				/** @var $typoScriptParser t3lib_tsparser */
				$typoScriptParser = t3lib_div::makeInstance('t3lib_tsparser');
				$typoScriptParser->parse($bodytext);
				$typoscript = t3lib_div::array_merge_recursive_overrule(
					(array) $typoScriptParser->setup,
					(array) $typoscript
				);
			}
			$newTyposcript['10'] = 'FORM_INT';
			$newTyposcript['10.'] = $typoscript;
			$content = $contentObject->COBJ_ARRAY($newTyposcript, 'INT');
		} elseif ($typoscriptObjectName == 'FORM_INT') {
			$this->initialize($typoscript);
			$content = $this->execute();
		}

		return $content;
	}

	/**
	 * Build the models and views and renders the output from the views
	 *
	 * @return string HTML Output
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function execute() {
		$content = '';

			// Form
		if ($this->showForm()) {
			$content = $this->renderForm();
		} else {
				// Confirmation screen
			if ($this->showConfirmation()) {
				$content = $this->renderConfirmation();

				// We need the post processing
			} else {
				$content = $this->doPostProcessing();
			}
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
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	private function showForm() {
		$show = FALSE;

		$submittedByPrefix = $this->requestHandler->getByMethod();

		if (
				// Nothing has been submitted
			$submittedByPrefix === NULL ||

				// Submitted but not valid
			(
				!empty($submittedByPrefix) &&
				!$this->validate->isValid()
			) ||

				// Submitted, valid, but not confirmed
			(
				!empty($submittedByPrefix) &&
				$this->validate->isValid() &&
				$this->requestHandler->getPost('confirmation') === $this->localizationHandler->getLocalLanguageLabel('tx_form_view_confirmation.donotconfirm')
			)
		) {
			$show = TRUE;
		}

		return $show;
	}

	/**
	 * Render the form
	 *
	 * @return string The form HTML
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	private function renderForm() {
		$this->requestHandler->destroySession();

		$form = $this->typoscriptFactory->buildModelFromTyposcript($this->typoscript);

		/** @var $view tx_form_view_form */
		$view = t3lib_div::makeInstance('tx_form_view_form', $form);

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
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	private function showConfirmation() {
		$show = FALSE;

		if (
			isset($this->typoscript['confirmation']) &&
			$this->typoscript['confirmation'] == 1 &&
			$this->requestHandler->getPost('confirmation') === NULL
		) {
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
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	private function renderConfirmation() {
		$form = $this->typoscriptFactory->buildModelFromTyposcript($this->typoscript);

		$this->requestHandler->storeSession();

		$confirmationTyposcript = array();
		if (isset($this->typoscript['confirmation.'])) {
			$confirmationTyposcript = $this->typoscript['confirmation.'];
		}

		/** @var $view tx_form_view_confirmation */
		$view = t3lib_div::makeInstance(
			'tx_form_view_confirmation',
			$form,
			$confirmationTyposcript
		);

		return $view->get();
	}

	/**
	 * Do the post processing
	 *
	 * Destroys the session because it is not needed anymore
	 *
	 * @return string The post processing HTML
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	private function doPostProcessing() {
		$form = $this->typoscriptFactory->buildModelFromTyposcript($this->typoscript);

		$postProcessorTypoScript = array();
		if (isset($this->typoscript['postProcessor.'])) {
			$postProcessorTypoScript = $this->typoscript['postProcessor.'];
		}

		/** @var $postProcessor tx_form_system_postprocessor */
		$postProcessor = t3lib_div::makeInstance(
			'tx_form_system_postprocessor',
			$form,
			$postProcessorTypoScript
		);
		$content = $postProcessor->process();
		$this->requestHandler->destroySession();

		return $content;
	}
}
?>