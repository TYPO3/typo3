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
class AlphanumericValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericString() {
		/** @var \TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator::class, array('translateErrorMessage'));
		$this->assertFalse($subject->validate('12ssDF34daweidf')->hasErrors());
	}

	/**
	 * @test
	 */
	public function alphanumericValidatorReturnsErrorsForAStringWithSpecialCharacters() {
		/** @var \TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator::class, array('translateErrorMessage'));
		$this->assertTrue($subject->validate('adsf%&/$jklsfdö')->hasErrors());
	}

	/**
	 * @test
	 */
	public function alphanumericValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		/** @var \TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator::class, array('translateErrorMessage'));
		$this->assertEquals(1, count($subject->validate('adsf%&/$jklsfdö')->getErrors()));
	}

	/**
	 * @test
	 */
	public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericUnicodeString() {
		/** @var \TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator::class, array('translateErrorMessage'));
		$this->assertFalse($subject->validate('12ssDF34daweidfäøüößØœ你好')->hasErrors());
	}

}
