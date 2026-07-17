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

namespace TYPO3\CMS\Core\Tests\Unit\Log\Processor;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\RequestIdProcessor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RequestIdProcessorTest extends UnitTestCase
{
    #[Test]
    public function requestIdProcessorAddsRequestIdToLogRecord(): void
    {
        $requestId = new RequestId();
        $logRecord = new LogRecord('test.core.log', LogLevel::DEBUG, 'test');
        $processor = new RequestIdProcessor($requestId);
        $logRecord = $processor->processLogRecord($logRecord);
        self::assertSame((string)$requestId, $logRecord->getRequestId());
    }
}
