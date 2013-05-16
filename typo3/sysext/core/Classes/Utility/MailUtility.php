<?php
namespace TYPO3\CMS\Core\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Tolleiv Nietsch <nietsch@aoemedia.de>
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
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class to handle mail specific functionality
 *
 * @author Tolleiv Nietsch <nietsch@aoemedia.de>
 */
class MailUtility {

	/**
	 * Proxy for the PHP mail() function. Adds possibility to hook in and send the mails in a different way.
	 * The hook can be used by adding function to the configuration array:
	 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery']
	 *
	 * @param string $to Email address to send to.
	 * @param string $subject Subject line, non-encoded. (see PHP function mail())
	 * @param string $messageBody Message content, non-encoded. (see PHP function mail())
	 * @param string $additionalHeaders Additional headers for the mail (see PHP function mail())
	 * @param string $additionalParameters Additional flags for the sending mail tool (see PHP function mail())
	 * @return boolean Indicates whether the mail has been sent or not
	 * @see PHP function mail() []
	 * @link http://www.php.net/manual/en/function.mail.php
	 * @deprecated since 6.1, will be removed two versions later - Use \TYPO3\CMS\Core\Mail\Mailer instead
	 */
	static public function mail($to, $subject, $messageBody, $additionalHeaders = NULL, $additionalParameters = NULL) {
		GeneralUtility::logDeprecatedFunction();
		$success = TRUE;
		// If the mail does not have a From: header, fall back to the default in TYPO3_CONF_VARS.
		if (!preg_match('/^From:/im', $additionalHeaders) && $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']) {
			if (!is_null($additionalHeaders) && substr($additionalHeaders, -1) != LF) {
				$additionalHeaders .= LF;
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']) {
				$additionalHeaders .= 'From: "' . $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] . '" <' . $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] . '>';
			} else {
				$additionalHeaders .= 'From: ' . $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery'])) {
			$parameters = array(
				'to' => $to,
				'subject' => $subject,
				'messageBody' => $messageBody,
				'additionalHeaders' => $additionalHeaders,
				'additionalParameters' => $additionalParameters
			);
			$fakeThis = FALSE;
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery'] as $hookSubscriber) {
				$hookSubscriberContainsArrow = strpos($hookSubscriber, '->');
				if ($hookSubscriberContainsArrow !== FALSE) {
					throw new \RuntimeException($hookSubscriber . ' is an invalid hook implementation. Please consider using an implementation of TYPO3\\CMS\\Core\\Mail\\MailerAdapter.', 1322287600);
				} else {
					$mailerAdapter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($hookSubscriber);
					if ($mailerAdapter instanceof \TYPO3\CMS\Core\Mail\MailerAdapterInterface) {
						$success = $success && $mailerAdapter->mail($to, $subject, $messageBody, $additionalHeaders, $additionalParameters, $fakeThis);
					} else {
						throw new \RuntimeException($hookSubscriber . ' is not an implementation of TYPO3\\CMS\\Core\\Mail\\MailerAdapter,
							but must implement that interface to be used in the substituteMailDelivery hook.', 1294062286);
					}
				}
			}
		} else {
			if (is_null($additionalParameters)) {
				$success = @mail($to, $subject, $messageBody, $additionalHeaders);
			} else {
				$success = @mail($to, $subject, $messageBody, $additionalHeaders, $additionalParameters);
			}
		}
		if (!$success) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('Mail to "' . $to . '" could not be sent (Subject: "' . $subject . '").', 'Core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}
		return $success;
	}

	/**
	 * Gets a valid "from" for mail messages (email and name).
	 *
	 * Ready to be passed to $mail->setFrom()
	 *
	 * @return array key=Valid email address which can be used as sender, value=Valid name which can be used as a sender. NULL if no address is configured
	 */
	static public function getSystemFrom() {
		$address = self::getSystemFromAddress();
		$name = self::getSystemFromName();
		if (!$address) {
			return NULL;
		} elseif ($name) {
			return array($address => $name);
		} else {
			return array($address);
		}
	}

	/**
	 * Creates a valid "from" name for mail messages.
	 *
	 * As configured in Install Tool.
	 *
	 * @return string The name (unquoted, unformatted). NULL if none is set
	 */
	static public function getSystemFromName() {
		if ($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']) {
			return $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
		} else {
			return NULL;
		}
	}

	/**
	 * Creates a valid email address for the sender of mail messages.
	 *
	 * Uses a fallback chain:
	 * $TYPO3_CONF_VARS['MAIL']['defaultMailFromAddress'] ->
	 * no-reply@FirstDomainRecordFound ->
	 * no-reply@php_uname('n') ->
	 * no-reply@example.com
	 *
	 * Ready to be passed to $mail->setFrom()
	 *
	 * @return string An email address
	 */
	static public function getSystemFromAddress() {
		// default, first check the localconf setting
		$address = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($address)) {
			// just get us a domain record we can use as the host
			$host = '';
			$domainRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('domainName', 'sys_domain', 'hidden = 0', '', 'pid ASC, sorting ASC');
			if (!empty($domainRecord['domainName'])) {
				$tempUrl = $domainRecord['domainName'];
				if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($tempUrl, 'http')) {
					// shouldn't be the case anyways, but you never know
					// ... there're crazy people out there
					$tempUrl = 'http://' . $tempUrl;
				}
				$host = parse_url($tempUrl, PHP_URL_HOST);
			}
			$address = 'no-reply@' . $host;
			if (!\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($address)) {
				// still nothing, get host name from server
				$address = 'no-reply@' . php_uname('n');
				if (!\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($address)) {
					// if everything fails use a dummy address
					$address = 'no-reply@example.com';
				}
			}
		}
		return $address;
	}

	/**
	 * Breaks up a single line of text for emails
	 *
	 * @param string $str The string to break up
	 * @param string $newlineChar The string to implode the broken lines with (default/typically \n)
	 * @param integer $lineWidth The line width
	 * @return string Reformated text
	 */
	static public function breakLinesForEmail($str, $newlineChar = LF, $lineWidth = 76) {
		$lines = array();
		$substrStart = 0;
		while (strlen($str) > $substrStart) {
			$substr = substr($str, $substrStart, $lineWidth);
			// has line exceeded (reached) the maximum width?
			if (strlen($substr) == $lineWidth) {
				// find last space-char
				$spacePos = strrpos(rtrim($substr), ' ');
				// space-char found?
				if ($spacePos !== FALSE) {
					// take everything up to last space-char
					$theLine = substr($substr, 0, $spacePos);
				} else {
					// search for space-char in remaining text
					// makes this line longer than $lineWidth!
					$afterParts = explode(' ', substr($str, $lineWidth + $substrStart), 2);
					$theLine = $substr . $afterParts[0];
				}
				if (!strlen($theLine)) {
					// prevent endless loop because of empty line
					break;
				}
			} else {
				$theLine = $substr;
			}
			$lines[] = trim($theLine);
			$substrStart += strlen($theLine);
			if (trim(substr($str, $substrStart, $lineWidth)) === '') {
				// no more text
				break;
			}
		}
		return implode($newlineChar, $lines);
	}

	/**
	 * Parses mailbox headers and turns them into an array.
	 *
	 * Mailbox headers are a comma separated list of 'name <email@example.org>' combinations
	 * or plain email addresses (or a mix of these).
	 * The resulting array has key-value pairs where the key is either a number
	 * (no display name in the mailbox header) and the value is the email address,
	 * or the key is the email address and the value is the display name.
	 *
	 * @param string $rawAddresses Comma separated list of email addresses (optionally with display name)
	 * @return array Parsed list of addresses.
	 */
	static public function parseAddresses($rawAddresses) {
		/** @var $addressParser \TYPO3\CMS\Core\Mail\Rfc822AddressesParser */
		$addressParser = GeneralUtility::makeInstance(
				'TYPO3\\CMS\\Core\\Mail\\Rfc822AddressesParser',
				$rawAddresses
		);
		$addresses = $addressParser->parseAddressList();
		$addressList = array();
		foreach ($addresses as $address) {
			if ($address->mailbox === '') {
				continue;
			}
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
}

?>