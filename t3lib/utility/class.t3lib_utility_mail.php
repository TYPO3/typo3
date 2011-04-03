<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2011 Tolleiv Nietsch <nietsch@aoemedia.de>
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

/**
 * Class to handle mail specific functionality
 *
 *
 * @author	 Tolleiv Nietsch <nietsch@aoemedia.de>
 * @package TYPO3
 * @subpackage t3lib
 */
final class t3lib_utility_Mail {

	/**
	 * Proxy for the PHP mail() function. Adds possibility to hook in and send the mails in a different way.
	 * The hook can be used by adding function to the configuration array:
	 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery']
	 *
	 * @param	string		Email address to send to.
	 * @param	string		Subject line, non-encoded. (see PHP function mail())
	 * @param	string		Message content, non-encoded. (see PHP function mail())
	 * @param	string		 Additional headers for the mail (see PHP function mail())
	 * @param	string		Additional flags for the sending mail tool (see PHP function mail())
	 * @return	boolean		Indicates whether the mail has been sent or not
	 * @see		PHP function mail() []
	 * @link	http://www.php.net/manual/en/function.mail.php
	 */
	public static function mail($to, $subject, $messageBody, $additionalHeaders = NULL, $additionalParameters = NULL) {
		$success = TRUE;

			// If the mail does not have a From: header, fall back to the default in TYPO3_CONF_VARS.
		if (!preg_match('/^From:/im', $additionalHeaders) && $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']) {
			if (!is_null($additionalHeaders) && substr($additionalHeaders, -1) != LF) {
				$additionalHeaders .= LF;
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']) {
				$additionalHeaders .= 'From: "' . $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
						. '" <' . $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] . '>';
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
				'additionalParameters' => $additionalParameters,
			);
			$fakeThis = FALSE;
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery'] as $hookSubscriber) {
				$hookSubscriberContainsArrow = strpos($hookSubscriber, '->');

				if ($hookSubscriberContainsArrow !== FALSE) {
						// deprecated, remove in TYPO3 4.7
					t3lib_div::deprecationLog(
						'The usage of user function notation for the substituteMailDelivery hook is deprecated,
						use the t3lib_mail_MailerAdapter interface instead.'
					);
					$success = $success && t3lib_div::callUserFunction($hookSubscriber, $parameters, $fakeThis);
				} else {
					$mailerAdapter = t3lib_div::makeInstance($hookSubscriber);
					if ($mailerAdapter instanceof t3lib_mail_MailerAdapter) {
						$success = $success && $mailerAdapter->mail($to, $subject, $messageBody, $additionalHeaders, $additionalParameters, $fakeThis);
					} else {
						throw new RuntimeException(
							$hookSubscriber . ' is not an implementation of t3lib_mail_MailerAdapter,
							but must implement that interface to be used in the substituteMailDelivery hook.',
							1294062286
						);
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
			t3lib_div::sysLog('Mail to "' . $to . '" could not be sent (Subject: "' . $subject . '").', 'Core', 3);
		}
		return $success;
	}

	/**
	 * Gets a valid "from" for mail messages (email and name).
	 *
	 * Ready to be passed to $mail->setFrom() (t3lib_mail)
	 *
	 * @return array key=Valid email address which can be used as sender, value=Valid name which can be used as a sender. NULL if no address is configured
	 */
	public static function getSystemFrom() {
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
	public static function getSystemFromName() {
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
	 *     $TYPO3_CONF_VARS['MAIL']['defaultMailFromAddress'] ->
	 *     no-reply@FirstDomainRecordFound ->
	 *     no-reply@php_uname('n') ->
	 *     no-reply@example.com
	 *
	 * Ready to be passed to $mail->setFrom() (t3lib_mail)
	 *
	 * @return	string	An email address
	 */
	public static function getSystemFromAddress() {
			// default, first check the localconf setting
		$address = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];

		if (!t3lib_div::validEmail($address)) {
				// just get us a domain record we can use as the host
			$host = '';
			$domainRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'domainName',
				'sys_domain',
				'hidden = 0',
				'',
				'pid ASC, sorting ASC'
			);

			if (!empty($domainRecord['domainName'])) {
				$tempUrl = $domainRecord['domainName'];

				if (!t3lib_div::isFirstPartOfStr($tempUrl, 'http')) {
						// shouldn't be the case anyways, but you never know
						// ... there're crazy people out there
					$tempUrl = 'http://' .$tempUrl;
				}
				$host = parse_url($tempUrl, PHP_URL_HOST);
			}

			$address = 'no-reply@' . $host;

			if (!t3lib_div::validEmail($address)) {
					// still nothing, get host name from server
				$address = 'no-reply@' . php_uname('n');

				if (!t3lib_div::validEmail($address)) {
						// if everything fails use a dummy address
					$address = 'no-reply@example.com';
				}
			}
		}

		return $address;
	}
}

?>