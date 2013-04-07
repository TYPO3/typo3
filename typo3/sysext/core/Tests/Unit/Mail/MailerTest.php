<?php
namespace TYPO3\CMS\Core\Tests\Unit\Mail;

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
 * Testcase for the TYPO3\CMS\Core\Mail\Mailer class.
 *
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
class MailerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Mail\Mailer
	 */
	protected $fixture;

	/**
	 * @var array
	 */
	protected $globalMailSettings;

	public function setUp() {
		$this->globalMailSettings = $GLOBALS['TYPO3_CONF_VARS']['MAIL'];
		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Mail\\Mailer', array('noMethodMocked'), array(), '', FALSE);
	}

	public function tearDown() {
		unset($this->fixture);
		$GLOBALS['TYPO3_CONF_VARS']['MAIL'] = $this->globalMailSettings;
	}

	//////////////////////////
	// Tests concerning TYPO3\CMS\Core\Mail\Mailer
	//////////////////////////
	/**
	 * @test
	 */
	public function injectedSettingsAreNotReplacedByGlobalSettings() {
		$settings = array('transport' => 'mbox', 'transport_mbox_file' => '/path/to/file');
		$GLOBALS['TYPO3_CONF_VARS']['MAIL'] = array('transport' => 'sendmail', 'transport_sendmail_command' => 'sendmail');
		$this->fixture->injectMailSettings($settings);
		$this->fixture->__construct();
		$this->assertAttributeSame($settings, 'mailSettings', $this->fixture);
	}

	/**
	 * @test
	 */
	public function globalSettingsAreUsedIfNoSettingsAreInjected() {
		$settings = ($GLOBALS['TYPO3_CONF_VARS']['MAIL'] = array('transport' => 'sendmail', 'transport_sendmail_command' => 'sendmail'));
		$this->fixture->__construct();
		$this->assertAttributeSame($settings, 'mailSettings', $this->fixture);
	}

	/**
	 * Data provider for wrongConfigigurationThrowsException
	 *
	 * @return array Data sets
	 */
	static public function wrongConfigigurationProvider() {
		return array(
			'smtp but no host' => array(array('transport' => 'smtp')),
			'sendmail but no command' => array(array('transport' => 'sendmail')),
			'mbox but no file' => array(array('transport' => 'mbox')),
			'no instance of Swift_Transport' => array(array('transport' => 'TYPO3\\CMS\\Core\\Messaging\\ErrorpageMessage'))
		);
	}

	/**
	 * @test
	 * @param $settings
	 * @dataProvider wrongConfigigurationProvider
	 * @expectedException \TYPO3\CMS\Core\Exception
	 */
	public function wrongConfigigurationThrowsException($settings) {
		$this->fixture->injectMailSettings($settings);
		$this->fixture->__construct();
	}

	/**
	 * @test
	 */
	public function providingCorrectClassnameDoesNotThrowException() {
		if (!class_exists('t3lib_mail_SwiftMailerFakeTransport')) {
				// Create fake custom transport class
			eval('class t3lib_mail_SwiftMailerFakeTransport extends \\TYPO3\\CMS\\Core\\Mail\\MboxTransport {
				public function __construct($settings) {}
			}');
		}
		$this->fixture->injectMailSettings(array('transport' => 't3lib_mail_SwiftMailerFakeTransport'));
		$this->fixture->__construct();
	}

}

?>