<?php
namespace TYPO3\CMS\Core\Mail;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Ingo Renner <ingo@typo3.org>
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
 * Mailer Adapter interface
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
interface MailerAdapterInterface {
	/**
	 * Mail sending function
	 *
	 * @param string $to Mail recipient.
	 * @param string $subject Mail subject.
	 * @param string $messageBody Mail body.
	 * @param array $additionalHeaders Additional mail headers.
	 * @param array $additionalParameters Additional mailer parameters.
	 * @param boolean $fakeSending Whether to fake sending or not, used in Unit Tests.
	 * @return boolean TRUE if the mail was successfully sent, FALSE otherwise.
	 */
	public function mail($to, $subject, $messageBody, $additionalHeaders = NULL, $additionalParameters = NULL, $fakeSending = FALSE);

}

?>