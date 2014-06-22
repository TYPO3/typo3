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
			array('just+spam@test.de')
		);
	}

	/**
	 * @test
	 * @dataProvider validAddresses
	 * @param mixed $address
	 */
	public function emailAddressValidatorReturnsTrueForAValidEmailAddress($address) {
		$emailAddressValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\EmailAddressValidator', array('addError'), array(), '', FALSE);
		$emailAddressValidator->expects($this->never())->method('addError');
		$emailAddressValidator->isValid($address);
	}

	/**
	 * Data provider with invalid email addresses
	 *
	 * @return array
	 */
	public function invalidAddresses() {
		return array(
			array('andreas.foerthner@'),
			array('andreas@foerthner@example.com'),
			array('@typo3.org'),
			array('someone@typo3.'),
			array('local@192.168.2'),
			array('local@192.168.270.1'),
			array('just@test.invalid ')
		);
	}

	/**
	 * @test
	 * @dataProvider invalidAddresses
	 * @param mixed $address
	 */
	public function emailAddressValidatorReturnsFalseForAnInvalidEmailAddress($address) {
		$emailAddressValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\EmailAddressValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$emailAddressValidator->expects($this->once())->method('addError');
		$emailAddressValidator->isValid($address);
	}

	/**
	 * @test
	 */
	public function emailValidatorCreatesTheCorrectErrorForAnInvalidEmailAddress() {
		$emailAddressValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\EmailAddressValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		// we only test for the error key, after the translation method is mocked.
		$emailAddressValidator->expects($this->once())->method('addError')->with(NULL, 1221559976);
		$emailAddressValidator->isValid('notAValidMail@Address');
	}
}
