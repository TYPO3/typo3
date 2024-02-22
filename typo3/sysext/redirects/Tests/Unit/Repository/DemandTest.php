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

namespace TYPO3\CMS\Redirects\Tests\Unit\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Redirects\Repository\Demand;
use TYPO3\CMS\Redirects\Utility\RedirectConflict;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DemandTest extends UnitTestCase
{
    public static function getParametersRespectsDemandStateStateDataProvider(): array
    {
        return [
            [
                [1, '', '', [], '', '', []],
                ['integrity_status' => RedirectConflict::NO_CONFLICT],
            ],
            [
                [2, '', '', ['host'], '', '', []],
                ['source_host' => 'host', 'integrity_status' => RedirectConflict::NO_CONFLICT],
            ],
            [
                [3, '', '', [], 'path', '', []],
                ['source_path' => 'path', 'integrity_status' => RedirectConflict::NO_CONFLICT],
            ],
            [
                [4, '', '', [], '', 'target', []],
                ['target' => 'target', 'integrity_status' => RedirectConflict::NO_CONFLICT],
            ],
            [
                [5, '', '', [], '', '', [301]],
                ['target_statuscode' => 301, 'integrity_status' => RedirectConflict::NO_CONFLICT],
            ],
            [
                [6, '', '', ['host'], '', 'target'],
                ['source_host' => 'host', 'target' => 'target', 'integrity_status' => RedirectConflict::NO_CONFLICT],
            ],
            [
                [7, '', '', [], 'path', '', [302]],
                ['source_path' => 'path', 'target_statuscode' => 302, 'integrity_status' => RedirectConflict::NO_CONFLICT],
            ],
            [
                [8, '', '', ['host'], 'path', 'target', [307]],
                ['source_path' => 'path', 'source_host' => 'host', 'target' => 'target', 'target_statuscode' => 307, 'integrity_status' => RedirectConflict::NO_CONFLICT],
            ],
            [
                [9, '', '', [], '', '', [], 100],
                ['max_hits' => 100, 'integrity_status' => RedirectConflict::NO_CONFLICT],
            ],
            [
                [10, '', '', [], '', '', [], 0, null, 1],
                ['creation_type' => 1, 'integrity_status' => RedirectConflict::NO_CONFLICT],
            ],
            [
                [11, '', '', [], '', '', [], 0, null, -1, 1],
                ['protected' => 1, 'integrity_status' => RedirectConflict::NO_CONFLICT],
            ],
            [
                [12, '', '', [], '', '', [], 0, null, null, null, 'self_reference'],
                ['integrity_status' => RedirectConflict::SELF_REFERENCE],
            ],
        ];
    }

    #[DataProvider('getParametersRespectsDemandStateStateDataProvider')]
    #[Test]
    public function getParametersRespectsDemandState(array $input, array $expected): void
    {
        self::assertEquals($expected, (new Demand(...$input))->getParameters());
    }
}
