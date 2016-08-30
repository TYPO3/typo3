<?php
namespace TYPO3\CMS\Recycler\Tests\Unit\Task;

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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Recycler\Task\CleanerFieldProvider;
use TYPO3\CMS\Recycler\Task\CleanerTask;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;

/**
 * Testcase
 */
class CleanerFieldProviderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var CleanerFieldProvider
     */
    protected $subject = null;

    /**
     * Sets up an instance of \TYPO3\CMS\Recycler\Task\CleanerFieldProvider
     */
    protected function setUp()
    {
        $languageServiceMock = $this->getMock(LanguageService::class, ['sL'], [], '', false);
        $languageServiceMock->expects($this->any())->method('sL')->will($this->returnValue('titleTest'));
        $this->subject = $this->getMock(CleanerFieldProvider::class, ['getLanguageService']);
        $this->subject->expects($this->any())->method('getLanguageService')->willReturn($languageServiceMock);
    }

    /**
     * @param array $mockedMethods
     * @return \PHPUnit_Framework_MockObject_MockObject|SchedulerModuleController
     */
    protected function getScheduleModuleControllerMock($mockedMethods = [])
    {
        $languageServiceMock = $this->getMock(LanguageService::class, ['sL'], [], '', false);
        $languageServiceMock->expects($this->any())->method('sL')->will($this->returnValue('titleTest'));

        $mockedMethods = array_merge(['getLanguageService'], $mockedMethods);
        $scheduleModuleMock = $this->getMock(SchedulerModuleController::class, $mockedMethods, [], '', false);
        $scheduleModuleMock->expects($this->any())->method('getLanguageService')->willReturn($languageServiceMock);

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

        $scheduleModuleControllerMock = $this->getScheduleModuleControllerMock(['addMessage']);
        $scheduleModuleControllerMock->expects($this->atLeastOnce())
            ->method('addMessage')
            ->with($this->equalTo('titleTest'), FlashMessage::ERROR);

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

        $this->subject->validateAdditionalFields($submittedData, $this->getScheduleModuleControllerMock(['addMessage']));
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
        $this->assertTrue($this->subject->validateAdditionalFields($submittedData, $scheduleModuleControllerMock));
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

        $taskMock = $this->getMock(CleanerTask::class);

        $taskMock->expects($this->once())
            ->method('setTcaTables')
            ->with($this->equalTo(['pages']));

        $taskMock->expects($this->once())
            ->method('setPeriod')
            ->with($this->equalTo(14));

        $this->subject->saveAdditionalFields($submittedData, $taskMock);
    }
}
