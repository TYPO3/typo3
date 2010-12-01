<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Jigal van Hemert <jigal@xs4all.nl>
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
 * Hook subscriber for using Swift Mailer with the t3lib_utility_mail function
 *
 * $Id$
 *
 * @author	Jigal van Hemert <jigal@xs4all.nl>
 * @package TYPO3
 * @subpackage t3lib
 */
class tx_t3lib_mail_hooks {

	/** @var $mailerObject t3lib_mail_Mailer */
	protected $mailerObject;

	/** @var $messageObject Swift_Message */
	protected $messageObject;

	/** @var $messageHeaders Swift_Mime_HeaderSet */
	protected $messageHeaders;

	/**
	 * @param array $parameters Array with keys: 'to', 'subject', 'messageBody', 'additionalHeaders', 'additionalParameters'
	 * @param bool $fakeSending If set fake sending a mail
	 * @throws t3lib_exception
	 * @return bool
	 */
	public function sendMail(array $parameters = array(), $fakeSending = FALSE) {

			// report success for fake sending
		if ($fakeSending === TRUE) {
			return TRUE;
		}
			// create mailer object
		$this->mailerObject = t3lib_div::makeInstance('t3lib_mail_Mailer');

			// create message object
		$this->messageObject = Swift_Message::newInstance($parameters['subject'], $parameters['messageBody']);
		$this->messageObject->setTo($parameters['to']);
			// handle additional headers
		$headers = t3lib_div::trimExplode(LF, $parameters['additionalHeaders'], TRUE);
		$this->messageHeaders = $this->messageObject->getHeaders();
		foreach ($headers as $header) {
			list($headerName, $headerValue) = t3lib_div::trimExplode(':', $header, FALSE, 2);
			$this->setHeader($headerName, $headerValue);
		}
			// handle additional parameters (force return path)
		if (preg_match('/-f\s*(\S*?)/', $parameters['additionalParameters'], $matches)) {
			$this->messageObject->setReturnPath($this->unEscapeShellArg($matches[1]));
		}
			// handle from:
		$from = $this->messageObject->getFrom();
		if (count($from) > 0) {
			reset($from);
			list($fromAddress, $fromName) = each($from);
		} else {
			$fromAddress = $this->messageObject->getReturnPath();
			$fromName = $fromAddress;
		}
		if (strlen($fromAddress) == 0) {
			$fromAddress = 'no-reply@example.org';
			$fromName = 'TYPO3 Installation';
		}
		$this->messageObject->setFrom(array($fromAddress => $fromName));
			// send mail
		$result = $this->mailerObject->send($this->messageObject);

			// report success/failure
		return (bool) $result;
	}

	/**
	 * Tries to undo the action by escapeshellarg()
	 *
	 * @param  $escapedString String escaped by escapeshellarg()
	 * @return string	String with escapeshellarg() action undone as best as possible
	 */
	protected function unEscapeShellArg($escapedString) {
		if (TYPO3_OS === 'WIN') {
				// on Windows double quotes are used and % signs are replaced by spaces
			if (preg_match('/^"([^"]*)"$/', trim($escapedString), $matches)) {
				$result = str_replace('\"', '"', $matches[1]);
					// % signs are replaced with spaces, so they can't be recovered
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
	 * @param  $headerName Name of header
	 * @param  $headerValue Value of header
	 * @return void
	 */
	protected function setHeader($headerName, $headerValue) {
		if ($this->messageHeaders->has($headerName)) {
			$header = $this->messageHeaders->get($headerName);
			$headerType = $header->getFieldType();
			switch ($headerType) {
				case Swift_Mime_Header::TYPE_TEXT:
					$header->setValue($headerValue);
					break;
				case Swift_Mime_Header::TYPE_PARAMETERIZED:
					$header->setValue($headerValue);
					break;
				case Swift_Mime_Header::TYPE_MAILBOX:
						// mailbox headers look like:
						// name <email@example.org>, othermail@example.org, ...
						// pattern matches cases with and without name
						// comma is added to match each item in a comma separated list
					preg_match_all('/,\s*([^<]+)(<([^>]*?)>)?/', ', ' . $headerValue, $addresses, PREG_SET_ORDER);
					$addressList = array();
					foreach ($addresses as $address) {
						if (!$address[2]) {
								// item with name found ( name <email@example.org> )
							if (t3lib_div::validEmail($address[1])) {
								$addressList[] = $address[1];
							}
						} else {
								// item without name found ( email@example.org )
							if (t3lib_div::validEmail($address[3])) {
								$addressList[$address[3]] = $address[1];
							}
						}
					}
					if (count($addressList) > 0) {
						$header->setNameAddresses($addressList);
					}
					break;
				case Swift_Mime_Header::TYPE_DATE:
					$header->setTimeStamp(strtotime($headerValue));
					break;
				case Swift_Mime_Header::TYPE_ID:
						// remove '<' and '>' from ID headers
					$header->setId(trim($headerValue, '<>'));
					break;
				case Swift_Mime_Header::TYPE_PATH:
					$header->setAddress($headerValue);
					break;
			}
				// change value
		} else {
			switch ($headerName) {
					// mailbox headers
				case 'From':
				case 'To':
				case 'Cc':
				case 'Bcc':
				case 'Reply-To':
				case 'Sender':
						// mailbox headers look like:
						// name <email@example.org>, othermail@example.org, ...
						// pattern matches cases with and without name
						// comma is added to match each item in a comma separated list
					preg_match_all('/,\s*(.*?)(<([^>]*?)>)?/', ', ' . $headerValue, $addresses, PREG_SET_ORDER);
					$addressList = array();
					foreach ($addresses as $address) {
						if ($address[2]) {
								// item with name found ( name <email@example.org> )
							if (t3lib_div::validEmail($address[1])) {
								$addressList[] = $address[1];
							}
						} else {
								// item without name found ( email@example.org )
							if (t3lib_div::validEmail($address[3])) {
								$addressList[$address[3]] = $address[1];
							}
						}
					}
					if (count($addressList) > 0) {
						$header->addMailboxHeader($headerName, $addressList);
					}
					break;
					// date headers
				case 'Date':
					$this->messageHeaders->addDateHeader($headerName, strtotime($headerValue));
					break;
					// ID headers
				case 'Message-ID':
						// remove '<' and '>' from ID headers
					$this->messageHeaders->addIdHeader($headerName, trim($headerValue, '<>'));
					// path headers
				case 'Return-Path':
					$this->messageHeaders->addPathHeader($headerName, $headerValue);
					break;
					// parameterized headers
				case 'Content-Type':
				case 'Content-Disposition':
					$this->messageHeaders->addParameterizedHeader($headerName, $headerValue);
					break;
					// text headers
				default:
					$this->messageHeaders->addTextheader($headerName, $headerValue);
					break;
			}
		}
	}
}
