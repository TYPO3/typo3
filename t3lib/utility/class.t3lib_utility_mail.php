<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010 Tolleiv Nietsch <nietsch@aoemedia.de>
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
 * $Id: class.t3lib_utility_mail.php 6536 2009-11-25 14:07:18Z stucki $
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
	 * @param	string 		Additional headers for the mail (see PHP function mail())
	 * @param	string		Additional flags for the sending mail tool (see PHP function mail())
	 * @return	boolean		Indicates whether the mail has been sent or not
	 * @see		PHP function mail() []
	 * @link	http://www.php.net/manual/en/function.mail.php
	 */
	public static function mail($to, $subject, $messageBody, $additionalHeaders = null, $additionalParameters = null) {
		$success = TRUE;
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery'])) {
			$parameters = array(
				'to' => $to,
				'subject' => $subject,
				'messageBody' => $messageBody,
				'additionalHeaders' => $additionalHeaders,
				'additionalParameters' => $additionalParameters,
			);
			$fakeThis = FALSE;
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery'] as $hookMethod) {
				$success = $success && t3lib_div::callUserFunction($hookMethod, $parameters, $fakeThis);
			}
		} else {
			$success = @mail($to, $subject, $messageBody, $additionalHeaders, $additionalParameters);
		}

		if (!$success) {
			t3lib_div::sysLog('Mail to "' . $email . '" could not be sent (Subject: "' . $subject . '").', 'Core', 3);
		}
		return $success;
	}
}
?>
