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

use TYPO3\CMS\Redirects\Repository\Demand;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DemandTest extends UnitTestCase
{
    public function getParametersRespectsDemandStateStateDataProvider(): array
    {
        return [
            [[1, '', '', [], '', '', []], []],
            [[2, '', '', ['host'], '', '', []], ['source_host' => 'host']],
            [[3, '', '', [], 'path', '', []], ['source_path' => 'path']],
            [[4, '', '', [], '', 'target', []], ['target' => 'target']],
            [[5, '', '', [], '', '', [301]], ['target_statuscode' => 301]],
            [[6, '', '', ['host'], '', 'target'], ['source_host' => 'host', 'target' => 'target']],
            [[7, '', '', [], 'path', '', [302]], ['source_path' => 'path', 'target_statuscode' => 302]],
            [[8, '', '', ['host'], 'path', 'target', [307]], ['source_path' => 'path', 'source_host' => 'host', 'target' => 'target', 'target_statuscode' => 307]],
        ];
    }

    /**
     * @test
     * @dataProvider getParametersRespectsDemandStateStateDataProvider
     */
    public function getParametersRespectsDemandState(array $input, array $expected): void
    {
        self::assertEquals($expected, (new Demand(...$input))->getParameters());
    }
}
