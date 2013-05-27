<?php

/*                                                                        *
 * This script belongs to the Extbase framework.                            *
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

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the Generic Object Validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Extbase_Tests_Unit_Validation_Validator_GenericObjectValidatorTest extends Tx_Extbase_Tests_Unit_Validation_Validator_AbstractValidatorTestcase {

	protected $validatorClassName = 'Tx_Extbase_Validation_Validator_GenericObjectValidator';

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validatorShouldReturnErrorsIfTheValueIsNoObjectAndNotNull() {
		$this->assertTrue($this->validator->validate('foo')->hasErrors());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validatorShouldReturnNoErrorsIfTheValueIsNull() {
		$this->assertFalse($this->validator->validate(NULL)->hasErrors());
	}

	public function dataProviderForValidator() {
		$error1 = new Tx_Extbase_Error_Error('error1', 1);
		$error2 = new Tx_Extbase_Error_Error('error2', 2);

		$emptyResult1 = new Tx_Extbase_Error_Result();
		$emptyResult2 = new Tx_Extbase_Error_Result();

		$resultWithError1 = new Tx_Extbase_Error_Result();
		$resultWithError1->addError($error1);

		$resultWithError2 = new Tx_Extbase_Error_Result();
		$resultWithError2->addError($error2);

		$classNameForObjectWithPrivateProperties = 'B' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $classNameForObjectWithPrivateProperties . '{ protected $foo = \'foovalue\'; protected $bar = \'barvalue\'; }');
		$objectWithPrivateProperties = new $classNameForObjectWithPrivateProperties();

		return array(
			// If no errors happened, this is shown
			array($objectWithPrivateProperties, $emptyResult1, $emptyResult2, array()),

			// If errors on two properties happened, they are merged together.
			array($objectWithPrivateProperties, $resultWithError1, $resultWithError2, array('foo' => array($error1), 'bar' => array($error2)))
		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderForValidator
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validateChecksAllPropertiesForWhichAPropertyValidatorExists($mockObject, $validationResultForFoo, $validationResultForBar, $errors) {

		$validatorForFoo = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$validatorForFoo->expects($this->once())->method('validate')->with('foovalue')->will($this->returnValue($validationResultForFoo));

		$validatorForBar = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$validatorForBar->expects($this->once())->method('validate')->with('barvalue')->will($this->returnValue($validationResultForBar));

		$this->validator->addPropertyValidator('foo', $validatorForFoo);
		$this->validator->addPropertyValidator('bar', $validatorForBar);
		$this->assertEquals($errors, $this->validator->validate($mockObject)->getFlattenedErrors());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validateCanHandleRecursiveTargetsWithoutEndlessLooping() {
		$classNameA = 'B' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $classNameA . '{ public $b; }');
		$classNameB = 'B' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $classNameB . '{ public $a; }');
		$A = new $classNameA();
		$B = new $classNameB();
		$A->b = $B;
		$B->a = $A;

		$aValidator = new Tx_Extbase_Validation_Validator_GenericObjectValidator(array());
		$bValidator = new Tx_Extbase_Validation_Validator_GenericObjectValidator(array());
		$aValidator->addPropertyValidator('b', $bValidator);
		$bValidator->addPropertyValidator('a', $aValidator);

		$this->assertFalse($aValidator->validate($A)->hasErrors());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validateDetectsFailuresInRecursiveTargetsI() {
		$classNameA = 'A' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $classNameA . '{ public $b; }');
		$classNameB = 'B' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $classNameB . '{ public $a; public $uuid = 0xF; }');
		$A = new $classNameA();
		$B = new $classNameB();
		$A->b = $B;
		$B->a = $A;

		$aValidator = $this->getValidator();
		$bValidator = $this->getValidator();

		$aValidator->addPropertyValidator('b', $bValidator);
		$bValidator->addPropertyValidator('a', $aValidator);

		$error = new Tx_Extbase_Error_Error('error1', 123);
		$result = new Tx_Extbase_Error_Result();
		$result->addError($error);
		$mockUuidValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$mockUuidValidator->expects($this->any())->method('validate')->with(0xF)->will($this->returnValue($result));
		$bValidator->addPropertyValidator('uuid', $mockUuidValidator);

		$this->assertSame(array('b.uuid' => array($error)), $aValidator->validate($A)->getFlattenedErrors());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validateDetectsFailuresInRecursiveTargetsII() {
		$classNameA = 'A' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $classNameA . '{ public $b; public $uuid = 0xF; }');
		$classNameB = 'B' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $classNameB . '{ public $a; public $uuid = 0xF; }');
		$A = new $classNameA();
		$B = new $classNameB();
		$A->b = $B;
		$B->a = $A;

		$aValidator = $this->getValidator();
		$bValidator = $this->getValidator();

		$aValidator->addPropertyValidator('b', $bValidator);
		$bValidator->addPropertyValidator('a', $aValidator);

		$error1 = new Tx_Extbase_Error_Error('error1', 123);
		$result1 = new Tx_Extbase_Error_Result();
		$result1->addError($error1);
		$mockUuidValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$mockUuidValidator->expects($this->any())->method('validate')->with(0xF)->will($this->returnValue($result1));
		$aValidator->addPropertyValidator('uuid', $mockUuidValidator);
		$bValidator->addPropertyValidator('uuid', $mockUuidValidator);

		$this->assertSame(array('b.uuid' => array($error1), 'uuid' => array($error1)), $aValidator->validate($A)->getFlattenedErrors());
	}
}

?>