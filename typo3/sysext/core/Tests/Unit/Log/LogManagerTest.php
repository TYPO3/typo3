<?php
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

/**
 * Test case
 */
class LogManagerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Log\LogManager
     */
    protected $logManagerInstance = null;

    protected function setUp()
    {
        $this->logManagerInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class);
    }

    protected function tearDown()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->reset();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function logManagerReturnsLoggerWhenRequestedWithGetLogger()
    {
        $this->assertInstanceOf(\TYPO3\CMS\Core\Log\Logger::class, $this->logManagerInstance->getLogger('test'));
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
        $writer = \TYPO3\CMS\Core\Log\Writer\NullWriter::class;
        $level = \TYPO3\CMS\Core\Log\LogLevel::DEBUG;
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
        $processor = \TYPO3\CMS\Core\Log\Processor\NullProcessor::class;
        $level = \TYPO3\CMS\Core\Log\LogLevel::DEBUG;
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
