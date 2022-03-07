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

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\MemoryUsageProcessor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class MemoryUsageProcessorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function memoryUsageProcessorAddsMemoryUsageDataToLogRecord(): void
    {
        $logRecord = new LogRecord('test.core.log', LogLevel::DEBUG, 'test');
        $processor = new MemoryUsageProcessor();
        $logRecord = $processor->processLogRecord($logRecord);
        self::assertArrayHasKey('memoryUsage', $logRecord['data']);
    }
}
