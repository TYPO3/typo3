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
 * Static methods for validation
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_system_validate implements t3lib_Singleton {

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
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct() {
	}

	/**
	 * Create a rule object according to class
	 * and sent some arguments
	 *
	 * @param string $class Name of the validation rule
	 * @param array $arguments Configuration of the rule
	 * @return object The rule object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function createRule($class, $arguments = array()) {
		$class = strtolower((string) $class);
		$className = 'tx_form_system_validate_' . $class;

		$rule = t3lib_div::makeInstance($className, $arguments);

		return $rule;
	}

	/**
	 * Add a rule to the rules array
	 * The rule needs to be completely configured before adding it to the array
	 *
	 * @param object $rule Rule object
	 * @param string $fieldName Field name the rule belongs to
	 * @param boolean $breakOnError Break the rule chain when TRUE
	 * @return tx_form_validate
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function addRule($rule, $fieldName, $breakOnError = FALSE) {
		$this->rules[] = array(
			'instance' => (object) $rule,
			'fieldName' => (string) $fieldName,
			'breakOnError' => (boolean) $breakOnError
		);

		if($rule->messageMustBeDisplayed()) {
			if(!isset($this->messages[$fieldName])) {
				$this->messages[$fieldName] = array();
			}
			end($this->rules);
			$key = key($this->rules);
			$message = $rule->getMessage();
			$this->messages[$fieldName][$key][$key + 1] = $message['cObj'];
			$this->messages[$fieldName][$key][($key + 1) . '.'] = $message['cObj.'];
		}

		return $this;
	}

	/**
	 * Returns TRUE when each rule in the chain returns valid
	 * When a rule has breakOnError set and the rule does not validate,
	 * the check for the remaining rules will stop and method returns FALSE
	 *
	 * @return boolean True if all rules validate
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function isValid() {
		$this->errors = array();
		$result = TRUE;

		foreach ($this->rules as $key => $element) {
			$rule = $element['instance'];
			$fieldName = $element['fieldName'];

			if ($rule->isValid()) {
				continue;
			}
			$result = FALSE;
			if(!isset($this->errors[$fieldName])) {
				$this->errors[$fieldName] = array();
			}
			$error = $rule->getError();
			$this->errors[$fieldName][$key][$key + 1] = $error['cObj'];
			$this->errors[$fieldName][$key][($key + 1) . '.'] = $error['cObj.'];
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
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getMessages() {
		return $this->messages;
	}

	/**
	 * Returns messages for a single form object
	 *
	 * @param string $name Name of the form object
	 * @return array
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getMessagesByName($name) {
		return $this->messages[$name];
	}

	/**
	 * Returns TRUE when a form object has a message
	 *
	 * @param string $name Name of the form object
	 * @return boolean
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function hasMessage($name) {
		if(isset($this->messages[$name])) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Returns all error messages from all rules
	 *
	 * @return array
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Returns error messages for a single form object
	 *
	 * @param string $name Name of the form object
	 * @return array
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getErrorsByName($name) {
		return $this->errors[$name];
	}

	/**
	 * Returns TRUE when a form object has an error
	 *
	 * @param string $name Name of the form object
	 * @return boolean
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function hasErrors($name) {
		if(isset($this->errors[$name])) {
			return TRUE;
		}
		return FALSE;
	}
}
?>