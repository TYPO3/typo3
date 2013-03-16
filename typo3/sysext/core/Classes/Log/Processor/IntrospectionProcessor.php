<?php
namespace TYPO3\CMS\Core\Log\Processor;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Ingo Renner (ingo@typo3.org)
 * (c) 2012-2013 Steffen Müller (typo3@t3node.com)
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
 * Introspection processor to automatically add where the log record came from.
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class IntrospectionProcessor extends \TYPO3\CMS\Core\Log\Processor\AbstractProcessor {

	/**
	 * Add debug backtrace information to logRecord
	 * It adds: filepath, line number, class and function name
	 *
	 * @param \TYPO3\CMS\Core\Log\LogRecord $logRecord The log record to process
	 * @return \TYPO3\CMS\Core\Log\LogRecord The processed log record with additional data
	 * @see debug_backtrace()
	 */
	public function processLogRecord(\TYPO3\CMS\Core\Log\LogRecord $logRecord) {
		$trace = debug_backtrace();
		// skip first since it's always the current method
		array_shift($trace);
		// the call_user_func call is also skipped
		array_shift($trace);
		// skip TYPO3\CMS\Core\Log classes
		// @TODO: Check, if this still works. This was 't3lib_log_' before namespace switch.
		$i = 0;
		while (isset($trace[$i]['class']) && FALSE !== strpos($trace[$i]['class'], 'TYPO3\CMS\Core\Log')) {
			$i++;
		}
		// we should have the call source now
		$logRecord->addData(array(
			'file' => isset($trace[$i]['file']) ? $trace[$i]['file'] : NULL,
			'line' => isset($trace[$i]['line']) ? $trace[$i]['line'] : NULL,
			'class' => isset($trace[$i]['class']) ? $trace[$i]['class'] : NULL,
			'function' => isset($trace[$i]['function']) ? $trace[$i]['function'] : NULL
		));
		return $logRecord;
	}

}


?>