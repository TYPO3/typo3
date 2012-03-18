<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Franz Geiger <mail@fx-g.de>
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
 * The DB post processor
 *
 * @author Franz Geiger <mail@fx-g.de>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_System_Postprocessor_Database implements tx_form_System_Postprocessor_Interface {
	/**
	 * @var tx_form_Domain_Model_Form
	 */
	protected $form;

	/**
	 * @var array
	 */
	protected $typoScript;

	/**
	 * Array to be stored in DB
	 *
	 * @var array
	 */
	protected $fields_values;

	/**
	 * @var tx_form_System_Request
	 */
	protected $requestHandler;
	
	/**
	 * @var boolean
	 */
	protected $insertSuccess;

	/**
	 * Constructor
	 *
	 * @param $form tx_form_Domain_Model_Form Form domain model
	 * @param $typoscript array Post processor TypoScript settings
	 * @return void
	 */
	public function __construct(tx_form_Domain_Model_Form $form, array $typoScript) {
		$this->form = $form;
		$this->typoScript = $typoScript;
		$this->fields_values = array ('CType' => 'form_entry');
		$this->requestHandler = t3lib_div::makeInstance('tx_form_System_Request');
	}

	/**
	 * The main method called by the post processor
	 *
	 * Configures the DB entry
	 *
	 * @return string HTML message from this processor
	 */
	public function process() {
		$this->setPid();
		$this->setHeader();
		$this->setContent();
		$this->addAttachmentsFromForm();
		$this->store();

		return $this->render();
	}

	/**
	 * Add the plain content
	 *
	 * @return void
	 */
	protected function setPid() {

		// TODO: sanity check of pid
		if ($this->typoScript['pid']) {
			$this->fields_values['pid'] = $this->typoScript['pid'];
		} else {
			$this->insertSuccess = FALSE;
		}
	}

	/**
	 * Add the plain content
	 *
	 * @return void
	 */
	protected function setHeader() {

		if ($this->typoScript['header']) {
			$this->fields_values['header'] = $this->typoScript['header'];

			// TODO: headerField should be checked if header is not set
		} else {
			$this->fields_values['header'] = 'Form Entry';
			// TODO: find usefull default header to be used if neither header nor headerField is set
		}
	}

	/**
	 * Add the content as a csv table
	 *
	 * @return void
	 */
	protected function setContent() {
		/** @var $view tx_form_View_Database_Csv */
		$view = t3lib_div::makeInstance(
			'tx_form_View_Database_Csv',
			$this->form
		);
		$this->fields_values['bodytext'] = $view->render();
	}

	/**
	 * Stores data in DB.
	 *
	 * @return void
	 */
	protected function store() {
		if ($this->insertSuccess !== FALSE) {
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tt_content', $this->fields_values);
			// TODO: check if INSERT was succesfull 
			$this->insertSuccess = TRUE;
		}
	}

	/**
	 * Render message after trying to store the data in DB
	 *
	 * @return string HTML message from the DB view
	 */
	protected function render() {
		/** @var $view tx_form_View_Database */
		$view = t3lib_div::makeInstance(
			'tx_form_View_Database',
			$this->insertSuccess,
			$this->typoScript
		);

		return $view->render();
	}

	/**
	 * Add attachments when uploaded
	 *
	 * @return void
	 */
	protected function addAttachmentsFromForm() {
		$formElements = $this->form->getElements();
		$values = $this->requestHandler->getByMethod();
		$this->addAttachmentsFromElements($formElements, $values);
	}

	/**
	 * Loop through all elements and move the file to upload folder as defined
	 * in typoscript when the element is a fileupload
	 *
	 * @param array $elements
	 * @param array $submittedValues
	 * @return void
	 */
	protected function addAttachmentsFromElements($elements, $submittedValues) {
		/** @var $element tx_form_Domain_Model_Element_Abstract */
		foreach ($elements as $element) {
			if (is_a($element, 'tx_form_Domain_Model_Element_Container')) {
				$this->addAttachmentsFromElements($element->getElements(), $submittedValues);
				continue;
			}
			if (is_a($element, 'tx_form_Domain_Model_Element_Fileupload')) {
				$elementName = $element->getName();
				if (is_array($submittedValues[$elementName]) && isset($submittedValues[$elementName]['tempFilename'])) {
					$filename = $submittedValues[$elementName]['tempFilename'];
					if (is_file($filename) && t3lib_div::isAllowedAbsPath($filename)) {

						/* 
						 * TODO: move file to path configured in $this->typoscript['uploadFolder']
						 * and save filename in table (later should probably be done in view)
						 */
					}
				}
			}
		}
	}
}
?>
