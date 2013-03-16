<?php
namespace TYPO3\CMS\Form\Utility;

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
 * Static methods for validation
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ValidatorUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Validation objects to use
	 *
	 * @var array
	 */
	protected $rules = array();

	/**
	 * Messages for all form fields
	 *
	 * @var array
	 */
	protected $messages = array();

	/**
	 * Errors for all form fields
	 *
	 * @var unknown_type
	 */
	protected $errors = array();

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Returns the current prefix of the form
	 * The RequestHandler is asked
	 *
	 * @return String
	 */
	protected function getPrefix() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Request')->getPrefix();
	}

	/**
	 * Create a rule object according to class
	 * and sent some arguments
	 *
	 * @param string $class Name of the validation rule
	 * @param array $arguments Configuration of the rule
	 * @return \TYPO3\CMS\Form\Validation\AbstractValidator The rule object
	 */
	public function createRule($class, $arguments = array()) {
		$class = strtolower((string) $class);
		$className = 'TYPO3\\CMS\\Form\\Validation\\' . ucfirst($class) . 'Validator';
		$rule = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $arguments);
		return $rule;
	}

	/**
	 * Add a rule to the rules array
	 * The rule needs to be completely configured before adding it to the array
	 *
	 * @param object $rule Rule object
	 * @param string $fieldName Field name the rule belongs to
	 * @param boolean $breakOnError Break the rule chain when TRUE
	 * @return \TYPO3\CMS\Form\Utility\ValidatorUtility
	 */
	public function addRule($rule, $fieldName, $breakOnError = FALSE) {
		$prefix = $this->getPrefix();
		$this->rules[$prefix][] = array(
			'instance' => (object) $rule,
			'fieldName' => (string) $fieldName,
			'breakOnError' => (bool) $breakOnError
		);
		if ($rule->messageMustBeDisplayed()) {
			if (!isset($this->messages[$prefix][$fieldName])) {
				$this->messages[$prefix][$fieldName] = array();
			}
			end($this->rules[$prefix]);
			$key = key($this->rules[$prefix]);
			$message = $rule->getMessage();
			$this->messages[$prefix][$fieldName][$key][$key + 1] = $message['cObj'];
			$this->messages[$prefix][$fieldName][$key][($key + 1) . '.'] = $message['cObj.'];
		}
		return $this;
	}

	/**
	 * Returns TRUE when each rule in the chain returns valid
	 * When a rule has breakOnError set and the rule does not validate,
	 * the check for the remaining rules will stop and method returns FALSE
	 *
	 * @return boolean True if all rules validate
	 */
	public function isValid() {
		$prefix = $this->getPrefix();
		$this->errors[$prefix] = array();
		$result = TRUE;
		foreach ($this->rules[$prefix] as $key => $element) {
			$rule = $element['instance'];
			$fieldName = $element['fieldName'];
			if ($rule->isValid()) {
				continue;
			}
			$result = FALSE;
			if (!isset($this->errors[$prefix][$fieldName])) {
				$this->errors[$prefix][$fieldName] = array();
			}
			$error = $rule->getError();
			$this->errors[$prefix][$fieldName][$key][$key + 1] = $error['cObj'];
			$this->errors[$prefix][$fieldName][$key][($key + 1) . '.'] = $error['cObj.'];
			if ($element['breakOnError']) {
				break;
			}
		}
		return $result;
	}

	/**
	 * Returns all messages from all rules
	 *
	 * @return array
	 */
	public function getMessages() {
		return $this->messages[$this->getPrefix()];
	}

	/**
	 * Returns messages for a single form object
	 *
	 * @param string $name Name of the form object
	 * @return array
	 */
	public function getMessagesByName($name) {
		return $this->messages[$this->getPrefix()][$name];
	}

	/**
	 * Returns TRUE when a form object has a message
	 *
	 * @param string $name Name of the form object
	 * @return boolean
	 */
	public function hasMessage($name) {
		if (isset($this->messages[$this->getPrefix()][$name])) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Returns all error messages from all rules
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors[$this->getPrefix()];
	}

	/**
	 * Returns error messages for a single form object
	 *
	 * @param string $name Name of the form object
	 * @return array
	 */
	public function getErrorsByName($name) {
		return $this->errors[$this->getPrefix()][$name];
	}

	/**
	 * Returns TRUE when a form object has an error
	 *
	 * @param string $name Name of the form object
	 * @return boolean
	 */
	public function hasErrors($name) {
		if (isset($this->errors[$this->getPrefix()][$name])) {
			return TRUE;
		}
		return FALSE;
	}

}

?>