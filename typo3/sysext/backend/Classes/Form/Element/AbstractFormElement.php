<?php
namespace TYPO3\CMS\Backend\Form\Element;

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

use TYPO3\CMS\Backend\Form\FormEngine;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Base class for form elements of FormEngine
 */
abstract class AbstractFormElement {

	/**
	 * @var string A CSS class name prefix for all element types, single elements add their type to this string
	 */
	protected $cssClassTypeElementPrefix = 't3-formengine-field-';

	/**
	 * @var FormEngine
	 */
	protected $formEngine;

	/**
	 * Constructor function, setting the FormEngine
	 *
	 * @param FormEngine $formEngine
	 */
	public function __construct(FormEngine $formEngine) {
		$this->formEngine = $formEngine;
	}

	/**
	 * Handler for Flex Forms
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $additionalInformation An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 */
	abstract public function render($table, $field, $row, &$additionalInformation);

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @return DocumentTemplate
	 */
	protected function getDocumentTemplate() {
		return $GLOBALS['TBE_TEMPLATE'];
	}

	/**
	 * @return DocumentTemplate
	 */
	protected function getControllerDocumentTemplate() {
		// $GLOBALS['SOBE'] might be any kind of PHP class (controller most of the times)
		// These classes do not inherit from any common class, but they all seem to have a "doc" member
		return $GLOBALS['SOBE']->doc;
	}
}
