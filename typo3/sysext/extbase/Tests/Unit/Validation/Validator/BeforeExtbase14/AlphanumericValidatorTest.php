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
class AlphanumericValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function alphanumericValidatorReturnsTrueForAnAlphanumericString() {
		$alphanumericValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\AlphanumericValidator', array('dummy'), array(), '', FALSE);
		$alphanumericValidator->expects($this->never())->method('addError');
		$alphanumericValidator->isValid('12ssDF34daweidf');
	}

	/**
	 * @test
	 */
	public function alphanumericValidatorReturnsFalseForAStringWithSpecialCharacters() {
		/** @var \TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator $alphanumericValidator */
		$alphanumericValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\AlphanumericValidator', array('translateErrorMessage'), array(), '', FALSE);
		$alphanumericValidator->expects($this->never())->method('addError');
		$alphanumericValidator->isValid('adsf%&/$jklsfdö');
	}

	/**
	 * @test
	 */
	public function alphanumericValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$alphanumericValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\AlphanumericValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		// we only test for the error key, after the translation method is mocked.
		$alphanumericValidator->expects($this->once())->method('addError')->with(NULL, 1221551320);
		$alphanumericValidator->isValid('adsf%&/$jklsfdö');
	}
}
