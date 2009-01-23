<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Validator for the controller arguments object
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class ArgumentsValidator implements F3_FLOW3_Validation_ObjectValidatorInterface {

	/**
	 * @var F3_FLOW3_Object_FactoryInterface The object factory
	 */
	protected $objectFactory;

	/**
	 * @var TX_EXTMVC_Controller_Arguments The registered arguments with the specified property validators
	 */
	protected $registeredArguments;

	/**
	 * Constructor
	 *
	 * @param TX_EXTMVC_Controller_Arguments The registered arguments with the specified property editors
	 * @param F3_FLOW3_Object_FactoryInterface The object factory
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(TX_EXTMVC_Controller_Arguments $registeredArguments, F3_FLOW3_Object_FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
		$this->registeredArguments = $registeredArguments;
	}

	/**
	 * Checks if classes of the given type can be validated with this
	 * validator.
	 *
	 * @param  string $className: Specifies the class type which is supposed to be validated. The check succeeds if this validator can handle the specified class or any subclass of it.
	 * @return boolean TRUE if this validator can validate the class type or FALSE if it can't
	 */
	public function canValidate($className) {
		return ($className === 'TX_EXTMVC_Controller_Arguments');
	}

	/**
	 * Validates the given object. Any errors will be stored in the passed errors
	 * object. If validation succeeds completely, this method returns TRUE. If at
	 * least one error occurred, the result is FALSE.
	 *
	 * @param object $object: The object which is supposed to be validated.
	 * @param F3_FLOW3_Validation_Errors $errors: Here any occured validation error is stored
	 * @return boolean TRUE if validation succeeded completely, FALSE if at least one error occurred.
	 * @throws F3_FLOW3_Validation_Exception_InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 */
	public function validate($object, F3_FLOW3_Validation_Errors &$errors) {
		if (!$object instanceof TX_EXTMVC_Controller_Arguments) throw new F3_FLOW3_Validation_Exception_InvalidSubject('The specified object cannot be validated by this validator.', 1216720829);

		$isValid = TRUE;
		foreach ($object as $argument) {
			if ($argument->isRequired()) $isValid &= $this->validateProperty($object, $argument->getName(), $errors);
		}

		return (boolean)$isValid;
	}

	/**
	 * Validates a specific property ($propertyName) of the given object. Any errors will be stored
	 * in the given errors object. If validation succeeds, this method returns TRUE, else it will return FALSE.
	 * It also invokes any registered property editors.
	 *
	 * @param object $object: The object of which the property should be validated
	 * @param string $propertyName: The name of the property that should be validated
	 * @param F3_FLOW3_Validation_Errors $errors: Here any occured validation error is stored
	 * @return boolean TRUE if the property could be validated, FALSE if an error occured
	 * @throws F3_FLOW3_Validation_Exception_InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 */
	public function validateProperty($object, $propertyName, F3_FLOW3_Validation_Errors &$errors) {
		if (!$object instanceof TX_EXTMVC_Controller_Arguments) throw new F3_FLOW3_Validation_Exception_InvalidSubject('The specified object cannot be validated by this validator.', 1216720830);

		$propertyValidatorErrors = $this->createNewValidationErrorsObject();

		$isValid = TRUE;
		if ($object[$propertyName]->getValidator() != NULL) $isValid &= $object[$propertyName]->getValidator()->isValidProperty($object[$propertyName]->getValue(), $propertyValidatorErrors);
		$datatypeValidator = $object[$propertyName]->getDatatypeValidator();
		$isValid &= $datatypeValidator->isValidProperty($object[$propertyName]->getValue(), $propertyValidatorErrors);

		if (!$isValid) $errors[$propertyName] = $propertyValidatorErrors;

		return (boolean)$isValid;
	}

	/**
	 * Returns TRUE, if the given property ($proptertyValue) is a valid value for the property ($propertyName) of the class ($className).
	 * Any errors will be stored in the given errors object. If at least one error occurred, the result is FALSE.
	 *
	 * @param string $className: The propterty's class name
	 * @param string $propertyName: The name of the property for wich the value should be validated
	 * @param object $propertyValue: The value that should be validated
	 * @return boolean TRUE if the value could be validated for the given property, FALSE if an error occured
	 */
	public function isValidProperty($className, $propertyName, $propertyValue, F3_FLOW3_Validation_Errors &$errors) {
		$propertyValidatorErrors = $this->createNewValidationErrorsObject();

		$isValid = TRUE;
		if ($this->registeredArguments[$propertyName]->getValidator() != NULL) $isValid &= $this->registeredArguments[$propertyName]->getValidator()->isValidProperty($propertyValue->getValue(), $propertyValidatorErrors);
		$isValid &= $this->registeredArguments[$propertyName]->getDatatypeValidator()->isValidProperty($propertyValue, $propertyValidatorErrors);

		if (!$isValid) $errors[$propertyName] = $propertyValidatorErrors;

		return (boolean)$isValid;
	}

	/**
	 * This is a factory method to get a clean validation errors object
	 *
	 * @return F3_FLOW3_Validation_Errors An empty errors object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function createNewValidationErrorsObject() {
		return $this->objectFactory->create('F3_FLOW3_Validation_Errors');
	}
}
?>