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
 * Testcase for the email address validator
 *
 * This testcase checks the expected behavior for Extbase < 1.4.0, to make sure
 * we do not break backwards compatibility.
 */
class EmailAddressValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

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
		$emailAddressValidator = new \TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator();
		$this->assertTrue($emailAddressValidator->isValid($address), "{$address} was declared to be invalid, but it is valid.");
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
		$emailAddressValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\EmailAddressValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($emailAddressValidator->isValid($address));
	}

	/**
	 * @test
	 */
	public function emailValidatorCreatesTheCorrectErrorForAnInvalidEmailAddress() {
		$emailAddressValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\EmailAddressValidator', array('addError'), array(), '', FALSE);
		$emailAddressValidator->expects($this->once())->method('addError')->with('The given subject was not a valid email address.', 1221559976);
		$emailAddressValidator->isValid('notAValidMail@Address');
	}
}

?>