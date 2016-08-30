<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Processor;

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

/**
 * Test case
 */
class AbstractProcessorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Log\Exception\InvalidLogProcessorConfigurationException
     */
    public function processorRefusesInvalidConfigurationOptions()
    {
        $invalidConfiguration = [
            'foo' => 'bar'
        ];
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Tests\Unit\Log\Fixtures\ProcessorFixture::class, $invalidConfiguration);
    }

    /**
     * @test
     */
    public function loggerExecutesProcessors()
    {
        $logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
        $writer = new \TYPO3\CMS\Core\Log\Writer\NullWriter();
        $level = \TYPO3\CMS\Core\Log\LogLevel::DEBUG;
        $logRecord = new \TYPO3\CMS\Core\Log\LogRecord('dummy', $level, 'message');
        $processor = $this->getMock(\TYPO3\CMS\Core\Log\Processor\ProcessorInterface::class, ['processLogRecord']);
        $processor->expects($this->once())->method('processLogRecord')->willReturn($logRecord);

        $logger->addWriter($level, $writer);
        $logger->addProcessor($level, $processor);
        $logger->warning('test');
    }
}
