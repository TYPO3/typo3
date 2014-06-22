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
 * Testcase for the TYPO3\CMS\Core\Mail\Mailer class.
 *
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
class MailerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Mail\Mailer
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Mail\\Mailer', array('noMethodMocked'), array(), '', FALSE);
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

	/**
	 * @test
	 */
	public function noPortSettingSetsPortTo25() {
		$this->fixture->injectMailSettings(array('transport' => 'smtp', 'transport_smtp_server' => 'localhost'));
		$this->fixture->__construct();
		$port = $this->fixture->getTransport()->getPort();
		$this->assertEquals(25, $port);
	}

	/**
	 * @test
	 */
	public function emptyPortSettingSetsPortTo25() {
		$this->fixture->injectMailSettings(array('transport' => 'smtp', 'transport_smtp_server' => 'localhost:'));
		$this->fixture->__construct();
		$port = $this->fixture->getTransport()->getPort();
		$this->assertEquals(25, $port);
	}

	/**
	 * @test
	 */
	public function givenPortSettingIsRespected() {
		$this->fixture->injectMailSettings(array('transport' => 'smtp', 'transport_smtp_server' => 'localhost:12345'));
		$this->fixture->__construct();
		$port = $this->fixture->getTransport()->getPort();
		$this->assertEquals(12345, $port);
	}
}
