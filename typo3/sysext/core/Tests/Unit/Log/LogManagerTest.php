<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Log;

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

use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\Processor\NullProcessor;
use TYPO3\CMS\Core\Log\Writer\NullWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LogManagerTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Log\LogManager
     */
    protected $logManagerInstance = null;

    protected function setUp()
    {
        $this->logManagerInstance = GeneralUtility::makeInstance(LogManager::class);
    }

    protected function tearDown()
    {
        GeneralUtility::makeInstance(LogManager::class)->reset();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function logManagerReturnsLoggerWhenRequestedWithGetLogger()
    {
        $this->assertInstanceOf(Logger::class, $this->logManagerInstance->getLogger('test'));
    }

    /**
     * @test
     */
    public function logManagerTurnsUnderScoreStyleLoggerNamesIntoDotStyleLoggerNames()
    {
        $this->assertSame('test.a.b', $this->logManagerInstance->getLogger('test_a_b')->getName());
    }

    /**
     * @test
     */
    public function logManagerTurnsNamespaceStyleLoggerNamesIntoDotStyleLoggerNames()
    {
        $this->assertSame('test.a.b', $this->logManagerInstance->getLogger('test\\a\\b')->getName());
    }

    /**
     * @test
     */
    public function managerReturnsSameLoggerOnRepeatedRequest()
    {
        $loggerName = $this->getUniqueId('test.core.log');
        $this->logManagerInstance->registerLogger($loggerName);
        $logger1 = $this->logManagerInstance->getLogger($loggerName);
        $logger2 = $this->logManagerInstance->getLogger($loggerName);
        $this->assertSame($logger1, $logger2);
    }

    /**
     * @test
     */
    public function configuresLoggerWithConfiguredWriter()
    {
        $component = 'test';
        $writer = NullWriter::class;
        $level = LogLevel::DEBUG;
        $GLOBALS['TYPO3_CONF_VARS']['LOG'][$component]['writerConfiguration'] = [
            $level => [
                $writer => []
            ]
        ];
        /** @var $logger \TYPO3\CMS\Core\Log\Logger */
        $logger = $this->logManagerInstance->getLogger($component);
        $writers = $logger->getWriters();
        $this->assertInstanceOf($writer, $writers[$level][0]);
    }

    /**
     * @test
     */
    public function configuresLoggerWithConfiguredProcessor()
    {
        $component = 'test';
        $processor = NullProcessor::class;
        $level = LogLevel::DEBUG;
        $GLOBALS['TYPO3_CONF_VARS']['LOG'][$component]['processorConfiguration'] = [
            $level => [
                $processor => []
            ]
        ];
        /** @var $logger \TYPO3\CMS\Core\Log\Logger */
        $logger = $this->logManagerInstance->getLogger($component);
        $processors = $logger->getProcessors();
        $this->assertInstanceOf($processor, $processors[$level][0]);
    }
}
