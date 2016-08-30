<?php
namespace TYPO3\CMS\Install\Tests\Unit\Status;

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

use TYPO3\CMS\Install\Status\StatusUtility;

/**
 * Test case
 */
class StatusUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function sortBySeveritySortsGivenStatusObjects()
    {
        $errorMock = $this->getMock(\TYPO3\CMS\Install\Status\ErrorStatus::class, ['dummy']);
        $warningMock = $this->getMock(\TYPO3\CMS\Install\Status\WarningStatus::class, ['dummy']);
        $okMock = $this->getMock(\TYPO3\CMS\Install\Status\OkStatus::class, ['dummy']);
        $infoMock = $this->getMock(\TYPO3\CMS\Install\Status\InfoStatus::class, ['dummy']);
        $noticeMock = $this->getMock(\TYPO3\CMS\Install\Status\NoticeStatus::class, ['dummy']);
        $statusUtility = new StatusUtility();
        $return = $statusUtility->sortBySeverity([$noticeMock, $infoMock, $okMock, $warningMock, $errorMock]);
        $this->assertSame([$errorMock], $return['error']);
        $this->assertSame([$warningMock], $return['warning']);
        $this->assertSame([$okMock], $return['ok']);
        $this->assertSame([$infoMock], $return['information']);
        $this->assertSame([$noticeMock], $return['notice']);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Install\Status\Exception
     */
    public function filterBySeverityThrowsExceptionIfObjectNotImplementingStatusInterfaceIsGiven()
    {
        $statusUtility = new StatusUtility();
        $statusUtility->filterBySeverity([new \stdClass()]);
    }

    /**
     * @test
     */
    public function filterBySeverityReturnsSpecificSeverityOnly()
    {
        $errorMock = $this->getMock(\TYPO3\CMS\Install\Status\ErrorStatus::class, ['dummy']);
        $warningMock = $this->getMock(\TYPO3\CMS\Install\Status\WarningStatus::class, ['dummy']);
        $statusUtility = new StatusUtility();
        $return = $statusUtility->filterBySeverity([$errorMock, $warningMock], 'error');
        $this->assertSame([$errorMock], $return);
    }
}
