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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * A generic collection validator.
 *
 * @api
 */
class CollectionValidator extends GenericObjectValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'elementValidator' => array(NULL, 'The validator type to use for the collection elements', 'string'),
		'elementType' => array(NULL, 'The type of the elements in the collection', 'string'),
		'validationGroups' => array(NULL, 'The validation groups to link to', 'string'),
	);

	/**
	 * @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver
	 * @inject
	 */
	protected $validatorResolver;

	/**
	 * Checks if the given value is valid according to the validator, and returns
	 * the Error Messages object which occurred.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\CMS\Extbase\Error\Result
	 * @api
	 */
	public function validate($value) {
		$this->result = new \TYPO3\CMS\Extbase\Error\Result();

		if ($this->acceptsEmptyValues === FALSE || $this->isEmpty($value) === FALSE) {
			if ((is_object($value) && !\TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::isCollectionType(get_class($value))) && !is_array($value)) {
				$this->addError('The given subject was not a collection.', 1317204797);
				return $this->result;
			} elseif ($value instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage && !$value->isInitialized()) {
				return $this->result;
			} elseif (is_object($value) && $this->isValidatedAlready($value)) {
				return $this->result;
			} else {
				$this->isValid($value);
			}
		}
		return $this->result;
	}

	/**
	 * Checks for a collection and if needed validates the items in the collection.
	 * This is done with the specified element validator or a validator based on
	 * the given element type and validation group.
	 *
	 * Either elementValidator or elementType must be given, otherwise validation
	 * will be skipped.
	 *
	 * @param mixed $value A collection to be validated
	 * @return void
	 * @todo: method must be protected once the old property mapper is removed
	 */
	public function isValid($value) {
		if (!$this->configurationManager->isFeatureEnabled('rewrittenPropertyMapper')) {
			// @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
			if ($this->validatedInstancesContainer == NULL) {
				$this->validatedInstancesContainer = new \SplObjectStorage();
			}

			if ($this->result == NULL) {
				$this->result = new \TYPO3\CMS\Extbase\Error\Result();
			}
		}

		foreach ($value as $index => $collectionElement) {
			if (isset($this->options['elementValidator'])) {
				$collectionElementValidator = $this->validatorResolver->createValidator($this->options['elementValidator']);
			} elseif (isset($this->options['elementType'])) {
				if (isset($this->options['validationGroups'])) {
					$collectionElementValidator = $this->validatorResolver->getBaseValidatorConjunction($this->options['elementType'], $this->options['validationGroups']);
				} else {
					$collectionElementValidator = $this->validatorResolver->getBaseValidatorConjunction($this->options['elementType']);
				}
			} else {
				return;
			}
			if ($collectionElementValidator instanceof ObjectValidatorInterface) {
				$collectionElementValidator->setValidatedInstancesContainer($this->validatedInstancesContainer);
			}
			$this->result->forProperty($index)->merge($collectionElementValidator->validate($collectionElement));
		}
	}
}
