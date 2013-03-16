<?php
namespace TYPO3\CMS\Core\Mail;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Ernesto Baschny <ernst@cron-it.de>
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
 * Adapter for Swift_Mailer to be used by TYPO3 extensions.
 *
 * This will use the setting in TYPO3_CONF_VARS to choose the correct transport
 * for it to work out-of-the-box.
 *
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
class MboxTransport implements \Swift_Transport {

	/**
	 * @var string The file to write our mails into
	 */
	private $debugFile;

	/**
	 * Create a new MailTransport
	 *
	 * @param string $debugFile
	 */
	public function __construct($debugFile) {
		$this->debugFile = $debugFile;
	}

	/**
	 * Not used.
	 */
	public function isStarted() {
		return FALSE;
	}

	/**
	 * Not used.
	 */
	public function start() {

	}

	/**
	 * Not used.
	 */
	public function stop() {

	}

	/**
	 * Outputs the mail to a text file according to RFC 4155.
	 *
	 * @param Swift_Mime_Message $message The message to send
	 * @param string[] &$failedRecipients To collect failures by-reference, nothing will fail in our debugging case
	 * @return int
	 * @throws \RuntimeException
	 */
	public function send(\Swift_Mime_Message $message, &$failedRecipients = NULL) {
		$message->generateId();
		// Create a mbox-like header
		$mboxFrom = $this->getReversePath($message);
		$mboxDate = strftime('%c', $message->getDate());
		$messageStr = sprintf('From %s  %s', $mboxFrom, $mboxDate) . LF;
		// Add the complete mail inclusive headers
		$messageStr .= $message->toString();
		$messageStr .= LF . LF;
		$lockObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Locking\\Locker', $this->debugFile, $GLOBALS['TYPO3_CONF_VARS']['SYS']['lockingMode']);
		/** @var \TYPO3\CMS\Core\Locking\Locker $lockObject */
		$lockObject->acquire();
		// Write the mbox file
		$file = @fopen($this->debugFile, 'a');
		if (!$file) {
			$lockObject->release();
			throw new \RuntimeException(sprintf('Could not write to file "%s" when sending an email to debug transport', $this->debugFile), 1291064151);
		}
		@fwrite($file, $messageStr);
		@fclose($file);
		\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($this->debugFile);
		$lockObject->release();
		// Return every receipient as "delivered"
		$count = count((array) $message->getTo()) + count((array) $message->getCc()) + count((array) $message->getBcc());
		return $count;
	}

	/**
	 * Determine the best-use reverse path for this message
	 *
	 * @param Swift_Mime_Message $message
	 * @return mixed|NULL
	 */
	private function getReversePath(\Swift_Mime_Message $message) {
		$return = $message->getReturnPath();
		$sender = $message->getSender();
		$from = $message->getFrom();
		$path = NULL;
		if (!empty($return)) {
			$path = $return;
		} elseif (!empty($sender)) {
			$keys = array_keys($sender);
			$path = array_shift($keys);
		} elseif (!empty($from)) {
			$keys = array_keys($from);
			$path = array_shift($keys);
		}
		return $path;
	}

	/**
	 * Register a plugin in the Transport.
	 *
	 * @param Swift_Events_EventListener $plugin
	 */
	public function registerPlugin(\Swift_Events_EventListener $plugin) {
		return TRUE;
	}

}


?>