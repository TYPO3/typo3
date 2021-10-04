<?php

/*
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

namespace TYPO3\CMS\Core\Log\Processor;

use TYPO3\CMS\Core\Log\LogRecord;

/**
 * Introspection processor to automatically add where the log record came from.
 */
class IntrospectionProcessor extends AbstractProcessor
{
    /**
     * Add the full backtrace to the log entry or
     * just the last entry of the backtrace
     *
     * @var bool
     */
    protected $appendFullBackTrace = false;

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
     * @return IntrospectionProcessor
     */
    public function setShiftBackTraceLevel($shiftBackTraceLevel)
    {
        $this->shiftBackTraceLevel = (int)$shiftBackTraceLevel;
        return $this;
    }

    /**
     * Set if the full backtrace should be added to the log or just the last item
     *
     * @param bool $appendFullBackTrace If the full backtrace should be added
     * @return IntrospectionProcessor
     */
    public function setAppendFullBackTrace($appendFullBackTrace)
    {
        $this->appendFullBackTrace = (bool)$appendFullBackTrace;
        return $this;
    }

    /**
     * Add debug backtrace information to logRecord
     * It adds: filepath, line number, class and function name
     *
     * @param LogRecord $logRecord The log record to process
     * @return LogRecord The processed log record with additional data
     * @see debug_backtrace()
     */
    public function processLogRecord(LogRecord $logRecord)
    {
        $trace = $this->getDebugBacktrace();

        // skip TYPO3\CMS\Core\Log classes
        foreach ($trace as $traceEntry) {
            if (isset($traceEntry['class']) && str_contains($traceEntry['class'], 'TYPO3\\CMS\\Core\\Log')) {
                $trace = $this->shiftBacktraceLevel($trace);
            } else {
                break;
            }
        }

        // shift a given number of entries from the trace
        for ($i = 0; $i < $this->shiftBackTraceLevel; $i++) {
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
                $trace[0] = ['line' => $this->precedingBacktraceLine] + $trace[0];
            }
            if (!isset($trace[0]['file'])) {
                $trace[0] = ['file' => $this->precedingBacktraceFile] + $trace[0];
            }

            $logRecord->addData([
                'backtrace' => $trace,
            ]);
        } else {
            $logRecord->addData([
                'file' => $trace[0]['file'] ?? null,
                'line' => $trace[0]['line'] ?? null,
                'class' => $trace[0]['class'] ?? null,
                'function' => $trace[0]['function'] ?? null,
            ]);
        }

        return $logRecord;
    }

    /**
     * Shift the first item from the backtrace
     *
     * @param array $backtrace
     * @return array
     */
    protected function shiftBacktraceLevel(array $backtrace)
    {
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
    protected function getDebugBacktrace()
    {
        return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    }
}
