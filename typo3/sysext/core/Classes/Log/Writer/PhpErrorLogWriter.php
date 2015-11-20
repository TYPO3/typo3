<?php
namespace TYPO3\CMS\Core\Log\Writer;

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
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;

/**
 * Log writer that writes the log records into PHP error log.
 */
class PhpErrorLogWriter extends AbstractWriter
{
    /**
     * Writes the log record
     *
     * @param LogRecord $record Log record
     * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
     * @throws \RuntimeException
     */
    public function writeLog(LogRecord $record)
    {
        $levelName = LogLevel::getName($record->getLevel());
        $data = '';
        $recordData = $record->getData();
        if (!empty($recordData)) {
            // According to PSR3 the exception-key may hold an \Exception
            // Since json_encode() does not encode an exception, we run the _toString() here
            if (isset($recordData['exception']) && $recordData['exception'] instanceof \Exception) {
                $recordData['exception'] = (string)$recordData['exception'];
            }
            $data = '- ' . json_encode($recordData);
        }
        $message = sprintf(
            'TYPO3 [%s] request="%s" component="%s": %s %s',
            $levelName,
            $record->getRequestId(),
            $record->getComponent(),
            $record->getMessage(),
            $data
        );
        if (false === error_log($message)) {
            throw new \RuntimeException('Could not write log record to PHP error log', 1345036336);
        }
        return $this;
    }
}
