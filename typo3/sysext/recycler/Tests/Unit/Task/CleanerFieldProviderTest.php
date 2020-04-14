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

namespace TYPO3\CMS\Recycler\Tests\Unit\Task;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
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
            ->setMethods(['sL'])
            ->disableOriginalConstructor()
            ->getMock();
        $languageServiceMock->expects(self::any())->method('sL')->willReturn('titleTest');
        $this->subject = $this->getMockBuilder(CleanerFieldProvider::class)
            ->setMethods(['getLanguageService', 'addMessage'])
            ->getMock();
        $this->subject->expects(self::any())->method('getLanguageService')->willReturn($languageServiceMock);
    }

    /**
     * @param array $mockedMethods
     * @return \PHPUnit\Framework\MockObject\MockObject|SchedulerModuleController
     */
    protected function getScheduleModuleControllerMock($mockedMethods = [])
    {
        $languageServiceMock = $this->getMockBuilder(LanguageService::class)
            ->setMethods(['sL'])
            ->disableOriginalConstructor()
            ->getMock();
        $languageServiceMock->expects(self::any())->method('sL')->willReturn('titleTest');

        $mockedMethods = array_merge(['getLanguageService'], $mockedMethods);
        $scheduleModuleMock = $this->getMockBuilder(SchedulerModuleController::class)
            ->setMethods($mockedMethods)
            ->disableOriginalConstructor()
            ->getMock();
        $scheduleModuleMock->expects(self::any())->method('getLanguageService')->willReturn($languageServiceMock);

        return $scheduleModuleMock;
    }

    /**
     * @return array
     */
    public function validateAdditionalFieldsLogsPeriodErrorDataProvider()
    {
        return [
            ['abc'],
            [$this->getMockBuilder(CleanerTask::class)->disableOriginalConstructor()->getMock()],
            [null],
            [''],
            [0],
            ['1234abc']
        ];
    }

    /**
     * @param mixed $period
     * @test
     * @dataProvider validateAdditionalFieldsLogsPeriodErrorDataProvider
     */
    public function validateAdditionalFieldsLogsPeriodError($period)
    {
        $submittedData = [
            'RecyclerCleanerPeriod' => $period,
            'RecyclerCleanerTCA' => ['pages']
        ];

        $scheduleModuleControllerMock = $this->getScheduleModuleControllerMock();
        $this->subject->expects(self::atLeastOnce())
            ->method('addMessage')
            ->with(self::equalTo('titleTest'), FlashMessage::ERROR);

        $this->subject->validateAdditionalFields($submittedData, $scheduleModuleControllerMock);
    }

    /**
     * @return array
     */
    public function validateAdditionalFieldsDataProvider()
    {
        return [
            ['abc'],
            [$this->getMockBuilder(CleanerTask::class)->disableOriginalConstructor()->getMock()],
            [null],
            [123]
        ];
    }

    /**
     * @param mixed $table
     * @test
     * @dataProvider validateAdditionalFieldsDataProvider
     */
    public function validateAdditionalFieldsLogsTableError($table)
    {
        $submittedData = [
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => $table
        ];

        $this->subject->validateAdditionalFields($submittedData, $this->getScheduleModuleControllerMock());
    }

    /**
     * @test
     */
    public function validateAdditionalFieldsIsTrueIfValid()
    {
        $submittedData = [
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => ['pages']
        ];

        $scheduleModuleControllerMock = $this->getScheduleModuleControllerMock();
        $GLOBALS['TCA']['pages'] = ['foo' => 'bar'];
        self::assertTrue($this->subject->validateAdditionalFields($submittedData, $scheduleModuleControllerMock));
    }

    /**
     * @test
     */
    public function saveAdditionalFieldsSavesFields()
    {
        $submittedData = [
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => ['pages']
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
