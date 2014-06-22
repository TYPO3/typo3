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
class FloatValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Data provider with valid floating point numbers
	 *
	 * @return array Floats, both as float and string
	 */
	public function validFloatingPointNumbers() {
		return array(
			array(1029437.234726),
			array(-666.66),
			array('123.45'),
			array('+123.45'),
			array('-123.45'),
			array('123.45e3'),
			array(123450.0)
		);
	}

	/**
	 * Data provider with valid floating point numbers
	 *
	 * @return array Floats, both as float and string
	 */
	public function invalidFloatingPointNumbers() {
		return array(
			array(1029437),
			array(-666),
			array('1029437'),
			array('-666'),
			array('not a number')
		);
	}

	/**
	 * @test
	 * @dataProvider validFloatingPointNumbers
	 * @param mixed $number
	 */
	public function floatValidatorReturnsTrueForAValidFloat($number) {
		$floatValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\FloatValidator', array('addError'), array(), '', FALSE);
		$floatValidator->expects($this->never())->method('addError');
		$floatValidator->isValid($number);
	}

	/**
	 * @test
	 * @dataProvider invalidFloatingPointNumbers
	 * @param mixed $number
	 */
	public function floatValidatorReturnsFalseForAnInvalidFloat($number) {
		$floatValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\FloatValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$floatValidator->expects($this->once())->method('addError');
		$floatValidator->isValid($number);
	}

	/**
	 * @test
	 */
	public function floatValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$floatValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\FloatValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		// we only test for the error key, after the translation method is mocked.
		$floatValidator->expects($this->once())->method('addError')->with(NULL, 1221560288);
		$floatValidator->isValid(123456);
	}
}
