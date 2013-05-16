<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Oliver Klee (typo3-coding@oliverklee.de)
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
 * Testcase for the \TYPO3\CMS\Core\Utility\MailUtility class.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class MailUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * backed-up TYPO3_CONF_VARS SC_OPTIONS
	 *
	 * @var array
	 */
	private $scOptionsBackup = array();

	/**
	 * backed-up T3_VAR callUserFunction
	 *
	 * @var array
	 */
	private $callUserFunctionBackup = array();

	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		$this->scOptionsBackup = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'];
		$this->callUserFunctionBackup = $GLOBALS['T3_VAR']['callUserFunction'];
	}

	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'] = $this->scOptionsBackup;
		$GLOBALS['T3_VAR']['callUserFunction'] = $this->callUserFunctionBackup;
	}

	//////////////////////////
	// Tests concerning mail
	//////////////////////////
	/**
	 * @test
	 */
	public function mailCallsHook() {
		$this->doMailCallsHook();
	}

	/**
	 * @test
	 */
	public function mailCallsHookWithDefaultMailFrom() {
		$this->doMailCallsHook('no-reply@localhost', 'TYPO3 Mailer');
	}

	/**
	 * Method called from tests mailCallsHook() and mailCallsHookWithDefaultMailFrom().
	 */
	protected function doMailCallsHook($fromAddress = '', $fromName = '') {
		// Backup configuration
		$mailConfigurationBackup = $GLOBALS['TYPO3_CONF_VARS']['MAIL'];
		$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = $fromAddress;
		$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = $fromName;
		$to = 'john@example.com';
		$subject = 'Good news everybody!';
		$messageBody = 'The hooks works!';
		$additionalHeaders = 'Reply-to: jane@example.com';
		$additionalParameters = '-f postmaster@example.com';
		$fakeThis = FALSE;
		$additionalHeadersExpected = $additionalHeaders;
		if ($fromAddress !== '' && $fromName !== '') {
			$additionalHeadersExpected .= LF . sprintf('From: "%s" <%s>', $fromName, $fromAddress);
		}
		$mockMailer = $this->getMock('TYPO3\\CMS\\Core\\Mail\\MailerAdapterInterface', array('mail'));
		$mockClassName = get_class($mockMailer);
		\TYPO3\CMS\Core\Utility\GeneralUtility::addInstance($mockClassName, $mockMailer);
		$mockMailer->expects($this->once())->method('mail')->with($to, $subject, $messageBody, $additionalHeadersExpected, $additionalParameters, $fakeThis);
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery'] = array($mockClassName);
		\TYPO3\CMS\Core\Utility\MailUtility::mail($to, $subject, $messageBody, $additionalHeaders, $additionalParameters);
		// Restore configuration
		$GLOBALS['TYPO3_CONF_VARS']['MAIL'] = $mailConfigurationBackup;
	}

	/**
	 * @test
	 */
	public function breakLinesForEmailReturnsEmptyStringIfEmptryStringIsGiven() {
		$this->assertEmpty(\TYPO3\CMS\Core\Utility\MailUtility::breakLinesForEmail(''));
	}

	/**
	 * @test
	 */
	public function breakLinesForEmailReturnsOneLineIfCharWithIsNotExceeded() {
		$newlineChar = LF;
		$lineWidth = 76;
		$str = 'This text is not longer than 76 chars and therefore will not be broken.';
		$returnString = \TYPO3\CMS\Core\Utility\MailUtility::breakLinesForEmail($str, $newlineChar, $lineWidth);
		$this->assertEquals(1, count(explode($newlineChar, $returnString)));
	}

	/**
	 * @test
	 */
	public function breakLinesForEmailBreaksTextIfCharWithIsExceeded() {
		$newlineChar = LF;
		$lineWidth = 50;
		$str = 'This text is longer than 50 chars and therefore will be broken.';
		$returnString = \TYPO3\CMS\Core\Utility\MailUtility::breakLinesForEmail($str, $newlineChar, $lineWidth);
		$this->assertEquals(2, count(explode($newlineChar, $returnString)));
	}

	/**
	 * @test
	 */
	public function breakLinesForEmailBreaksTextWithNoSpaceFoundBeforeLimit() {
		$newlineChar = LF;
		$lineWidth = 10;
		// first space after 20 chars (more than $lineWidth)
		$str = 'abcdefghijklmnopqrst uvwxyz 123456';
		$returnString = \TYPO3\CMS\Core\Utility\MailUtility::breakLinesForEmail($str, $newlineChar, $lineWidth);
		$this->assertEquals($returnString, 'abcdefghijklmnopqrst' . LF . 'uvwxyz' . LF . '123456');
	}

	/**
	 * Data provider for parseAddressesTest
	 *
	 * @return array Data sets
	 */
	public function parseAddressesProvider() {
		return array(
			'name &ltemail&gt;' => array('name <email@example.org>', array('email@example.org' => 'name')),
			'&lt;email&gt;' => array('<email@example.org>', array('email@example.org')),
			'@localhost' => array('@localhost', array()),
			'000@example.com' => array('000@example.com', array('000@example.com')),
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
		$returnArray = \TYPO3\CMS\Core\Utility\MailUtility::parseAddresses($source);
		$this->assertEquals($addressList, $returnArray);
	}

}

?>