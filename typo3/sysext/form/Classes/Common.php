<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Oliver Hader <oliver.hader@typo3.org>
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
 * Common helper methods.
 *
 * @package TYPO3
 * @subpackage form
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class tx_form_Common implements t3lib_Singleton {
	/**
	 * @var array
	 */
	protected $formObjects = array();

	/**
	 * Gets a singleton instance of this object.
	 *
	 * @return tx_form_Common
	 */
	public static function getInstance() {
		return t3lib_div::makeInstance('tx_form_Common');
	}

	/**
	 * Initializes this object.
	 */
	public function __construct() {
		$this->setFormObjects(
			array(
				'BUTTON',
				'CHECKBOX',
				'CHECKBOXGROUP',
				'FIELDSET',
				'FILEUPLOAD',
				'FORM',
				'FORM_INT',
				'HEADER',
				'HIDDEN',
				'IMAGEBUTTON',
				'OPTGROUP',
				'OPTION',
				'PASSWORD',
				'RADIO',
				'RADIOGROUP',
				'RESET',
				'SELECT',
				'SUBMIT',
				'TEXTAREA',
				'TEXTBLOCK',
				'TEXTLINE',
			)
		);
	}

	/**
	 * Gets the available form objects.
	 *
	 * @return array
	 */
	public function getFormObjects() {
		return $this->formObjects;
	}

	/**
	 * Sets the available form objects.
	 *
	 * @param array $formObjects
	 * @return void
	 */
	public function setFormObjects(array $formObjects) {
		$this->formObjects = $formObjects;
	}

	/**
	 * Initializes the available form objects.
	 *
	 * @return tx_form_Common
	 */
	public function initializeFormObjects() {
			// Assign new FORM objects
		foreach ($this->getFormObjects() as $formObject) {
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][] = array(
				$formObject,
				'EXT:form/Classes/Controller/Form.php:&tx_form_Controller_Form'
			);
		}

		return $this;
	}

	/**
	 * Initializes the Page TSconfig properties.
	 *
	 * @return tx_form_Common
	 */
	public function initializePageTsConfig() {
		t3lib_extMgm::addPageTSConfig(
			'<INCLUDE_TYPOSCRIPT: source="FILE:EXT:form/Configuration/PageTS/modWizards.ts">'
		);

		return $this;
	}

	/**
	 * Gets the last part of the current object's class name.
	 * e.g. for 'tx_form_View_Confirmation_Additional' it will be 'Additional'
	 *
	 * @param object $object The object to be used
	 * @param boolean $lowercase Whether to convert to lowercase
	 * @return string
	 */
	public function getLastPartOfClassName($object, $lowercase = FALSE) {
		$lastPart = preg_replace('/.*_([^_]*)$/', '${1}', get_class($object), 1);

		if ($lowercase) {
			$lastPart = strtolower($lastPart);
		}

		return $lastPart;
	}
}
?>