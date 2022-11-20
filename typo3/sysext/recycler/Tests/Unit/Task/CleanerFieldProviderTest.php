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

namespace TYPO3\CMS\Recycler\Tests\Unit\Task;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Recycler\Task\CleanerFieldProvider;
use TYPO3\CMS\Recycler\Task\CleanerTask;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase
 */
class CleanerFieldProviderTest extends UnitTestCase
{
    /**
     * @var CleanerFieldProvider
     */
    protected $subject;

    /**
     * Sets up an instance of \TYPO3\CMS\Recycler\Task\CleanerFieldProvider
     */
    protected function setUp(): void
    {
        parent::setUp();
        $languageServiceMock = $this->getMockBuilder(LanguageService::class)
            ->onlyMethods(['sL'])
            ->disableOriginalConstructor()
            ->getMock();
        $languageServiceMock->method('sL')->willReturn('titleTest');
        $this->subject = $this->getMockBuilder(CleanerFieldProvider::class)
            ->onlyMethods(['getLanguageService', 'addMessage'])
            ->getMock();
        $this->subject->method('getLanguageService')->willReturn($languageServiceMock);
    }

    /**
     * @return MockObject|SchedulerModuleController
     */
    protected function getScheduleModuleControllerMock(array $mockedMethods = [])
    {
        $languageServiceMock = $this->getMockBuilder(LanguageService::class)
            ->onlyMethods(['sL'])
            ->disableOriginalConstructor()
            ->getMock();
        $languageServiceMock->method('sL')->willReturn('titleTest');

        $mockedMethods = array_merge(['getLanguageService'], $mockedMethods);
        $scheduleModuleMock = $this->getMockBuilder(SchedulerModuleController::class)
            ->onlyMethods($mockedMethods)
            ->disableOriginalConstructor()
            ->getMock();
        $scheduleModuleMock->method('getLanguageService')->willReturn($languageServiceMock);

        return $scheduleModuleMock;
    }

    /**
     * @return array
     */
    public function validateAdditionalFieldsLogsPeriodErrorDataProvider(): array
    {
        return [
            ['abc'],
            [$this->getMockBuilder(CleanerTask::class)->disableOriginalConstructor()->getMock()],
            [null],
            [''],
            [0],
            ['1234abc'],
        ];
    }

    /**
     * @param mixed $period
     * @test
     * @dataProvider validateAdditionalFieldsLogsPeriodErrorDataProvider
     */
    public function validateAdditionalFieldsLogsPeriodError($period): void
    {
        $submittedData = [
            'RecyclerCleanerPeriod' => $period,
            'RecyclerCleanerTCA' => ['pages'],
        ];

        $scheduleModuleControllerMock = $this->getScheduleModuleControllerMock();
        $this->subject->expects(self::atLeastOnce())
            ->method('addMessage')
            ->with(self::equalTo('titleTest'), ContextualFeedbackSeverity::ERROR);

        $this->subject->validateAdditionalFields($submittedData, $scheduleModuleControllerMock);
    }

    /**
     * @return array
     */
    public function validateAdditionalFieldsDataProvider(): array
    {
        return [
            ['abc'],
            [$this->getMockBuilder(CleanerTask::class)->disableOriginalConstructor()->getMock()],
            [null],
            [123],
        ];
    }

    /**
     * @param mixed $table
     * @test
     * @dataProvider validateAdditionalFieldsDataProvider
     */
    public function validateAdditionalFieldsLogsTableError($table): void
    {
        $submittedData = [
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => $table,
        ];

        $this->subject->validateAdditionalFields($submittedData, $this->getScheduleModuleControllerMock());
    }

    /**
     * @test
     */
    public function validateAdditionalFieldsIsTrueIfValid(): void
    {
        $submittedData = [
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => ['pages'],
        ];

        $scheduleModuleControllerMock = $this->getScheduleModuleControllerMock();
        $GLOBALS['TCA']['pages'] = ['foo' => 'bar'];
        self::assertTrue($this->subject->validateAdditionalFields($submittedData, $scheduleModuleControllerMock));
    }

    /**
     * @test
     */
    public function saveAdditionalFieldsSavesFields(): void
    {
        $submittedData = [
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => ['pages'],
        ];

        $taskMock = $this->getMockBuilder(CleanerTask::class)
            ->disableOriginalConstructor()
            ->getMock();

        $taskMock->expects(self::once())
            ->method('setTcaTables')
            ->with(self::equalTo(['pages']));

        $taskMock->expects(self::once())
            ->method('setPeriod')
            ->with(self::equalTo(14));

        $this->subject->saveAdditionalFields($submittedData, $taskMock);
    }
}
