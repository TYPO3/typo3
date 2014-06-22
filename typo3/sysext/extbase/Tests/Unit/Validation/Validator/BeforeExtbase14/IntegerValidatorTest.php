<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\BeforeExtbase14;

/**
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
 *
 * This testcase checks the expected behavior for Extbase < 1.4.0, to make sure
 * we do not break backwards compatibility.
 */
class IntegerValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Data provider with valid integer numbers
	 *
	 * @return array Integers, both as int and strings
	 */
	public function validIntegerNumbers() {
		return array(
			array(1029437),
			array(-666),
			array('12345'),
			array('+12345'),
			array('-12345')
		);
	}

	/**
	 * Data provider with invalid integer numbers
	 *
	 * @return array Various values of int, float and strings
	 */
	public function invalidIntegerNumbers() {
		return array(
			array('not a number'),
			array(3.1415),
			array(-0.75),
			array('12345.987'),
			array('-123.45')
		);
	}

	/**
	 * @test
	 * @dataProvider validIntegerNumbers
	 * @param mixed $number
	 */
	public function integerValidatorReturnsTrueForAValidInteger($number) {
		$integerValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\IntegerValidator', array('addError'), array(), '', FALSE);
		$integerValidator->expects($this->never())->method('addError');
		$integerValidator->isValid($number);
	}

	/**
	 * @test
	 * @dataProvider invalidIntegerNumbers
	 * @param mixed $number
	 */
	public function integerValidatorReturnsFalseForAnInvalidInteger($number) {
		$integerValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\IntegerValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$integerValidator->expects($this->once())->method('addError');
		$integerValidator->isValid($number);
	}

	/**
	 * @test
	 */
	public function integerValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$integerValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\IntegerValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		// we only test for the error key, after the translation method is mocked.
		$integerValidator->expects($this->once())->method('addError')->with(NULL, 1221560494);
		$integerValidator->isValid('not a number');
	}
}
