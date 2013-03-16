<?php
namespace TYPO3\CMS\Core\Mail;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Ernesto Baschny <ernst@cron-it.de>
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
require_once PATH_typo3 . 'contrib/swiftmailer/swift_required.php';

/**
 * Adapter for Swift_Mailer to be used by TYPO3 extensions.
 *
 * This will use the setting in TYPO3_CONF_VARS to choose the correct transport
 * for it to work out-of-the-box.
 *
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
class Mailer extends \Swift_Mailer {

	/**
	 * @var \Swift_Transport
	 */
	protected $transport;

	/**
	 * @var array
	 */
	protected $mailSettings = array();

	/**
	 * When constructing, also initializes the \Swift_Transport like configured
	 *
	 * @param null|\Swift_Transport $transport optionally pass a transport to the constructor.
	 * @throws \TYPO3\CMS\Core\Exception
	 */
	public function __construct(\Swift_Transport $transport = NULL) {
		if ($transport !== NULL) {
			$this->transport = $transport;
		} else {
			if (empty($this->mailSettings)) {
				$this->injectMailSettings();
			}
			try {
				$this->initializeTransport();
			} catch (\Exception $e) {
				throw new \TYPO3\CMS\Core\Exception($e->getMessage(), 1291068569);
			}
		}
		parent::__construct($this->transport);
	}

	/**
	 * Prepares a transport using the TYPO3_CONF_VARS configuration
	 *
	 * Used options:
	 * $TYPO3_CONF_VARS['MAIL']['transport'] = 'smtp' | 'sendmail' | 'mail' | 'mbox'
	 *
	 * $TYPO3_CONF_VARS['MAIL']['transport_smtp_server'] = 'smtp.example.org';
	 * $TYPO3_CONF_VARS['MAIL']['transport_smtp_port'] = '25';
	 * $TYPO3_CONF_VARS['MAIL']['transport_smtp_encrypt'] = FALSE; # requires openssl in PHP
	 * $TYPO3_CONF_VARS['MAIL']['transport_smtp_username'] = 'username';
	 * $TYPO3_CONF_VARS['MAIL']['transport_smtp_password'] = 'password';
	 *
	 * $TYPO3_CONF_VARS['MAIL']['transport_sendmail_command'] = '/usr/sbin/sendmail -bs'
	 *
	 * @throws \TYPO3\CMS\Core\Exception
	 * @throws \RuntimeException
	 */
	private function initializeTransport() {
		switch ($this->mailSettings['transport']) {
			case 'smtp':
				// Get settings to be used when constructing the transport object
				list($host, $port) = preg_split('/:/', $this->mailSettings['transport_smtp_server']);
				if ($host === '') {
					throw new \TYPO3\CMS\Core\Exception('$TYPO3_CONF_VARS[\'MAIL\'][\'transport_smtp_server\'] needs to be set when transport is set to "smtp"', 1291068606);
				}
				if ($port === '') {
					$port = '25';
				}
				$useEncryption = $this->mailSettings['transport_smtp_encrypt'] ? $this->mailSettings['transport_smtp_encrypt'] : NULL;
				// Create our transport
				$this->transport = \Swift_SmtpTransport::newInstance($host, $port, $useEncryption);
				// Need authentication?
				$username = $this->mailSettings['transport_smtp_username'];
				if ($username !== '') {
					$this->transport->setUsername($username);
				}
				$password = $this->mailSettings['transport_smtp_password'];
				if ($password !== '') {
					$this->transport->setPassword($password);
				}
				break;
			case 'sendmail':
				$sendmailCommand = $this->mailSettings['transport_sendmail_command'];
				if (empty($sendmailCommand)) {
					throw new \TYPO3\CMS\Core\Exception('$TYPO3_CONF_VARS[\'MAIL\'][\'transport_sendmail_command\'] needs to be set when transport is set to "sendmail"', 1291068620);
				}
				// Create our transport
				$this->transport = \Swift_SendmailTransport::newInstance($sendmailCommand);
				break;
			case 'mbox':
				$mboxFile = $this->mailSettings['transport_mbox_file'];
				if ($mboxFile == '') {
					throw new \TYPO3\CMS\Core\Exception('$TYPO3_CONF_VARS[\'MAIL\'][\'transport_mbox_file\'] needs to be set when transport is set to "mbox"', 1294586645);
				}
				// Create our transport
				$this->transport = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MboxTransport', $mboxFile);
				break;
			case 'mail':
				// Create the transport, no configuration required
				$this->transport = \Swift_MailTransport::newInstance();
				break;
			default:
				// Custom mail transport
				$customTransport = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->mailSettings['transport'], $this->mailSettings);
				if ($customTransport instanceof \Swift_Transport) {
					$this->transport = $customTransport;
				} else {
					throw new \RuntimeException($this->mailSettings['transport'] . ' is not an implementation of \\Swift_Transport,
							but must implement that interface to be used as a mail transport.', 1323006478);
				}
		}
	}

	/**
	 * This method is only used in unit tests
	 *
	 * @param array $mailSettings
	 * @access private
	 */
	public function injectMailSettings(array $mailSettings = NULL) {
		if (is_array($mailSettings)) {
			$this->mailSettings = $mailSettings;
		} else {
			$this->mailSettings = (array) $GLOBALS['TYPO3_CONF_VARS']['MAIL'];
		}
	}

}


?>