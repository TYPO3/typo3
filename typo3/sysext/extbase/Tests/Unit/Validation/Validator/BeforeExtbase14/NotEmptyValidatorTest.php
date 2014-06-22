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
class NotEmptyValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function notEmptyValidatorReturnsTrueForASimpleString() {
		$notEmptyValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NotEmptyValidator', array('addError'), array(), '', FALSE);
		$notEmptyValidator->expects($this->never())->method('addError');
		$notEmptyValidator->isValid('a not empty string');
	}

	/**
	 * @test
	 */
	public function notEmptyValidatorReturnsFalseForAnEmptyString() {
		$notEmptyValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NotEmptyValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$notEmptyValidator->expects($this->once())->method('addError');
		$notEmptyValidator->isValid('');
	}

	/**
	 * @test
	 */
	public function notEmptyValidatorReturnsFalseForANullValue() {
		$notEmptyValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NotEmptyValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$notEmptyValidator->expects($this->once())->method('addError');
		$notEmptyValidator->isValid(NULL);
	}

	/**
	 * @test
	 */
	public function notEmptyValidatorCreatesTheCorrectErrorForAnEmptySubject() {
		$notEmptyValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NotEmptyValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		// we only test for the error key, after the translation method is mocked.
		$notEmptyValidator->expects($this->once())->method('addError')->with(NULL, 1221560718);
		$notEmptyValidator->isValid('');
	}

	/**
	 * @test
	 */
	public function notEmptyValidatorCreatesTheCorrectErrorForANullValue() {
		$notEmptyValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NotEmptyValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		// we only test for the error key, after the translation method is mocked.
		$notEmptyValidator->expects($this->once())->method('addError')->with(NULL, 1221560910);
		$notEmptyValidator->isValid(NULL);
	}
}
