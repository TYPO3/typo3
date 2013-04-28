<?php
namespace TYPO3\CMS\Extbase\Validation\Validator;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractCompositeValidator implements \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface, \Countable {

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $validators;

	/**
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Constructs the validator conjunction
	 */
	public function __construct() {
		$this->validators = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Does nothing.
	 *
	 * @param array $options Not used
	 * @return void
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function setOptions(array $options) {
	}

	/**
	 * Returns an array of errors which occurred during the last isValid() call.
	 *
	 * @return array An array of \TYPO3\CMS\Extbase\Validation\Error objects or an empty array if no errors occurred.
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Adds a new validator to the conjunction.
	 *
	 * @param \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface $validator The validator that should be added
	 * @return void
	 */
	public function addValidator(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface $validator) {
		$this->validators->attach($validator);
	}

	/**
	 * Removes the specified validator.
	 *
	 * @param \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface $validator The validator to remove
	 * @throws \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException
	 * @return void
	 */
	public function removeValidator(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface $validator) {
		if (!$this->validators->contains($validator)) {
			throw new \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException('Cannot remove validator because its not in the conjunction.', 1207020177);
		}
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