<?php
declare(strict_types=1);
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

use TYPO3\CMS\Install\Status\ErrorStatus;
use TYPO3\CMS\Install\Status\Exception;
use TYPO3\CMS\Install\Status\InfoStatus;
use TYPO3\CMS\Install\Status\NoticeStatus;
use TYPO3\CMS\Install\Status\OkStatus;
use TYPO3\CMS\Install\Status\StatusUtility;
use TYPO3\CMS\Install\Status\WarningStatus;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class StatusUtilityTest extends UnitTestCase
{
    /**
     * @test
     */
    public function sortBySeveritySortsGivenStatusObjects()
    {
        $errorStatus = new ErrorStatus();
        $warningStatus = new WarningStatus();
        $okStatus = new OkStatus();
        $infoStatus = new InfoStatus();
        $noticeStatus = new NoticeStatus();
        $statusUtility = new StatusUtility();
        $return = $statusUtility->sortBySeverity([$noticeStatus, $infoStatus, $okStatus, $warningStatus, $errorStatus]);
        $this->assertSame([$errorStatus], $return['error']);
        $this->assertSame([$warningStatus], $return['warning']);
        $this->assertSame([$okStatus], $return['ok']);
        $this->assertSame([$infoStatus], $return['information']);
        $this->assertSame([$noticeStatus], $return['notice']);
    }

    /**
     * @test
     */
    public function filterBySeverityThrowsExceptionIfObjectNotImplementingStatusInterfaceIsGiven()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1366919442);
        $statusUtility = new StatusUtility();
        $statusUtility->filterBySeverity([new \stdClass()]);
    }

    /**
     * @test
     */
    public function filterBySeverityReturnsSpecificSeverityOnly()
    {
        $errorStatus = new ErrorStatus();
        $warningStatus = new WarningStatus();
        $statusUtility = new StatusUtility();
        $return = $statusUtility->filterBySeverity([$errorStatus, $warningStatus], 'error');
        $this->assertSame([$errorStatus], $return);
    }
}
