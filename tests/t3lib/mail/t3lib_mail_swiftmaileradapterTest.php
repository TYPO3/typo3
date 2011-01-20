<?php
/***************************************************************
* Copyright notice
*
* (c) 2011 Ernesto Baschny (ernst@cron-it.de)
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Testcase for the t3lib_mail_SwiftMailerAdapter class.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
class t3lib_mail_SwiftMailerAdapterTest extends tx_phpunit_testcase {

	public function setUp() {
		if (!class_exists('t3lib_mail_SwiftMailerAdapterExposed')) {
			// Make protected methods accessible so that they can be tested:
			eval('class t3lib_mail_SwiftMailerAdapterExposed extends t3lib_mail_SwiftMailerAdapter {
				public function parseAddressesExposed($args) {
					return $this->parseAddresses($args);
				}
			}');
		}
		$this->fixture = new t3lib_mail_SwiftMailerAdapterExposed();
	}

	public function tearDown() {
	}

	//////////////////////////
	// Tests concerning mail
	//////////////////////////

	/**
	 * Data provider for parseAddressesTest
	 *
	 * @return array Data sets
	 */
	public static function parseAddressesProvider() {
		return array(
			'name &ltemail&gt;' => array('name <email@example.org>', array('email@example.org' => 'name')),
			'&lt;email&gt;' => array('<email@example.org>', array('email@example.org')),
			'email' => array('email@example.org', array('email@example.org')),
			'email1,email2' => array('email1@example.org,email2@example.com', array('email1@example.org', 'email2@example.com')),
			'name &ltemail&gt;,email2' => array('name <email1@example.org>,email2@example.com', array('email1@example.org' => 'name', 'email2@example.com')),
			'"last, first" &lt;name@example.org&gt;' => array('"last, first" <email@example.org>', array('email@example.org' => '"last, first"')),
			'email,name &ltemail&gt;,"last, first" &lt;name@example.org&gt;' =>
				array(
					'email1@example.org, name <email2@example.org>, "last, first" <email3@example.org>',
				array(
					'email1@example.org',
					'email2@example.org' => 'name',
					'email3@example.org' => '"last, first"',
				)
			),
		);
	}

	/**
	 * @test
	 * @dataProvider parseAddressesProvider
	 */
	public function parseAddressesTest($source, $addressList) {
		$this->assertEquals(
			$addressList,
			$this->fixture->parseAddressesExposed($source)
		);
	}
}
?>
