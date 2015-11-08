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
        $languageServiceMock = $this->getMock(LanguageService::class, array('sL'), array(), '', false);
        $languageServiceMock->expects($this->any())->method('sL')->will($this->returnValue('titleTest'));
        $this->subject = $this->getMock(CleanerFieldProvider::class, array('getLanguageService'));
        $this->subject->expects($this->any())->method('getLanguageService')->willReturn($languageServiceMock);
    }

    /**
     * @param array $mockedMethods
     * @return \PHPUnit_Framework_MockObject_MockObject|SchedulerModuleController
     */
    protected function getScheduleModuleControllerMock($mockedMethods = array())
    {
        $languageServiceMock = $this->getMock(LanguageService::class, array('sL'), array(), '', false);
        $languageServiceMock->expects($this->any())->method('sL')->will($this->returnValue('titleTest'));

        $mockedMethods = array_merge(array('getLanguageService'), $mockedMethods);
        $scheduleModuleMock = $this->getMock(SchedulerModuleController::class, $mockedMethods, array(), '', false);
        $scheduleModuleMock->expects($this->any())->method('getLanguageService')->willReturn($languageServiceMock);

        return $scheduleModuleMock;
    }

    /**
     * @return array
     */
    public function validateAdditionalFieldsLogsPeriodErrorDataProvider()
    {
        return array(
            array('abc'),
            array($this->getMockBuilder(CleanerTask::class)->disableOriginalConstructor()->getMock()),
            array(null),
            array(''),
            array(0),
            array('1234abc')
        );
    }

    /**
     * @param mixed $period
     * @test
     * @dataProvider validateAdditionalFieldsLogsPeriodErrorDataProvider
     */
    public function validateAdditionalFieldsLogsPeriodError($period)
    {
        $submittedData = array(
            'RecyclerCleanerPeriod' => $period,
            'RecyclerCleanerTCA' => array('pages')
        );

        $scheduleModuleControllerMock = $this->getScheduleModuleControllerMock(array('addMessage'));
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
        return array(
            array('abc'),
            array($this->getMockBuilder(CleanerTask::class)->disableOriginalConstructor()->getMock()),
            array(null),
            array(123)
        );
    }

    /**
     * @param mixed $table
     * @test
     * @dataProvider validateAdditionalFieldsDataProvider
     */
    public function validateAdditionalFieldsLogsTableError($table)
    {
        $submittedData = array(
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => $table
        );

        $this->subject->validateAdditionalFields($submittedData, $this->getScheduleModuleControllerMock(['addMessage']));
    }

    /**
     * @test
     */
    public function validateAdditionalFieldsIsTrueIfValid()
    {
        $submittedData = array(
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => array('pages')
        );

        $scheduleModuleControllerMock = $this->getScheduleModuleControllerMock();
        $GLOBALS['TCA']['pages'] = array('foo' => 'bar');
        $this->assertTrue($this->subject->validateAdditionalFields($submittedData, $scheduleModuleControllerMock));
    }

    /**
     * @test
     */
    public function saveAdditionalFieldsSavesFields()
    {
        $submittedData = array(
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => array('pages')
        );

        $taskMock = $this->getMock(CleanerTask::class);

        $taskMock->expects($this->once())
            ->method('setTcaTables')
            ->with($this->equalTo(array('pages')));

        $taskMock->expects($this->once())
            ->method('setPeriod')
            ->with($this->equalTo(14));

        $this->subject->saveAdditionalFields($submittedData, $taskMock);
    }
}
