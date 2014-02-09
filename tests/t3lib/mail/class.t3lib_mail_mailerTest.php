<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Ernesto Baschny (ernst@cron-it.de)
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
 * Testcase for the t3lib_mail_Mailer class.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
class t3lib_mail_MailerTest extends tx_phpunit_testcase {

	/**
	 * @var t3lib_mail_Mailer
	 */
	protected $fixture;

	/**
	 * @var array
	 */
	protected $globalMailSettings;


	public function setUp() {
		$this->globalMailSettings = $GLOBALS['TYPO3_CONF_VARS']['MAIL'];
		$this->fixture = $this->getMock('t3lib_mail_Mailer', array('noMethodMocked'), array(), '', FALSE);
	}

	public function tearDown() {
		unset($this->fixture);
		$GLOBALS['TYPO3_CONF_VARS']['MAIL'] = $this->globalMailSettings;
	}

	//////////////////////////
	// Tests concerning TYPO3\CMS\Core\Mail\Mailer
	//////////////////////////
	/**
	 * Data provider for wrongConfigigurationThrowsException
	 *
	 * @return array Data sets
	 */
	static public function wrongConfigigurationProvider() {
		return array(
			'smtp but no host' => array(array('transport' => 'smtp')),
			'mbox but no file' => array(array('transport' => 'mbox')),
			'no instance of Swift_Transport' => array(array('transport' => 't3lib_message_ErrorpageMessage'))
		);
	}

	/**
	 * @test
	 * @param $settings
	 * @dataProvider wrongConfigigurationProvider
	 * @expectedException t3lib_exception
	 */
	public function wrongConfigigurationThrowsException($settings) {
		$GLOBALS['TYPO3_CONF_VARS']['MAIL'] = $settings;
		$this->fixture->__construct();
	}

	/**
	 * @test
	 */
	public function providingCorrectClassnameDoesNotThrowException() {
		if (!class_exists('t3lib_mail_SwiftMailerFakeTransport')) {
				// Create fake custom transport class
			eval('class t3lib_mail_SwiftMailerFakeTransport extends t3lib_mail_MboxTransport {
				public function __construct($settings) {}
			}');
		}
		$GLOBALS['TYPO3_CONF_VARS']['MAIL'] = array('transport' => 't3lib_mail_SwiftMailerFakeTransport');
		$this->fixture->__construct();
	}

	/**
	 * @test
	 */
	public function noPortSettingSetsPortTo25() {
		$GLOBALS['TYPO3_CONF_VARS']['MAIL'] = array('transport' => 'smtp', 'transport_smtp_server' => 'localhost');
		$this->fixture->__construct();
		$port = $this->fixture->getTransport()->getPort();
		$this->assertEquals(25, $port);
	}

	/**
	 * @test
	 */
	public function emptyPortSettingSetsPortTo25() {
		$GLOBALS['TYPO3_CONF_VARS']['MAIL'] = array('transport' => 'smtp', 'transport_smtp_server' => 'localhost:');
		$this->fixture->__construct();
		$port = $this->fixture->getTransport()->getPort();
		$this->assertEquals(25, $port);
	}

	/**
	 * @test
	 */
	public function givenPortSettingIsRespected() {
		$GLOBALS['TYPO3_CONF_VARS']['MAIL'] = array('transport' => 'smtp', 'transport_smtp_server' => 'localhost:12345');
		$this->fixture->__construct();
		$port = $this->fixture->getTransport()->getPort();
		$this->assertEquals(12345, $port);
	}
}

?>