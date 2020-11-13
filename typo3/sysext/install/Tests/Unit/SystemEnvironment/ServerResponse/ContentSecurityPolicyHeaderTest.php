<?php

declare(strict_types = 1);

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

namespace TYPO3\CMS\Install\Tests\Unit\SystemEnvironment\ServerResponse;

use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Install\SystemEnvironment\ServerResponse\ContentSecurityPolicyHeader;

class ContentSecurityPolicyHeaderTest extends TestCase
{
    public function mitigatesCrossSiteScriptingDataProvider(): array
    {
        return [
            '#1' => [
                '',
                false,
            ],
            '#2' => [
                "default-src 'none'",
                true,
            ],
            '#3' => [
                "script-src 'none'",
                false,
            ],
            '#4' => [
                "style-src 'none'",
                false,
            ],
            '#5' => [
                "default-src 'none'; script-src 'none'",
                true,
            ],
            '#6' => [
                "default-src 'none'; style-src 'none'",
                true,
            ],
            '#7' => [
                "default-src 'none'; object-src 'none'",
                true,
            ],
            '#8' => [
                "default-src 'none'; script-src 'self'; style-src 'self'; object-src 'self'",
                false,
            ],
            '#9' => [
                "script-src 'none'; style-src 'none'; object-src 'none'",
                true,
            ],
            '#10' => [
                "default-src 'none'; script-src 'unsafe-eval'; style-src 'none'; object-src 'none'",
                false,
            ],
            '#11' => [
                "default-src 'none'; script-src 'unsafe-inline'; style-src 'none'; object-src 'none'",
                false,
            ],
        ];
    }

    /**
     * @param string $header
     * @param bool $expectation
     *
     * @test
     * @dataProvider mitigatesCrossSiteScriptingDataProvider
     */
    public function mitigatesCrossSiteScripting(string $header, bool $expectation)
    {
        $subject = new ContentSecurityPolicyHeader($header);
        self::assertSame($expectation, $subject->mitigatesCrossSiteScripting());
    }
}
