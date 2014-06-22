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
class NumberValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function numberValidatorReturnsTrueForASimpleInteger() {
		$numberValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberValidator', array('addError'), array(), '', FALSE);
		$numberValidator->expects($this->never())->method('addError');
		$numberValidator->isValid(1029437);
	}

	/**
	 * @test
	 */
	public function numberValidatorReturnsFalseForAString() {
		$numberValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$numberValidator->expects($this->once())->method('addError');
		$numberValidator->isValid('not a number');
	}

	/**
	 * @test
	 */
	public function numberValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$numberValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		// we only test for the error key, after the translation method is mocked.
		$numberValidator->expects($this->once())->method('addError')->with(NULL, 1221563685);
		$numberValidator->isValid('this is not a number');
	}
}
