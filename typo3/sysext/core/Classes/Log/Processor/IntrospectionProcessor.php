<?php
namespace TYPO3\CMS\Core\Log\Processor;

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

use TYPO3\CMS\Core\Log\LogRecord;

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
	 * @param LogRecord $logRecord The log record to process
	 * @return LogRecord The processed log record with additional data
	 * @see debug_backtrace()
	 */
	public function processLogRecord(LogRecord $logRecord) {
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
