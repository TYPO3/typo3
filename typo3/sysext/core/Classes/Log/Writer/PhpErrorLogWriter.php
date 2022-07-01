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

namespace TYPO3\CMS\Core\Log\Writer;

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
        $data = '';
        $context = $record->getData();
        $message = $record->getMessage();
        if (!empty($context)) {
            // Fold an exception into the message, and string-ify it into context so it can be jsonified.
            if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
                $message .= $this->formatException($context['exception']);
                $context['exception'] = (string)$context['exception'];
            }
            $data = '- ' . json_encode($context);
        }

        $message = sprintf(
            'TYPO3 [%s] request="%s" component="%s": %s %s',
            strtoupper($record->getLevel()),
            $record->getRequestId(),
            $record->getComponent(),
            $this->interpolate($message, $context),
            $data
        );
        if (error_log($message) === false) {
            throw new \RuntimeException('Could not write log record to PHP error log', 1345036336);
        }
        return $this;
    }
}
