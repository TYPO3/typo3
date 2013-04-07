<?php
namespace TYPO3\CMS\Core\Mail;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Jigal van Hemert <jigal@xs4all.nl>
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
 * Hook subscriber for using Swift Mailer with \TYPO3\CMS\Core\Utility\MailUtility
 *
 * @author Jigal van Hemert <jigal@xs4all.nl>
 * @deprecated since 6.1, will be removed two versions later - will be removed together with \TYPO3\CMS\Core\Utility\MailUtility::mail()
 */
class SwiftMailerAdapter implements \TYPO3\CMS\Core\Mail\MailerAdapterInterface {

	/**
	 * @var $mailer \TYPO3\CMS\Core\Mail\Mailer
	 */
	protected $mailer;

	/**
	 * @var $message Swift_Message
	 */
	protected $message;

	/**
	 * @var $messageHeaders Swift_Mime_HeaderSet
	 */
	protected $messageHeaders;

	/**
	 * @var string
	 */
	protected $boundary = '';

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		// create mailer object
		$this->mailer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\Mailer');
		// create message object
		$this->message = \Swift_Message::newInstance();
	}

	/**
	 * Parses parts of the mail message and sends it with the Swift Mailer functions
	 *
	 * @param string $to Email address to send the message to
	 * @param string $subject Subject of mail message
	 * @param string $messageBody Raw body (may be multipart)
	 * @param array $additionalHeaders Additional mail headers
	 * @param array $additionalParameters Extra parameters for the mail() command
	 * @param boolean $fakeSending If set fake sending a mail
	 * @throws \TYPO3\CMS\Core\Exception
	 * @return bool
	 */
	public function mail($to, $subject, $messageBody, $additionalHeaders = NULL, $additionalParameters = NULL, $fakeSending = FALSE) {
		// report success for fake sending
		if ($fakeSending === TRUE) {
			return TRUE;
		}
		$this->message->setSubject($subject);
		// handle recipients
		$toAddresses = $this->parseAddresses($to);
		$this->message->setTo($toAddresses);
		// handle additional headers
		$headers = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(LF, $additionalHeaders, TRUE);
		$this->messageHeaders = $this->message->getHeaders();
		foreach ($headers as $header) {
			list($headerName, $headerValue) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $header, FALSE, 2);
			$this->setHeader($headerName, $headerValue);
		}
		// handle additional parameters (force return path)
		if (preg_match('/-f\\s*(\\S*?)/', $additionalParameters, $matches)) {
			$this->message->setReturnPath($this->unescapeShellArguments($matches[1]));
		}
		// handle from:
		$this->fixSender();
		// handle message body
		$this->setBody($messageBody);
		// send mail
		$result = $this->mailer->send($this->message);
		// report success/failure
		return (bool) $result;
	}

	/**
	 * Tries to undo the action by escapeshellarg()
	 *
	 * @param string $escapedString String escaped by escapeshellarg()
	 * @return string String with escapeshellarg() action undone as best as possible
	 */
	protected function unescapeShellArguments($escapedString) {
		if (TYPO3_OS === 'WIN') {
			// on Windows double quotes are used and % signs are replaced by spaces
			if (preg_match('/^"([^"]*)"$/', trim($escapedString), $matches)) {
				$result = str_replace('\\"', '"', $matches[1]);
			}
		} else {
			// on Unix-like systems single quotes are escaped
			if (preg_match('/^\'([^' . preg_quote('\'') . ']*)\'$/', trim($escapedString), $matches)) {
				$result = str_replace('\\\'', '\'', $matches[1]);
			}
		}
		return $result;
	}

	/**
	 * Handles setting and replacing of mail headers
	 *
	 * @param string $headerName Name of header
	 * @param string $headerValue Value of header
	 * @return void
	 */
	protected function setHeader($headerName, $headerValue) {
		// check for boundary in headers
		if (preg_match('/^boundary="(.*)"$/', $headerName, $matches) > 0) {
			$this->boundary = $matches[1];
			return;
		}

		// Ignore empty header-values (like from an 'Reply-To:' without an email-address)
		$headerValue = trim($headerValue);
		if (empty($headerValue)) {
			return;
		}

		// process other, real headers
		if ($this->messageHeaders->has($headerName)) {
			$header = $this->messageHeaders->get($headerName);
			$headerType = $header->getFieldType();
			switch ($headerType) {
				case \Swift_Mime_Header::TYPE_TEXT:
					$header->setValue($headerValue);
					break;
				case \Swift_Mime_Header::TYPE_PARAMETERIZED:
					$header->setValue(rtrim($headerValue, ';'));
					break;
				case \Swift_Mime_Header::TYPE_MAILBOX:
					$addressList = $this->parseAddresses($headerValue);
					if (count($addressList) > 0) {
						$header->setNameAddresses($addressList);
					}
					break;
				case \Swift_Mime_Header::TYPE_DATE:
					$header->setTimeStamp(strtotime($headerValue));
					break;
				case \Swift_Mime_Header::TYPE_ID:
					// remove '<' and '>' from ID headers
					$header->setId(trim($headerValue, '<>'));
					break;
				case \Swift_Mime_Header::TYPE_PATH:
					$header->setAddress($headerValue);
					break;
			}
		} else {
			switch ($headerName) {
				case 'From':
				case 'To':
				case 'Cc':
				case 'Bcc':
				case 'Reply-To':
				case 'Sender':
					$addressList = $this->parseAddresses($headerValue);
					if (count($addressList) > 0) {
						$this->messageHeaders->addMailboxHeader($headerName, $addressList);
					}
					break;
				case 'Date':
					$this->messageHeaders->addDateHeader($headerName, strtotime($headerValue));
					break;
				case 'Message-ID':
					// remove '<' and '>' from ID headers
					$this->messageHeaders->addIdHeader($headerName, trim($headerValue, '<>'));
				case 'Return-Path':
					$this->messageHeaders->addPathHeader($headerName, $headerValue);
					break;
				case 'Content-Type':
				case 'Content-Disposition':
					$this->messageHeaders->addParameterizedHeader($headerName, rtrim($headerValue, ';'));
					break;
				default:
					$this->messageHeaders->addTextheader($headerName, $headerValue);
					break;
			}
		}
	}

	/**
	 * Sets body of mail message. Handles multi-part and single part messages. Encoded body parts are decoded prior to adding
	 * them to the message object.
	 *
	 * @param string $body Raw body, may be multi-part
	 * @return void
	 */
	protected function setBody($body) {
		if ($this->boundary) {
			// handle multi-part
			$bodyParts = preg_split('/--' . preg_quote($this->boundary, '/') . '(--)?/m', $body, NULL, PREG_SPLIT_NO_EMPTY);
			foreach ($bodyParts as $bodyPart) {
				// skip empty parts
				if (trim($bodyPart) == '') {
					continue;
				}
				// keep leading white space when exploding the text
				$lines = explode(LF, $bodyPart);
				// set defaults for this part
				$encoding = '';
				$charset = 'utf-8';
				$contentType = 'text/plain';
				// skip intro messages
				if (trim($lines[0]) == 'This is a multi-part message in MIME format.') {
					continue;
				}
				// first line is empty leftover from splitting
				array_shift($lines);
				while (count($lines) > 0) {
					$line = array_shift($lines);
					if (preg_match('/^content-type:(.*);( charset=(.*))?$/i', $line, $matches)) {
						$contentType = trim($matches[1]);
						if ($matches[2]) {
							$charset = trim($matches[3]);
						}
					} elseif (preg_match('/^content-transfer-encoding:(.*)$/i', $line, $matches)) {
						$encoding = trim($matches[1]);
					} elseif (strlen(trim($line)) == 0) {
						// empty line before actual content of this part
						break;
					}
				}
				// use rest of part as body, but reverse encoding first
				$bodyPart = $this->decode(implode(LF, $lines), $encoding);
				$this->message->addPart($bodyPart, $contentType, $charset);
			}
		} else {
			// Handle single body
			// The headers have already been set, so use header information
			$contentType = $this->message->getContentType();
			$charset = $this->message->getCharset();
			$encoding = $this->message->getEncoder()->getName();
			// reverse encoding and set body
			$rawBody = $this->decode($body, $encoding);
			$this->message->setBody($rawBody, $contentType, $charset);
		}
	}

	/**
	 * Reverts encoding of body text
	 *
	 * @param string $text Body text to be decoded
	 * @param string $encoding Encoding type to be reverted
	 * @return string Decoded message body
	 */
	protected function decode($text, $encoding) {
		$result = $text;
		switch ($encoding) {
			case 'quoted-printable':
				$result = quoted_printable_decode($text);
				break;
			case 'base64':
				$result = base64_decode($text);
				break;
		}
		return $result;
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
		/** @var $addressParser \TYPO3\CMS\Core\Mail\Rfc822AddressesParser */
		$addressParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\Rfc822AddressesParser', $rawAddresses);
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
	 * Makes sure there is a correct sender set.
	 *
	 * If there is no from header the returnpath will be used. If that also fails a fake address will be used to make sure
	 * Swift Mailer will be able to send the message. Some SMTP server will not accept mail messages without a valid sender.
	 *
	 * @return void
	 */
	protected function fixSender() {
		$from = $this->message->getFrom();
		if (count($from) > 0) {
			reset($from);
			list($fromAddress, $fromName) = each($from);
		} else {
			$fromAddress = $this->message->getReturnPath();
			$fromName = $fromAddress;
		}
		if (strlen($fromAddress) == 0) {
			$fromAddress = 'no-reply@example.org';
			$fromName = 'TYPO3 CMS';
		}
		$this->message->setFrom(array($fromAddress => $fromName));
	}

}

?>
