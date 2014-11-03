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

/**
 * Introspection processor to automatically add where the log record came from.
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class IntrospectionProcessor extends \TYPO3\CMS\Core\Log\Processor\AbstractProcessor {

	/**
	 * Add the full backtrace to the log entry or
	 * just the last entry of the backtrace
	 *
	 * @var bool
	 */
	protected $appendFullBackTrace = FALSE;

	/**
	 * Number of entries to shift from the backtrace
	 *
	 * @var int
	 */
	protected $shiftBackTraceLevel = 0;

	/**
	 * Temporary storage of the preceding backtrace line number
	 *
	 * @var string
	 */
	private $precedingBacktraceLine = '';

	/**
	 * Temporary storage of the preceding backtrace file
	 *
	 * @var string
	 */
	private $precedingBacktraceFile = '';

	/**
	 * Set the number of levels to be shift from the backtrace
	 *
	 * @param int $shiftBackTraceLevel Numbers of levels to shift
	 * @return \TYPO3\CMS\Core\Log\Writer\AbstractWriter
	 */
	public function setShiftBackTraceLevel($shiftBackTraceLevel) {
		$this->shiftBackTraceLevel = (int)$shiftBackTraceLevel;
		return $this;
	}

	/**
	 * Set if the full backtrace should be added to the log or just the last item
	 *
	 * @param bool $appendFullBackTrace If the full backtrace should be added
	 * @return \TYPO3\CMS\Core\Log\Writer\AbstractWriter
	 */
	public function setAppendFullBackTrace($appendFullBackTrace) {
		$this->appendFullBackTrace = (bool)$appendFullBackTrace;
		return $this;
	}


	/**
	 * Add debug backtrace information to logRecord
	 * It adds: filepath, line number, class and function name
	 *
	 * @param \TYPO3\CMS\Core\Log\LogRecord $logRecord The log record to process
	 * @return \TYPO3\CMS\Core\Log\LogRecord The processed log record with additional data
	 * @see debug_backtrace()
	 */
	public function processLogRecord(\TYPO3\CMS\Core\Log\LogRecord $logRecord) {
		$trace = $this->getDebugBacktrace();

		// skip TYPO3\CMS\Core\Log classes
		foreach ($trace as $traceEntry) {
			if (isset($traceEntry['class']) && FALSE !== strpos($traceEntry['class'], 'TYPO3\CMS\Core\Log')) {
				$trace = $this->shiftBacktraceLevel($trace);
			} else {
				break;
			}
		}

		// shift a given number of entries from the trace
		for($i = 0; $i < $this->shiftBackTraceLevel; $i++) {
			// shift only if afterwards there is at least one entry left after.
			if (count($trace) > 1) {
				$trace = $this->shiftBacktraceLevel($trace);
			}
		}

		if ($this->appendFullBackTrace) {
			// Add the line and file of the last entry that has these information
			// to the first backtrace entry if it does not have this information.
			// This is required in case we have shifted entries and the first entry
			// is now a call_user_func that does not contain the line and file information.
			if (!isset($trace[0]['line'])) {
				$trace[0] = array('line' => $this->precedingBacktraceLine) + $trace[0];
			}
			if (!isset($trace[0]['file'])) {
				$trace[0] = array('file' => $this->precedingBacktraceFile) + $trace[0];
			}

			$logRecord->addData(array(
				'backtrace' => $trace
			));
		} else {
			$logRecord->addData(array(
				'file' => isset($trace[0]['file']) ? $trace[0]['file'] : NULL,
				'line' => isset($trace[0]['line']) ? $trace[0]['line'] : NULL,
				'class' => isset($trace[0]['class']) ? $trace[0]['class'] : NULL,
				'function' => isset($trace[0]['function']) ? $trace[0]['function'] : NULL
			));
		}

		return $logRecord;
	}

	/**
	 * Shift the first item from the backtrace
	 *
	 * @param array $backtrace
	 * @return array
	 */
	protected function shiftBacktraceLevel(array $backtrace) {
		if (isset($backtrace[0]['file'])) {
			$this->precedingBacktraceFile = $backtrace[0]['file'];
		}
		if (isset($backtrace[0]['line'])) {
			$this->precedingBacktraceLine = $backtrace[0]['line'];
		}
		array_shift($backtrace);

		return $backtrace;
	}

	/**
	 * Get the debug backtrace
	 *
	 * @return array
	 */
	protected function getDebugBacktrace() {
		return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	}

}
