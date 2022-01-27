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

namespace TYPO3\CMS\Core\Tests\Unit\Page;

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class JavaScriptModuleInstructionTest extends UnitTestCase
{
    public static function flagsAreAssignedDataProvider(): array
    {
        return [
            [0, 0, 0],
            [8, 8, 8, 8, 8],
            [12, 8, 4, 4],
            [7, 1, 7, 1, 2, 4],
            [7, 7, 7, 1, 2, 4],
        ];
    }

    /**
     * @test
     * @dataProvider flagsAreAssignedDataProvider
     */
    public function flagsAreAssigned(int $expectation, int $flags, int ...$additionalFlags): void
    {
        $subject = new JavaScriptModuleInstruction('Test', $flags);
        $subject->addFlags(...$additionalFlags);
        self::assertSame($expectation, $subject->getFlags());
    }
}
