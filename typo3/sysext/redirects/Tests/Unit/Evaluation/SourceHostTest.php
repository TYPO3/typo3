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

namespace TYPO3\CMS\Redirects\Tests\Unit\Evaluation;

use TYPO3\CMS\Redirects\Evaluation\SourceHost;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SourceHostTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function evaluateFieldValueWorksWithDifferentInputsDataProvider(): array
    {
        return [
            // Valid formats
            'www.domain.com' => ['www.domain.com', 'www.domain.com'],
            'domain.com' => ['domain.com', 'domain.com'],
            '127.0.0.1' => ['127.0.0.1', '127.0.0.1'],
            '2001:0db8:85a3:08d3::0370:7344' => ['2001:0db8:85a3:08d3::0370:7344', '2001:0db8:85a3:08d3::0370:7344'],
            'http://127.0.0.1' => ['http://127.0.0.1', '127.0.0.1'],
            'http://www.domain.com' => ['http://www.domain.com', 'www.domain.com'],
            'https://www.domain.com' => ['https://www.domain.com', 'www.domain.com'],
            'http://www.domain.com/subfolder/index.php?id=123&foo=bar' => [
                'http://www.domain.com/subfolder/index.php?id=123&foo=bar',
                'www.domain.com',
            ],
            'https://www.domain.com/subfolder/index.php?id=123&foo=bar' => [
                'https://www.domain.com/subfolder/index.php?id=123&foo=bar',
                'www.domain.com',
            ],
            'http://www.domain.com/subfolder/' => ['http://www.domain.com/subfolder/', 'www.domain.com'],
            'https://www.domain.com/subfolder/' => ['https://www.domain.com/subfolder/', 'www.domain.com'],
            'http://[2001:0db8:85a3:08d3::0370:7344]/' => [
                'http://[2001:0db8:85a3:08d3::0370:7344]/',
                '2001:0db8:85a3:08d3::0370:7344',
            ],
            'www.domain.com/subfolder/' => ['www.domain.com/subfolder/', 'www.domain.com'],
            'domain.com/subfolder/' => ['domain.com/subfolder/', 'domain.com'],
            'www.domain.com/subfolder/index.php?id=123&foo=bar' => [
                'www.domain.com/subfolder/index.php?id=123&foo=bar',
                'www.domain.com',
            ],
            'domain.com/subfolder/index.php?id=123&foo=bar' => [
                'domain.com/subfolder/index.php?id=123&foo=bar',
                'domain.com',
            ],
            // special case, * means all domains
            '*' => ['*', '*'],

            // Invalid formats
            'mailto:foo@typo3.org' => ['mailto:foo@typo3.org', ''],
            'mailto:foo@typo3.org?subject=bar' => ['mailto:foo@typo3.org?subject=bar', ''],
        ];
    }

    /**
     * @test
     * @dataProvider evaluateFieldValueWorksWithDifferentInputsDataProvider
     * @param string $input
     * @param string $expected
     */
    public function evaluateFieldValueWorksWithDifferentInputs(string $input, string $expected): void
    {
        $subject = new SourceHost();
        self::assertSame($expected, $subject->evaluateFieldValue($input));
    }
}
