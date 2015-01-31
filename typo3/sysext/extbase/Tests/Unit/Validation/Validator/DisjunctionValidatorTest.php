<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

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

/**
 * Test case
 */
class DisjunctionValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function allValidatorsInTheDisjunctionAreCalledEvenIfOneReturnsNoError() {
		$this->markTestSkipped('Needs a bugfix of Flow first.');
		$validatorDisjunction = new \TYPO3\CMS\Extbase\Validation\Validator\DisjunctionValidator(array());
		$validatorObject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, array('validate', 'getOptions'));
		$validatorObject->expects($this->once())->method('validate')->will($this->returnValue(new \TYPO3\CMS\Extbase\Error\Result()));
		$errors = new \TYPO3\CMS\Extbase\Error\Result();
		$errors->addError(new \TYPO3\CMS\Extbase\Error\Error('Error', 123));
		$secondValidatorObject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, array('validate', 'getOptions'));
		$secondValidatorObject->expects($this->exactly(1))->method('validate')->will($this->returnValue($errors));
		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);
		$validatorDisjunction->validate('some subject');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function validateReturnsNoErrorsIfOneValidatorReturnsNoError() {
		$validatorDisjunction = new \TYPO3\CMS\Extbase\Validation\Validator\DisjunctionValidator(array());
		$validatorObject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, array('validate', 'getOptions'));
		$validatorObject->expects($this->any())->method('validate')->will($this->returnValue(new \TYPO3\CMS\Extbase\Error\Result()));
		$errors = new \TYPO3\CMS\Extbase\Error\Result();
		$errors->addError(new \TYPO3\CMS\Extbase\Error\Error('Error', 123));
		$secondValidatorObject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, array('validate', 'getOptions'));
		$secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors));
		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);
		$this->assertFalse($validatorDisjunction->validate('some subject')->hasErrors());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function validateReturnsAllErrorsIfAllValidatorsReturnErrrors() {
		$validatorDisjunction = new \TYPO3\CMS\Extbase\Validation\Validator\DisjunctionValidator(array());
		$error1 = new \TYPO3\CMS\Extbase\Error\Error('Error', 123);
		$error2 = new \TYPO3\CMS\Extbase\Error\Error('Error2', 123);
		$errors1 = new \TYPO3\CMS\Extbase\Error\Result();
		$errors1->addError($error1);
		$validatorObject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, array('validate', 'getOptions'));
		$validatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors1));
		$errors2 = new \TYPO3\CMS\Extbase\Error\Result();
		$errors2->addError($error2);
		$secondValidatorObject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, array('validate', 'getOptions'));
		$secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors2));
		$validatorDisjunction->addValidator($validatorObject);
		$validatorDisjunction->addValidator($secondValidatorObject);
		$this->assertEquals(array($error1, $error2), $validatorDisjunction->validate('some subject')->getErrors());
	}

}
