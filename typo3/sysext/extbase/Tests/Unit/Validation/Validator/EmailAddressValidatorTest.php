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
class EmailAddressValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Data provider with valid email addresses
	 *
	 * @return array
	 */
	public function validAddresses() {
		return array(
			array('andreas.foerthner@netlogix.de'),
			array('user@localhost.localdomain'),
			array('info@guggenheim.museum'),
			array('just@test.invalid'),
			array('just+spam@test.de'),
		);
	}

	/**
	 * @test
	 * @dataProvider validAddresses
	 * @param mixed $address
	 */
	public function emailAddressValidatorReturnsNoErrorsForAValidEmailAddress($address) {
		/** @var \TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator::class, array('translateErrorMessage'));
		$this->assertFalse($subject->validate($address)->hasErrors());
	}

	/**
	 * Data provider with invalid email addresses
	 *
	 * @return array
	 */
	public function invalidAddresses() {
		return array(
			array('andreas.foerthner@'),
			array('@typo3.org'),
			array('someone@typo3.'),
			array('local@192.168.2'),
			array('local@192.168.270.1'),
			array('foo@bar.com' . chr(0)),
			array('foo@bar.org' . chr(10)),
			array('andreas@foerthner@example.com'),
			array('some@one.net ')
		);
	}

	/**
	 * @test
	 * @dataProvider invalidAddresses
	 * @param mixed $address
	 */
	public function emailAddressValidatorReturnsFalseForAnInvalidEmailAddress($address) {
		/** @var \TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator::class, array('translateErrorMessage'));
		$this->assertTrue($subject->validate($address)->hasErrors());
	}

	/**
	 * @test
	 */
	public function emailValidatorCreatesTheCorrectErrorForAnInvalidEmailAddress() {
		/** @var \TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator::class, array('translateErrorMessage'));
		$this->assertEquals(1, count($subject->validate('notAValidMail@Address')->getErrors()));
	}

}
