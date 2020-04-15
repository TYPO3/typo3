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

namespace TYPO3\CMS\Core\Tests\Unit\Log\Processor;

use TYPO3\CMS\Core\Log\Exception\InvalidLogProcessorConfigurationException;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\ProcessorInterface;
use TYPO3\CMS\Core\Log\Writer\NullWriter;
use TYPO3\CMS\Core\Tests\Unit\Log\Fixtures\ProcessorFixture;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractProcessorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function processorRefusesInvalidConfigurationOptions()
    {
        $this->expectException(InvalidLogProcessorConfigurationException::class);
        $this->expectExceptionCode(1321696151);

        $invalidConfiguration = [
            'foo' => 'bar'
        ];
        GeneralUtility::makeInstance(ProcessorFixture::class, $invalidConfiguration);
    }

    /**
     * @test
     */
    public function loggerExecutesProcessors()
    {
        $logger = new Logger('test.core.log');
        $writer = new NullWriter();
        $level = LogLevel::DEBUG;
        $logRecord = new LogRecord('dummy', $level, 'message');
        $processor = $this->getMockBuilder(ProcessorInterface::class)
            ->setMethods(['processLogRecord'])
            ->getMock();
        $processor->expects(self::once())->method('processLogRecord')->willReturn($logRecord);

        $logger->addWriter($level, $writer);
        $logger->addProcessor($level, $processor);
        $logger->warning('test');
    }
}
