<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * An abstract composite validator with consisting of other validators
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id: AbstractCompositeValidator.php 1729 2009-11-25 21:37:20Z stucki $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
abstract class Tx_Extbase_Validation_Validator_AbstractCompositeValidator implements Tx_Extbase_Validation_Validator_ValidatorInterface, Countable {

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 */
	protected $validators;

	/**
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Constructs the validator conjunction
	 *
	 */
	public function __construct() {
		$this->validators = new Tx_Extbase_Persistence_ObjectStorage();
	}

	/**
	 * Does nothing.
	 *
	 * @param array $options Not used
	 * @return void
	 */
	public function setOptions(array $options) {
	}

	/**
	 * Returns an array of errors which occurred during the last isValid() call.
	 *
	 * @return array An array of Tx_Extbase_Validation_Error objects or an empty array if no errors occurred.
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Adds a new validator to the conjunction.
	 *
	 * @param Tx_Extbase_Validation_Validator_ValidatorInterface $validator The validator that should be added
	 * @return void
	 */
	public function addValidator(Tx_Extbase_Validation_Validator_ValidatorInterface $validator) {
		$this->validators->attach($validator);
	}

	/**
	 * Removes the specified validator.
	 *
	 * @param Tx_Extbase_Validation_ValidatorInterface $validator The validator to remove
	 */
	public function removeValidator(Tx_Extbase_Validation_Validator_ValidatorInterface $validator) {
		if (!$this->validators->contains($validator)) throw new Tx_Extbase_Validation_Exception_NoSuchValidator('Cannot remove validator because its not in the conjunction.', 1207020177);
		$this->validators->detach($validator);
	}

	/**
	 * Returns the number of validators contained in this conjunction.
	 *
	 * @return integer The number of validators
	 */
	public function count() {
		return count($this->validators);
	}
}

?>