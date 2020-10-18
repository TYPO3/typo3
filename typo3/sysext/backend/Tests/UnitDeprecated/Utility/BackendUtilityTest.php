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

namespace TYPO3\CMS\Backend\Tests\UnitDeprecated\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BackendUtilityTest extends UnitTestCase
{

    /**
     * @test
     */
    public function fixVersioningPidDoesNotChangeValuesForNoBeUserAvailable()
    {
        $GLOBALS['BE_USER'] = null;
        $tableName = 'table_a';
        $GLOBALS['TCA'][$tableName]['ctrl']['versioningWS'] = 'not_empty';
        $rr = [
            'pid' => -1,
            't3ver_oid' => 7,
            't3ver_wsid' => 42,
            't3ver_state' => 0
        ];
        $reference = $rr;
        BackendUtility::fixVersioningPid($tableName, $rr);
        self::assertSame($reference, $rr);
    }
}
