<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Ernesto Baschny <ernst@cron-it.de>
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

require_once(PATH_typo3 . 'contrib/swiftmailer/swift_required.php');


/**
 * Adapter for Swift_Mailer to be used by TYPO3 extensions
 *
 * @author	Ernesto Baschny <ernst@cron-it.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_mail_Message extends Swift_Message {

	/**
	 * @var t3lib_mail_Mailer
	 */
	protected $mailer;

	/**
	 * @var string This will be added as X-Mailer to all outgoing mails
	 */
	protected $mailerHeader = 'TYPO3';

	/**
	 * TRUE if the message has been sent.
	 * @var boolean
	 */
	protected $sent = FALSE;

	/**
	 * Holds the failed recipients after the message has been sent
	 * @var array
	 */
	protected $failedRecipients = array();

	/**
	 *
	 * @return void
	 */
	private function initializeMailer() {
		$this->mailer = t3lib_div::makeInstance('t3lib_mail_Mailer');
	}

	/**
	 * Sends the message.
	 *
	 * @return integer the number of recipients who were accepted for delivery
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function send() {
		$this->initializeMailer();
		$this->sent = TRUE;
		$this->getHeaders()->addTextHeader('X-Mailer', $this->mailerHeader);
		return $this->mailer->send($this, $this->failedRecipients);
	}

	/**
	 * Checks whether the message has been sent.
	 *
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isSent() {
		return $this->sent;
	}

	/**
	 * Returns the recipients for which the mail was not accepted for delivery.
	 *
	 * @return array the recipients who were not accepted for delivery
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getFailedRecipients() {
		return $this->failedRecipients;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_mail_message.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_mail_message.php']);
}

?>