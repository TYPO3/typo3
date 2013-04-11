<?php
namespace TYPO3\CMS\Core\Messaging;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Benjamin Mack <benni@typo3.org>
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
 * A class representing error messages shown on a page.
 * Classic Example: "No pages are found on rootlevel"
 *
 * @author Benjamin Mack <benni@typo3.org>
 */
class ErrorpageMessage extends \TYPO3\CMS\Core\Messaging\AbstractStandaloneMessage {

	/**
	 * Constructor for an Error message
	 *
	 * @param string $message The error message
	 * @param string $title Title of the message, can be empty
	 * @param integer $severity Optional severity, must be either of AbstractMessage::INFO or related constants
	 */
	public function __construct($message = '', $title = '', $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR) {
		$this->setHtmlTemplate(TYPO3_mainDir . 'sysext/t3skin/templates/errorpage-message.html');
		parent::__construct($message, $title, $severity);
	}

}


?>