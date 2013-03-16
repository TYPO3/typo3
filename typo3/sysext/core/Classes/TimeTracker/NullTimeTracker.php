<?php
namespace TYPO3\CMS\Core\TimeTracker;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Ingo Renner <ingo@typo3.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A fake time tracker that does nothing but providing the methods of the real time tracker.
 * This is done to save some performance over the real time tracker.
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class NullTimeTracker {

	/**
	 * "Constructor"
	 * Sets the starting time
	 *
	 * does nothing
	 *
	 * @return void
	 */
	public function start() {

	}

	/**
	 * Pushes an element to the TypoScript tracking array
	 *
	 * does nothing
	 *
	 * @param string $tslabel Label string for the entry, eg. TypoScript property name
	 * @param string $value Additional value(?)
	 * @return void
	 */
	public function push($tslabel, $value = '') {

	}

	/**
	 * Pulls an element from the TypoScript tracking array
	 *
	 * does nothing
	 *
	 * @param string $content The content string generated within the push/pull part.
	 * @return void
	 */
	public function pull($content = '') {

	}

	/**
	 * Set TSselectQuery - for messages in TypoScript debugger.
	 *
	 * does nothing
	 *
	 * @param array $data Query array
	 * @param string $msg Message/Label to attach
	 * @return void
	 */
	public function setTSselectQuery(array $data, $msg = '') {

	}

	/**
	 * Logs the TypoScript entry
	 *
	 * does nothing
	 *
	 * @param string $content The message string
	 * @param integer $num Message type: 0: information, 1: message, 2: warning, 3: error
	 * @return void
	 */
	public function setTSlogMessage($content, $num = 0) {

	}

	/**
	 * Print TypoScript parsing log
	 *
	 * does nothing
	 *
	 * @return string HTML table with the information about parsing times.
	 */
	public function printTSlog() {

	}

	/**
	 * Increases the stack pointer
	 *
	 * does nothing
	 *
	 * @return void
	 */
	public function incStackPointer() {

	}

	/**
	 * Decreases the stack pointer
	 *
	 * does nothing
	 *
	 * @return void
	 */
	public function decStackPointer() {

	}

	/**
	 * Gets a microtime value as milliseconds value.
	 *
	 * @param float $microtime The microtime value - if not set the current time is used
	 * @return integer The microtime value as milliseconds value
	 */
	public function getMilliseconds($microtime = NULL) {

	}

}


?>