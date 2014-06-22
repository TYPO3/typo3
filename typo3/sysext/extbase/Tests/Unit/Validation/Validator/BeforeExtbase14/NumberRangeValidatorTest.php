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
class NumberRangeValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function deprecatedOptionsAreStillSupported() {
		$this->getMock(
			'TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator',
			array(),
			array(array('startRange' => 0, 'endRange' => 1000))
		);
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorReturnsTrueForASimpleIntegerInRange() {
		$numberRangeValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->expects($this->never())->method('addError');
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 1000));
		$numberRangeValidator->isValid(10.5);
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorReturnsFalseForANumberOutOfRange() {
		$numberRangeValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$numberRangeValidator->expects($this->once())->method('addError');
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 1000));
		$numberRangeValidator->isValid(1000.1);
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorReturnsTrueForANumberInReversedRange() {
		$numberRangeValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->expects($this->never())->method('addError');
		$numberRangeValidator->setOptions(array('startRange' => 1000, 'endRange' => 0));
		$numberRangeValidator->isValid(100);
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorReturnsFalseForAString() {
		$numberRangeValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$numberRangeValidator->expects($this->once())->method('addError');
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 1000));
		$numberRangeValidator->isValid('not a number');
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorCreatesTheCorrectErrorForANumberOutOfRange() {
		$startRange = 1;
		$endRange = 42;

		$numberRangeValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		// we only test for the error key, after the translation method is mocked.
		$numberRangeValidator->expects($this->once())->method('addError')->with(NULL, 1221561046, array(1, 42));
		$numberRangeValidator->setOptions(array('startRange' => $startRange, 'endRange' => $endRange));
		$numberRangeValidator->isValid(4711);
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorCreatesTheCorrectErrorForAStringSubject() {
		$numberRangeValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		// we only test for the error key, after the translation method is mocked.
		$numberRangeValidator->expects($this->once())->method('addError')->with(NULL, 1221563685);
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 42));
		$numberRangeValidator->isValid('this is not between 0 an 42');
	}
}
