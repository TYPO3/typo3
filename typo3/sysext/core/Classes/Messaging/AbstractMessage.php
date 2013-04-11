<?php
namespace TYPO3\CMS\Core\Messaging;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
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
 * A class used for any kind of messages.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Benjamin Mack <benni@typo3.org>
 */
abstract class AbstractMessage {

	const NOTICE = -2;
	const INFO = -1;
	const OK = 0;
	const WARNING = 1;
	const ERROR = 2;
	/**
	 * The message's title
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * The message
	 *
	 * @var string
	 */
	protected $message = '';

	/**
	 * The message's severity
	 *
	 * @var integer
	 */
	protected $severity = self::OK;

	/**
	 * Gets the message's title.
	 *
	 * @return string The message's title.
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the message's title
	 *
	 * @param string $title The message's title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = (string) $title;
	}

	/**
	 * Gets the message.
	 *
	 * @return string The message.
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Sets the message
	 *
	 * @param string $message The message
	 * @return void
	 */
	public function setMessage($message) {
		$this->message = (string) $message;
	}

	/**
	 * Gets the message' severity.
	 *
	 * @return integer The message' severity, must be one of AbstractMessage::INFO or similar contstants
	 */
	public function getSeverity() {
		return $this->severity;
	}

	/**
	 * Sets the message' severity
	 *
	 * @param integer $severity The severity, must be one of AbstractMessage::INFO or similar constants
	 * @return void
	 */
	public function setSeverity($severity = self::OK) {
		$this->severity = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($severity, self::NOTICE, self::ERROR, self::OK);
	}

	/**
	 * Creates a string representation of the message. Useful for command
	 * line use.
	 *
	 * @return string A string representation of the message.
	 */
	public function __toString() {
		$severities = array(
			self::INFO => 'INFO',
			self::OK => 'OK',
			self::WARNING => 'WARNING',
			self::ERROR => 'ERROR'
		);
		$title = '';
		if (!empty($this->title)) {
			$title = ' - ' . $this->title;
		}
		return $severities[$this->severity] . $title . ': ' . $this->message;
	}

}


?>