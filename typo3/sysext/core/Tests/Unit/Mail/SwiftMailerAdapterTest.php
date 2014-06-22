<?php
namespace TYPO3\CMS\Core\Tests\Unit\Mail;

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
 * Testcase for the \TYPO3\CMS\Core\Mail\SwiftMailerAdapter class.
 *
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
class SwiftMailerAdapterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Mail\SwiftMailerAdapter
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = $this->getAccessibleMock('\\TYPO3\\CMS\\Core\\Mail\\SwiftMailerAdapter', array('dummy'));
	}

	//////////////////////////
	// Tests concerning mail
	//////////////////////////
	/**
	 * Data provider for parseAddressesTest
	 *
	 * @return array Data sets
	 */
	static public function parseAddressesProvider() {
		return array(
			'name &ltemail&gt;' => array('name <email@example.org>', array('email@example.org' => 'name')),
			'&lt;email&gt;' => array('<email@example.org>', array('email@example.org')),
			'email' => array('email@example.org', array('email@example.org')),
			'email1,email2' => array('email1@example.org,email2@example.com', array('email1@example.org', 'email2@example.com')),
			'name &ltemail&gt;,email2' => array('name <email1@example.org>,email2@example.com', array('email1@example.org' => 'name', 'email2@example.com')),
			'"last, first" &lt;name@example.org&gt;' => array('"last, first" <email@example.org>', array('email@example.org' => '"last, first"')),
			'email,name &ltemail&gt;,"last, first" &lt;name@example.org&gt;' => array(
				'email1@example.org, name <email2@example.org>, "last, first" <email3@example.org>',
				array(
					'email1@example.org',
					'email2@example.org' => 'name',
					'email3@example.org' => '"last, first"'
				)
			)
		);
	}

	/**
	 * @test
	 * @dataProvider parseAddressesProvider
	 */
	public function parseAddressesTest($source, $addressList) {
		$this->assertEquals($addressList, $this->fixture->_callRef('parseAddresses', $source));
	}

}
