<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3. 
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
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
 * @package Extbase
 * @subpackage extbase
 * @version $Id: EmailAddressValidator_testcase.php 1408 2009-10-08 13:15:09Z jocrau $
 */
class Tx_Extbase_Validation_Validator_EmailAddressValidator_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * An array of valid email addresses
	 * @var array
	 */
	protected $validAddresses;
	
	public function setUp() {
		$this->validAddresses = array(
				'andreas.foerthner@netlogix.de',
				'user@localhost',
				'user@localhost.localdomain',
				'info@guggenheim.museum',
				'just@test.invalid',
				'just+spam@test.de',
				'local@192.168.0.2)'
			);
	}
	
	/**
	 * @test
	 * @dataProvider validAddresses
	 */
	public function emailAddressValidatorReturnsTrueForAValidEmailAddress() {
		$emailAddressValidator = new Tx_Extbase_Validation_Validator_EmailAddressValidator();
		foreach ($this->validAddresses as $address) {
			$this->assertTrue($emailAddressValidator->isValid($address), "$address was declared to be invalid, but it is valid.");
		}
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
			array('local@192.168.270.1')
		);
	}

	/**
	 * @test
	 * @dataProvider invalidAddresses
	 */
	public function emailAddressValidatorReturnsFalseForAnInvalidEmailAddress() {
		$emailAddressValidator = $this->getMock('Tx_Extbase_Validation_Validator_EmailAddressValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($emailAddressValidator->isValid($address));
	}

	/**
	 * @test
	 */
	public function emailValidatorCreatesTheCorrectErrorForAnInvalidEmailAddress() {
		$emailAddressValidator = $this->getMock('Tx_Extbase_Validation_Validator_EmailAddressValidator', array('addError'), array(), '', FALSE);
		$emailAddressValidator->expects($this->once())->method('addError')->with('The given subject was not a valid email address.', 1221559976);
		$emailAddressValidator->isValid('notAValidMail@Address');
	}

}

?>