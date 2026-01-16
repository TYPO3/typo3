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

namespace TYPO3\CMS\PHPStan\Tests\Rules\Classes\NamedArgumentUsageRule\Fixtures;

final class NamedArgumentFixture
{
    /**
     * @param string $p1
     * @param string $p2
     * @param array $p3 Default is empty array
     * @param bool $p4 Default is false
     */
    public function targetMethod(string $p1, string $p2, array $p3 = [], bool $p4 = false): void {}

    public function test(): void
    {
        // 1. All positional (No error)
        $this->targetMethod('a', 'b', [], true);

        // 2. Named arguments but providing all parameters (Error: usage of named args detected)
        $this->targetMethod(
            p1: 'a',
            p2: 'b',
            p3: [],
            p4: true
        );

        // 3. Named arguments with skipping optional parameters (Error: named args + specific defaults list)
        // Skipping p3 and p4
        $this->targetMethod(
            p1: 'a',
            p2: 'b'
        );

        // 4. Mixed order + skipping
        // Skipping p3
        $this->targetMethod(
            p4: true,
            p1: 'a',
            p2: 'b'
        );

        // 5. Mixed positional and named (PHP 8 feature)
        // Positional 1, 2. Named p4. Skipping p3.
        $this->targetMethod('a', 'b', p4: true);
    }
}
