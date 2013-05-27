<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\BeforeExtbase14;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
 * Testcase for the integer validator
 *
 * This testcase checks the expected behavior for Extbase < 1.4.0, to make sure
 * we do not break backwards compatibility.
 */
class IntegerValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

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
		$integerValidator = new \TYPO3\CMS\Extbase\Validation\Validator\IntegerValidator();
		$this->assertTrue($integerValidator->isValid($number), "Validator declared {$number} as invalid though it is valid.");
	}

	/**
	 * @test
	 * @dataProvider invalidIntegerNumbers
	 * @param mixed $number
	 */
	public function integerValidatorReturnsFalseForAnInvalidInteger($number) {
		$integerValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\IntegerValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($integerValidator->isValid($number), "Validator declared {$number} as valid though it is invalid.");
	}

	/**
	 * @test
	 */
	public function integerValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$integerValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\IntegerValidator', array('addError'), array(), '', FALSE);

		$translatedMessage = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('validator.integer.notvalid', 'extbase');
		$integerValidator->expects($this->once())->method('addError')->with($translatedMessage, 1221560494);
		$integerValidator->isValid('not a number');
	}
}

?>