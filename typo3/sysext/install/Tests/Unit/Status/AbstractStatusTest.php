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

use TYPO3\CMS\Install\Status\AlertStatus;
use TYPO3\CMS\Install\Status\WarningStatus;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractStatusTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getSeverityNumberReturnNumberForAlertStatus()
    {
        $this->assertEquals(2, (new AlertStatus())->getSeverityNumber());
    }

    /**
     * @test
     */
    public function jsonSerializeReturnsArrayForWarningStatus()
    {
        $status = new WarningStatus();
        $status->setTitle('aTitle');
        $status->setMessage('aMessage');
        $expected = [
            'severity' => 1,
            'title' => 'aTitle',
            'message' => 'aMessage',
        ];
        $this->assertEquals($expected, $status->jsonSerialize());
    }
}
