<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2012 Ingo Renner (ingo@typo3.org)
 * (c) 2012 Steffen Müller (typo3@t3node.com)
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
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Web log processor to automatically add web request related data to a log
 * record.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Steffen Müller <typo3@t3node.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_log_processor_Web extends t3lib_log_processor_Abstract {

	/**
	 * Processes a log record and adds webserver environment data.
	 * We use the usual "Debug System Information"
	 *
	 * @param t3lib_log_Record $logRecord The log record to process
	 * @return t3lib_log_Record The processed log record with additional data
	 * @see t3lib_div::getIndpEnv()
	 */
	public function processLogRecord(t3lib_log_Record $logRecord) {
		$logRecord->addData(t3lib_div::getIndpEnv('_ARRAY'));

		return $logRecord;
	}

}

?>