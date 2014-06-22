<?php
namespace TYPO3\CMS\Core\Messaging;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
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
