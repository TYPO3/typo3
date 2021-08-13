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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BackendUtilityTest extends UnitTestCase
{

    /**
     * @test
     */
    public function fixVersioningPidDoesNotChangeValuesForNoBeUserAvailable(): void
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

    /**
     * @test
     */
    public function viewOnClickReturnsOnClickCodeWithAlternativeUrl(): void
    {
        // Make sure the hook inside viewOnClick is not fired. This may be removed if unit tests
        // bootstrap does not initialize TYPO3_CONF_VARS anymore.
        unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']);

        $alternativeUrl = 'https://typo3.org/about/typo3-the-cms/the-history-of-typo3/#section';
        $onclickCode = 'var previewWin = window.open(' . GeneralUtility::quoteJSvalue($alternativeUrl) . ',\'newTYPO3frontendWindow\');' . LF
            . 'if (previewWin.location.href === ' . GeneralUtility::quoteJSvalue($alternativeUrl) . ') { previewWin.location.reload(); };';
        self::assertStringMatchesFormat(
            $onclickCode,
            BackendUtility::viewOnClick(null, null, null, null, $alternativeUrl, null, false)
        );
    }
}
