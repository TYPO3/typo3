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
class IntrospectionProcessorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Log\Processor\IntrospectionProcessor
     */
    protected $processor;

    /**
     * A dummy result for the debug_backtrace function
     *
     * @var array
     */
    protected $dummyBacktrace = [
        [
            'file' => '/foo/filename1.php',
            'line' => 1,
            'class' => 'class1',
            'function' => 'function1'
        ],
        [
            'file' => '/foo/filename2.php',
            'line' => 2,
            'class' => 'class2',
            'function' => 'function2'
        ],
        [
            'class' => 'class3',
            'function' => 'function3'
        ],
        [
            'file' => '/foo/filename4.php',
            'line' => 4,
            'class' => 'class4',
            'function' => 'function4'
        ]
    ];

    /**
     * Sets up this testcase
     */
    protected function setUp()
    {
        $this->processor = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Log\Processor\IntrospectionProcessor::class,
            ['getDebugBacktrace', 'formatDebugBacktrace']
        );
    }

    /**
     * @test
     */
    public function introspectionProcessorAddsLastBacktraceItemToLogRecord()
    {
        $this->processor->expects($this->any())->method('getDebugBacktrace')->will($this->returnValue($this->dummyBacktrace));
        $logRecord = new \TYPO3\CMS\Core\Log\LogRecord('test.core.log', \TYPO3\CMS\Core\Log\LogLevel::DEBUG, 'test');
        $logRecord = $this->processor->processLogRecord($logRecord);

        $this->assertEquals($this->dummyBacktrace[0]['file'], $logRecord['data']['file']);
        $this->assertEquals($this->dummyBacktrace[0]['line'], $logRecord['data']['line']);
        $this->assertEquals($this->dummyBacktrace[0]['class'], $logRecord['data']['class']);
        $this->assertEquals($this->dummyBacktrace[0]['function'], $logRecord['data']['function']);
    }

    /**
     * @test
     */
    public function introspectionProcessorShiftsLogRelatedFunctionsFromBacktrace()
    {
        $dummyBacktrace = $this->dummyBacktrace;
        array_unshift(
            $dummyBacktrace,
            [
                'file' => '/foo/Log.php',
                'line' => 999,
                'class' => 'TYPO3\CMS\Core\Log\Bar\Foo',
                'function' => 'function999'
            ],
            [
                'file' => '/foo/Log2.php',
                'line' => 888,
                'class' => 'TYPO3\CMS\Core\Log\Bar2\Foo2',
                'function' => 'function888'
            ]
        );
        $this->processor->expects($this->any())->method('getDebugBacktrace')->will($this->returnValue($dummyBacktrace));

        $logRecord = new \TYPO3\CMS\Core\Log\LogRecord('test.core.log', \TYPO3\CMS\Core\Log\LogLevel::DEBUG, 'test');
        $logRecord = $this->processor->processLogRecord($logRecord);

        $this->assertEquals($this->dummyBacktrace[0]['file'], $logRecord['data']['file']);
        $this->assertEquals($this->dummyBacktrace[0]['line'], $logRecord['data']['line']);
        $this->assertEquals($this->dummyBacktrace[0]['class'], $logRecord['data']['class']);
        $this->assertEquals($this->dummyBacktrace[0]['function'], $logRecord['data']['function']);
    }

    /**
     * DataProvider for introspectionProcessorShiftsGivenNumberOfEntriesFromBacktrace
     */
    public function introspectionProcessorShiftsGivenNumberOfEntriesFromBacktraceDataProvider()
    {
        return [
            ['0'],
            ['1'],
            ['3']
        ];
    }

    /**
     * @test
     * @dataProvider introspectionProcessorShiftsGivenNumberOfEntriesFromBacktraceDataProvider
     */
    public function introspectionProcessorShiftsGivenNumberOfEntriesFromBacktrace($number)
    {
        $this->processor->expects($this->any())->method('getDebugBacktrace')->will($this->returnValue($this->dummyBacktrace));
        $this->processor->setShiftBackTraceLevel($number);

        $logRecord = new \TYPO3\CMS\Core\Log\LogRecord('test.core.log', \TYPO3\CMS\Core\Log\LogLevel::DEBUG, 'test');
        $logRecord = $this->processor->processLogRecord($logRecord);

        $this->assertEquals($this->dummyBacktrace[$number]['file'], $logRecord['data']['file']);
        $this->assertEquals($this->dummyBacktrace[$number]['line'], $logRecord['data']['line']);
        $this->assertEquals($this->dummyBacktrace[$number]['class'], $logRecord['data']['class']);
        $this->assertEquals($this->dummyBacktrace[$number]['function'], $logRecord['data']['function']);
    }

    /**
     * @test
     */
    public function introspectionProcessorLeavesOneEntryIfGivenNumberOfEntriesFromBacktraceIsGreaterOrEqualNumberOfBacktraceLevels()
    {
        $this->processor->expects($this->any())->method('getDebugBacktrace')->will($this->returnValue($this->dummyBacktrace));
        $this->processor->setShiftBackTraceLevel(4);

        $logRecord = new \TYPO3\CMS\Core\Log\LogRecord('test.core.log', \TYPO3\CMS\Core\Log\LogLevel::DEBUG, 'test');
        $logRecord = $this->processor->processLogRecord($logRecord);

        $this->assertEquals($this->dummyBacktrace[3]['file'], $logRecord['data']['file']);
        $this->assertEquals($this->dummyBacktrace[3]['line'], $logRecord['data']['line']);
        $this->assertEquals($this->dummyBacktrace[3]['class'], $logRecord['data']['class']);
        $this->assertEquals($this->dummyBacktrace[3]['function'], $logRecord['data']['function']);
    }

    /**
     * @test
     */
    public function appendFullBacktraceAddsTheFullBacktraceAsStringToTheLog()
    {
        $this->processor->expects($this->any())->method('getDebugBacktrace')->will($this->returnValue($this->dummyBacktrace));

        $this->processor->setAppendFullBacktrace(true);

        $logRecord = new \TYPO3\CMS\Core\Log\LogRecord('test.core.log', \TYPO3\CMS\Core\Log\LogLevel::DEBUG, 'test');
        $logRecord = $this->processor->processLogRecord($logRecord);

        $this->assertContains($this->dummyBacktrace[0], $logRecord['data']['backtrace']);
        $this->assertContains($this->dummyBacktrace[1], $logRecord['data']['backtrace']);
        $this->assertContains($this->dummyBacktrace[2], $logRecord['data']['backtrace']);
        $this->assertContains($this->dummyBacktrace[3], $logRecord['data']['backtrace']);
    }
}
