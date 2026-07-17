<?php

declare(strict_types=1);

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

use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Log\LogRecord;

/**
 * Adds the unique ID of the current request to log records, in order
 * to correlate all log entries written during a single request.
 */
final class RequestIdProcessor implements ProcessorInterface
{
    public function __construct(private readonly RequestId $requestId) {}

    public function processLogRecord(LogRecord $logRecord): LogRecord
    {
        return $logRecord->setRequestId((string)$this->requestId);
    }
}
