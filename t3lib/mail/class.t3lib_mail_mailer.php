<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Ernesto Baschny <ernst@cron-it.de>
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

	// Make sure Swift's auto-loader is registered
require_once(PATH_typo3 . 'contrib/swiftmailer/swift_required.php');


/**
 * Adapter for Swift_Mailer to be used by TYPO3 extensions.
 *
 * This will use the setting in TYPO3_CONF_VARS to choose the correct transport
 * for it to work out-of-the-box.
 *
 * $Id$
 *
 * @author	Ernesto Baschny <ernst@cron-it.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_mail_Mailer extends Swift_Mailer {

	/**
	 * @var Swift_Transport
	 */
	protected $transport;

	/**
	 * When constructing, also initializes the Swift_Transport like configured
	 *
	 * @param Swift_Transport optionally pass a transport to the constructor. By default the configured transport from $TYPO3_CONF_VARS is used
	 * @throws t3lib_exception
	 */
	public function __construct(Swift_Transport $transport = NULL) {
		if ($transport !== NULL) {
			$this->transport = $transport;
		} else {
			try {
				$this->initializeTransport();
			} catch (Exception $e) {
				throw new t3lib_exception($e->getMessage(), 1291068569);
			}
		}
		parent::__construct($this->transport);
	}

	/**
	 * Prepares a transport using the TYPO3_CONF_VARS configuration
	 *
	 * Used options:
	 * $TYPO3_CONF_VARS['MAIL']['transport'] = 'smtp' | 'sendmail' | 'mail'
	 *
	 * $TYPO3_CONF_VARS['MAIL']['transport_smtp_server'] = 'smtp.example.org';
	 * $TYPO3_CONF_VARS['MAIL']['transport_smtp_port'] = '25';
	 * $TYPO3_CONF_VARS['MAIL']['transport_smtp_encrypt'] = FALSE; # requires openssl in PHP
	 * $TYPO3_CONF_VARS['MAIL']['transport_smtp_username'] = 'username';
	 * $TYPO3_CONF_VARS['MAIL']['transport_smtp_password'] = 'password';
	 *
	 * $TYPO3_CONF_VARS['MAIL']['transport_sendmail_command'] = '/usr/sbin/sendmail -bs'
	 *
	 * @throws t3lib_exception
	 */
	private function initializeTransport() {
		$mailSettings = $GLOBALS['TYPO3_CONF_VARS']['MAIL'];
		switch ($mailSettings['transport']) {

			case 'smtp':
					// Get settings to be used when constructing the transport object
				list($host, $port) = preg_split('/:/', $mailSettings['transport_smtp_server']);
				if ($host === '') {
					throw new t3lib_exception(
						'$TYPO3_CONF_VARS[\'MAIL\'][\'transport_smtp_server\'] needs to be set when transport is set to "smtp"',
						1291068606
					);
				}
				if ($port === '') {
					$port = '25';
				}
				$useEncryption = ($mailSettings['transport_smtp_encrypt'] ? TRUE : FALSE);

					// Create our transport
				$this->transport = Swift_SmtpTransport::newInstance($host, $port, $useEncryption);

					// Need authentication?
				$username = $mailSettings['transport_smtp_username'];
				if ($username !== '') {
					$this->transport->setUsername($username);
				}
				$password = $mailSettings['transport_smtp_password'];
				if ($password !== '') {
					$this->transport->setPassword($password);
				}
				break;

			case 'sendmail':
				$sendmailCommand = $mailSettings['transport_sendmail_command'];
				if ($sendmailCommand === '') {
					throw new t3lib_exception(
						'$TYPO3_CONF_VARS[\'MAIL\'][\'transport_sendmail_command\'] needs to be set when transport is set to "sendmail"',
						1291068620
					);
				}
					// Create our transport
				$this->transport = Swift_SendmailTransport::newInstance($sendmailCommand);
				break;

			case 'mail':
			default:
					// Create the transport, no configuration required
				$this->transport = Swift_MailTransport::newInstance();
				break;
		}
		return;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_mail_mailer.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_mail_mailer.php']);
}

?>