<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
/**
 * Contains a class for formmail
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   69: class t3lib_formmail
 *   95:	 function start($V,$base64=false)
 *  172:	 function addAttachment($file, $filename)
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Formmail class, used by the TYPO3 "cms" extension (default frontend) to send email forms.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see tslib_fe::sendFormmail(), t3lib/formmail.php
 */
class t3lib_formmail {
	protected $reserved_names = 'recipient,recipient_copy,auto_respond_msg,auto_respond_checksum,redirect,subject,attachment,from_email,from_name,replyto_email,replyto_name,organisation,priority,html_enabled,quoted_printable,submit_x,submit_y';
	protected $dirtyHeaders = array(); // collection of suspicious header data, used for logging

	protected $characterSet;
	protected $subject;
	protected $fromName;
	protected $replyToName;
	protected $organisation;
	protected $fromAddress;
	protected $replyToAddress;
	protected $priority;
	protected $autoRespondMessage;
	protected $encoding = 'quoted-printable';

	/** @var t3lib_mail_Message */
	protected $mailMessage;
	protected $recipient;
	protected $plainContent = '';

	/** @var array Files to clean up at the end (attachments) */
	protected $temporaryFiles = array();

	/**
	 * Start function
	 * This class is able to generate a mail in formmail-style from the data in $V
	 * Fields:
	 *
	 * [recipient]:			email-adress of the one to receive the mail. If array, then all values are expected to be recipients
	 * [attachment]:		....
	 *
	 * [subject]:			The subject of the mail
	 * [from_email]:		Sender email. If not set, [email] is used
	 * [from_name]:			Sender name. If not set, [name] is used
	 * [replyto_email]:		Reply-to email. If not set [from_email] is used
	 * [replyto_name]:		Reply-to name. If not set [from_name] is used
	 * [organisation]:		Organization (header)
	 * [priority]:			Priority, 1-5, default 3
	 * [html_enabled]:		If mail is sent as html
	 * [use_base64]:		If set, base64 encoding will be used instead of quoted-printable
	 *
	 * @param	array		Contains values for the field names listed above (with slashes removed if from POST input)
	 * @param	boolean		Whether to base64 encode the mail content
	 * @return	void
	 */
	function start($valueList, $base64 = false) {

		$this->mailMessage = t3lib_div::makeInstance('t3lib_mail_Message');

		if ($GLOBALS['TSFE']->config['config']['formMailCharset']) {
				// Respect formMailCharset if it was set
			$this->characterSet = $GLOBALS['TSFE']->csConvObj->parse_charset($GLOBALS['TSFE']->config['config']['formMailCharset']);
		} elseif ($GLOBALS['TSFE']->metaCharset != $GLOBALS['TSFE']->renderCharset) {
				// Use metaCharset for mail if different from renderCharset
			$this->characterSet = $GLOBALS['TSFE']->metaCharset;
		}

		if ($base64 || $valueList['use_base64']) {
			$this->encoding = 'base64';
		}

		if (isset($valueList['recipient'])) {
				// convert form data from renderCharset to mail charset
			$this->subject = ($valueList['subject'])
					? $valueList['subject']
					: 'Formmail on ' . t3lib_div::getIndpEnv('HTTP_HOST');
			$this->subject = $this->sanitizeHeaderString($this->subject);

			$this->fromName = ($valueList['from_name'])
					? $valueList['from_name']
					: (($valueList['name']) ? $valueList['name'] : '');
			$this->fromName = $this->sanitizeHeaderString($this->fromName);

			$this->replyToName = ($valueList['replyto_name']) ? $valueList['replyto_name'] : $this->fromName;
			$this->replyToName = $this->sanitizeHeaderString($this->replyToName);

			$this->organisation = ($valueList['organisation']) ? $valueList['organisation'] : '';
			$this->organisation = $this->sanitizeHeaderString($this->organisation);

			$this->fromAddress = ($valueList['from_email']) ? $valueList['from_email'] : (
				($valueList['email']) ? $valueList['email'] : ''
			);
			if (!t3lib_div::validEmail($this->fromAddress)) {
				$this->fromAddress = t3lib_utility_Mail::getSystemFromAddress();
				$this->fromName = t3lib_utility_Mail::getSystemFromName();
			}

			$this->replyToAddress = ($valueList['replyto_email']) ? $valueList['replyto_email'] : $this->fromAddress;

			$this->priority = ($valueList['priority']) ? t3lib_div::intInRange($valueList['priority'], 1, 5) : 3;

				// auto responder
			$this->autoRespondMessage = (trim($valueList['auto_respond_msg']) && $this->fromAddress)
					? trim($valueList['auto_respond_msg'])
					: '';

			if ($this->autoRespondMessage !== '') {
					// Check if the value of the auto responder message has been modified with evil intentions
				$autoRespondChecksum = $valueList['auto_respond_checksum'];
				$correctHmacChecksum = t3lib_div::hmac($this->autoRespondMessage);
				if ($autoRespondChecksum !== $correctHmacChecksum) {
					t3lib_div::sysLog('Possible misuse of t3lib_formmail auto respond method. Subject: ' . $valueList['subject'],
						'Core',
						3);
					return;
				} else {
					$this->autoRespondMessage = $this->sanitizeHeaderString($this->autoRespondMessage);
				}
			}

			$plainTextContent = '';
			$htmlContent = '<table border="0" cellpadding="2" cellspacing="2">';

				// Runs through $V and generates the mail
			if (is_array($valueList)) {
				foreach ($valueList as $key => $val) {
					if (!t3lib_div::inList($this->reserved_names, $key)) {
						$space = (strlen($val) > 60) ? LF : '';
						$val = (is_array($val) ? implode($val, LF) : $val);

							// convert form data from renderCharset to mail charset (HTML may use entities)
						$plainTextValue = $val;
						$HtmlValue = htmlspecialchars($val);

						$plainTextContent .= strtoupper($key) . ':  ' . $space . $plainTextValue . LF . $space;
						$htmlContent .= '<tr><td bgcolor="#eeeeee"><font face="Verdana" size="1"><strong>' . strtoupper($key)
								. '</strong></font></td><td bgcolor="#eeeeee"><font face="Verdana" size="1">' . nl2br($HtmlValue)
								. '&nbsp;</font></td></tr>';
					}
				}
			}
			$htmlContent .= '</table>';

			$this->plainContent = $plainTextContent;

			if ($valueList['html_enabled']) {
				$this->mailMessage->setBody($htmlContent, 'text/html');
				$this->mailMessage->addPart($plainTextContent, 'text/plain');
			} else {
				$this->mailMessage->setBody($plainTextContent, 'text/plain');
			}

			for ($a = 0; $a < 10; $a++) {
				$variableName = 'attachment' . (($a) ? $a : '');
				if (!isset($_FILES[$variableName])) {
					continue;
				}
				if (!is_uploaded_file($_FILES[$variableName]['tmp_name'])) {
					t3lib_div::sysLog('Possible abuse of t3lib_formmail: temporary file "' . $_FILES[$variableName]['tmp_name']
							. '" ("' . $_FILES[$variableName]['name'] . '") was not an uploaded file.', 'Core', 3);
				}
				if ($_FILES[$variableName]['tmp_name']['error'] !== UPLOAD_ERR_OK) {
					t3lib_div::sysLog('Error in uploaded file in t3lib_formmail: temporary file "'
							. $_FILES[$variableName]['tmp_name'] . '" ("' . $_FILES[$variableName]['name'] . '") Error code: '
							. $_FILES[$variableName]['tmp_name']['error'], 'Core', 3);
				}
				$theFile = t3lib_div::upload_to_tempfile($_FILES[$variableName]['tmp_name']);
				$theName = $_FILES[$variableName]['name'];

				if ($theFile && file_exists($theFile)) {
					if (filesize($theFile) < $GLOBALS['TYPO3_CONF_VARS']['FE']['formmailMaxAttachmentSize']) {
						$this->mailMessage->attach(Swift_Attachment::fromPath($theFile)->setFilename($theName));
					}
				}
				$this->temporaryFiles[] = $theFile;
			}

			$from = $this->fromName ? array($this->fromAddress => $this->fromName) : array($this->fromAddress);
			$this->recipient = $this->parseAddresses($valueList['recipient']);
			$this->mailMessage->setSubject($this->subject)
					->setFrom($from)
					->setTo($this->recipient)
					->setPriority($this->priority);
			$replyTo = $this->replyToName ? array($this->replyToAddress => $this->replyToName) : array($this->replyToAddress);
			$this->mailMessage->addReplyTo($replyTo);
			$this->mailMessage->getHeaders()->addTextHeader('Organization', $this->organisation);
			if ($valueList['recipient_copy']) {
				$this->mailMessage->addCc($this->parseAddresses($valueList['recipient_copy']));
			}
			if ($this->characterSet) {
				$this->mailMessage->setCharset($this->characterSet);
			}
				// Ignore target encoding. This is handled automatically by Swift Mailer and overriding the defaults
				// is not worth the trouble

				// log dirty header lines
			if ($this->dirtyHeaders) {
				t3lib_div::sysLog('Possible misuse of t3lib_formmail: see TYPO3 devLog', 'Core', 3);
				if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']) {
					t3lib_div::devLog('t3lib_formmail: ' . t3lib_div::arrayToLogString($this->dirtyHeaders, '', 200), 'Core', 3);
				}
			}
		}
	}

	/**
	 * Checks string for suspicious characters
	 *
	 * @param	string	String to check
	 * @return	string	Valid or empty string
	 */
	protected function sanitizeHeaderString($string) {
		$pattern = '/[\r\n\f\e]/';
		if (preg_match($pattern, $string) > 0) {
			$this->dirtyHeaders[] = $string;
			$string = '';
		}
		return $string;
	}

	/**
	 * Parses mailbox headers and turns them into an array.
	 *
	 * Mailbox headers are a comma separated list of 'name <email@example.org' combinations or plain email addresses (or a mix
	 * of these).
	 * The resulting array has key-value pairs where the key is either a number (no display name in the mailbox header) and the
	 * value is the email address, or the key is the email address and the value is the display name.
	 *
	 * @param string $rawAddresses Comma separated list of email addresses (optionally with display name)
	 * @return array Parsed list of addresses.
	 */
	protected function parseAddresses($rawAddresses = '') {
			/** @var $addressParser t3lib_mail_Rfc822AddressesParser */
		$addressParser = t3lib_div::makeInstance('t3lib_mail_Rfc822AddressesParser', $rawAddresses);
		$addresses = $addressParser->parseAddressList();
		$addressList = array();
		foreach ($addresses as $address) {
			if ($address->personal) {
				// item with name found ( name <email@example.org> )
				$addressList[$address->mailbox . '@' . $address->host] = $address->personal;
			} else {
				// item without name found ( email@example.org )
				$addressList[] = $address->mailbox . '@' . $address->host;
			}
		}
		return $addressList;
	}

	/**
	 * Sends the actual mail and handles autorespond message
	 *
	 * @return boolean
	 */
	public function sendTheMail() {

			// Sending the mail requires the recipient and message to be set.
		if (!$this->mailMessage->getTo() || !trim($this->mailMessage->getBody())) {
			return FALSE;
		}

		$this->mailMessage->send();

			// Auto response
		if ($this->autoRespondMessage) {
			$theParts = explode('/', $this->autoRespondMessage, 2);
			$theParts[0] = str_replace('###SUBJECT###', $this->subject, $theParts[0]);
			$theParts[1] = str_replace("/", LF, $theParts[1]);
			$theParts[1] = str_replace("###MESSAGE###", $this->plainContent, $theParts[1]);

				/** @var $autoRespondMail t3lib_mail_Message */
			$autoRespondMail = t3lib_div::makeInstance('t3lib_mail_Message');
			$autoRespondMail->setTo($this->fromAddress)
					->setSubject($theParts[0])
					->setFrom($this->recipient)
					->setBody($theParts[1]);
			$autoRespondMail->send();
		}
		return $this->mailMessage->isSent();
	}

	/**
	 * Do some cleanup at the end (deleting attachment files)
	 */
	public function __destruct() {
		foreach ($this->temporaryFiles as $file) {
			t3lib_div::unlink_tempfile($file);
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_formmail.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_formmail.php']);
}

?>
