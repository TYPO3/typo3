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

namespace TYPO3\CMS\Core\Tests\Unit\Log;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\Processor\NullProcessor;
use TYPO3\CMS\Core\Log\Writer\NullWriter;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LogManagerTest extends UnitTestCase
{
    #[Test]
    public function logManagerTurnsUnderScoreStyleLoggerNamesIntoDotStyleLoggerNames(): void
    {
        self::assertSame('test.a.b', (new LogManager())->getLogger('test_a_b')->getName());
    }

    #[Test]
    public function logManagerTurnsNamespaceStyleLoggerNamesIntoDotStyleLoggerNames(): void
    {
        self::assertSame('test.a.b', (new LogManager())->getLogger('test\\a\\b')->getName());
    }

    #[Test]
    public function managerReturnsSameLoggerOnRepeatedRequest(): void
    {
        $loggerName = StringUtility::getUniqueId('test.core.log');
        $logger = new LogManager();
        $logger->registerLogger($loggerName);
        $logger1 = $logger->getLogger($loggerName);
        $logger2 = $logger->getLogger($loggerName);
        self::assertSame($logger1, $logger2);
    }

    #[Test]
    public function configuresLoggerWithConfiguredWriter(): void
    {
        $component = 'test';
        $writer = NullWriter::class;
        $level = LogLevel::DEBUG;
        $GLOBALS['TYPO3_CONF_VARS']['LOG'][$component]['writerConfiguration'] = [
            $level => [
                $writer => [],
            ],
        ];
        $logger = (new LogManager())->getLogger($component);
        $writers = $logger->getWriters();
        self::assertInstanceOf($writer, $writers[$level][0]);
    }

    #[Test]
    public function configuresLoggerWithConfiguredProcessor(): void
    {
        $component = 'test';
        $processor = NullProcessor::class;
        $level = LogLevel::DEBUG;
        $GLOBALS['TYPO3_CONF_VARS']['LOG'][$component]['processorConfiguration'] = [
            $level => [
                $processor => [],
            ],
        ];
        $logger = (new LogManager())->getLogger($component);
        $processors = $logger->getProcessors();
        self::assertInstanceOf($processor, $processors[$level][0]);
    }
}
