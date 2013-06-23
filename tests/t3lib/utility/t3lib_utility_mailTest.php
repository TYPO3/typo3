<?php
/***************************************************************
* Copyright notice
*
* (c) 2010-2011 Oliver Klee (typo3-coding@oliverklee.de)
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
 * Testcase for the t3lib_utility_Mail class.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class t3lib_utility_mailTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	public function tearDown() {
		t3lib_div::purgeInstances();
	}


	//////////////////////////
	// Tests concerning mail
	//////////////////////////

	/**
	 * @test
	 */
	public function mailCallsHook() {
		$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'Unit Test';
		$GLOABLS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'unittest@example.org';
		$to = 'john@example.com';
		$subject = 'Good news everybody!';
		$messageBody = 'The hooks works!';
		$additionalHeaders = 'Reply-to: jane@example.com' . LF .
			'From: Unit Test <unittest@example.org>';
		$additionalParameters = '-f postmaster@example.com';

		$mockMailer = $this->getMock('mockMailer', array('mail'));
		$mockMailer->expects($this->once())->method('mail')
			->with(
				array(
					'to' => $to,
					'subject' => $subject,
					'messageBody' => $messageBody,
					'additionalHeaders' => $additionalHeaders,
					'additionalParameters' => $additionalParameters,
				),
				FALSE
			);
		$GLOBALS['T3_VAR']['callUserFunction']['mockMailer->mail']
			= array('obj' => $mockMailer, 'method' => 'mail');

		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']
			['t3lib/utility/class.t3lib_utility_mail.php']
			['substituteMailDelivery'] = array('mockMailer->mail');

		t3lib_utility_Mail::mail(
			$to, $subject, $messageBody, $additionalHeaders,
			$additionalParameters
		);
	}
}
?>