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
 * A generic object validator which allows for specifying property validators
 */
class GenericObjectValidator extends AbstractValidator implements ObjectValidatorInterface {

	/**
	 * @var array
	 */
	protected $propertyValidators = array();

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
			if (!is_object($value)) {
				$this->addError('Object expected, %1$s given.', 1241099149, array(gettype($value)));
			} elseif ($this->isValidatedAlready($value) === FALSE) {
				$this->isValid($value);
			}
		}

		return $this->result;
	}

	/**
	 * Load the property value to be used for validation.
	 *
	 * In case the object is a doctrine proxy, we need to load the real instance first.
	 *
	 * @param object $object
	 * @param string $propertyName
	 * @return mixed
	 */
	protected function getPropertyValue($object, $propertyName) {
		// TODO: add support for lazy loading proxies, if needed
		if (\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($object, $propertyName)) {
			return \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($object, $propertyName);
		} else {
			return \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($object, $propertyName, TRUE);
		}
	}

	/**
	 * Checks if the specified property of the given object is valid, and adds
	 * found errors to the $messages object.
	 *
	 * @param mixed $value The value to be validated
	 * @param array $validators The validators to be called on the value
	 * @param string $propertyName Name of ther property to check
	 * @return void
	 */
	protected function checkProperty($value, $validators, $propertyName) {
		$result = NULL;
		foreach ($validators as $validator) {
			if ($validator instanceof ObjectValidatorInterface) {
				$validator->setValidatedInstancesContainer($this->validatedInstancesContainer);
			}
			$currentResult = $validator->validate($value);
			if ($currentResult->hasMessages()) {
				if ($result == NULL) {
					$result = $currentResult;
				} else {
					$result->merge($currentResult);
				}
			}
		}
		if ($result != NULL) {
			$this->result->forProperty($propertyName)->merge($result);
		}
	}

	/**
	 * Checks if the given value is valid according to the property validators.
	 *
	 * @param mixed $object The value that should be validated
	 * @return void
	 * @api
	 * @todo: method must be protected once the old property mapper is removed
	 */
	public function isValid($object) {
		if ($this->configurationManager->isFeatureEnabled('rewrittenPropertyMapper')) {
			foreach ($this->propertyValidators as $propertyName => $validators) {
				$propertyValue = $this->getPropertyValue($object, $propertyName);
				$this->checkProperty($propertyValue, $validators, $propertyName);
			}

			return;
		} else {
			/* @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1 */
			if (!is_object($object)) {
				$this->addError(
					$this->translateErrorMessage(
						'validator.genericobject.noobject',
						'extbase'
					), 1241099148);

				return FALSE;
			}
			$result = TRUE;
			foreach (array_keys($this->propertyValidators) as $propertyName) {
				if ($this->isPropertyValid($object, $propertyName) === FALSE) {
					$result = FALSE;
				}
			}

			return $result;
		}
	}

	/**
	 * Checks the given object can be validated by the validator implementation
	 *
	 * @param object $object The object to be checked
	 * @return boolean TRUE if the given value is an object
	 * @api
	 */
	public function canValidate($object) {
		return is_object($object);
	}

	/**
	 * Checks if the specified property of the given object is valid.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param object $object The object containing the property to validate
	 * @param string $propertyName Name of the property to validate
	 * @throws \InvalidArgumentException
	 * @return boolean TRUE if the property value is valid, FALSE if an error occurred
	 * @api
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function isPropertyValid($object, $propertyName) {
		if (!is_object($object)) {
			throw new \InvalidArgumentException('Object expected, ' . gettype($object) . ' given.', 1241099149);
		}
		if (!isset($this->propertyValidators[$propertyName])) {
			return TRUE;
		}
		$result = TRUE;
		foreach ($this->propertyValidators[$propertyName] as $validator) {
			$validator->isValid(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($object, $propertyName));
			if (count($validator->getErrors()) > 0) {
				$this->addErrorsForProperty($validator->getErrors(), $propertyName);
				$result = FALSE;
			}
		}
		return $result;
	}

	/**
	 * @param array $errors Array of \TYPO3\CMS\Extbase\Validation\Error
	 * @param string $propertyName Name of the property to add errors
	 * @return void
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	protected function addErrorsForProperty($errors, $propertyName) {
		if (!isset($this->errors[$propertyName])) {
			$this->errors[$propertyName] = new \TYPO3\CMS\Extbase\Validation\PropertyError($propertyName);
		}
		$this->errors[$propertyName]->addErrors($errors);
	}

	/**
	 * Adds the given validator for validation of the specified property.
	 *
	 * @param string $propertyName Name of the property to validate
	 * @param \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface $validator The property validator
	 * @return void
	 * @api
	 */
	public function addPropertyValidator($propertyName, \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface $validator) {
		if (!isset($this->propertyValidators[$propertyName])) {
			$this->propertyValidators[$propertyName] = new \SplObjectStorage();
		}
		$this->propertyValidators[$propertyName]->attach($validator);
	}

	/**
	 * @param object $object
	 * @return boolean
	 */
	protected function isValidatedAlready($object) {
		if ($this->validatedInstancesContainer === NULL) {
			$this->validatedInstancesContainer = new \SplObjectStorage();
		}
		if ($this->validatedInstancesContainer->contains($object)) {
			return TRUE;
		} else {
			$this->validatedInstancesContainer->attach($object);

			return FALSE;
		}
	}

	/**
	 * Returns all property validators - or only validators of the specified property
	 *
	 * @param string $propertyName Name of the property to return validators for
	 * @return array An array of validators
	 */
	public function getPropertyValidators($propertyName = NULL) {
		if ($propertyName !== NULL) {
			return (isset($this->propertyValidators[$propertyName])) ? $this->propertyValidators[$propertyName] : array();
		} else {
			return $this->propertyValidators;
		}
	}

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		// @todo: remove configuration manager once the old property mapper is removed
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @var \SplObjectStorage
	 */
	protected $validatedInstancesContainer;

	/**
	 * Allows to set a container to keep track of validated instances.
	 *
	 * @param \SplObjectStorage $validatedInstancesContainer A container to keep track of validated instances
	 * @return void
	 * @api
	 */
	public function setValidatedInstancesContainer(\SplObjectStorage $validatedInstancesContainer) {
		$this->validatedInstancesContainer = $validatedInstancesContainer;
	}
}
