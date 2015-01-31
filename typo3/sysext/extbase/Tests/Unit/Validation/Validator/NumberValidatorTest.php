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
class NumberValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	protected $validatorClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberValidator';

	/**
	 * @var \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface
	 */
	protected $validator;

	public function setup() {
		$this->validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'));
	}

	/**
	 * @test
	 */
	public function numberValidatorReturnsTrueForASimpleInteger() {
		$this->assertFalse($this->validator->validate(1029437)->hasErrors());
	}

	/**
	 * @test
	 */
	public function numberValidatorReturnsFalseForAString() {
		$expectedResult = new \TYPO3\CMS\Extbase\Error\Result();
		// we only test for the error code, after the message translation method is mocked
		$expectedResult->addError(new \TYPO3\CMS\Extbase\Validation\Error(NULL, 1221563685));
		$this->assertEquals($expectedResult, $this->validator->validate('not a number'));
	}
}
