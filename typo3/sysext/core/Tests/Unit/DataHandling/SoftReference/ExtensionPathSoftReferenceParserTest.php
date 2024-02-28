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

namespace TYPO3\CMS\Core\Tests\Unit\DataHandling\SoftReference;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class ExtensionPathSoftReferenceParserTest extends AbstractSoftReferenceParserTestCase
{
    public static function extensionPathSoftReferenceParserDataProvider(): array
    {
        return [
            'Simple EXT: path has a match' => [
                'text' => 'EXT:foobar/Configuration/TypoScript/setup.typoscript',
                'content' => 'EXT:foobar/Configuration/TypoScript/setup.typoscript',
                'expectedElements' => [
                    2 => [
                        'matchString' => 'EXT:foobar/Configuration/TypoScript/setup.typoscript',
                    ],
                ],
                'expectedHasMatched' => true,
            ],
            'Multiple EXT: paths have matches' => [
                'text' => '
                    @import \'EXT:foobar/Configuration/TypoScript/setup1.typoscript\'
                    foo = bar
                    @import "EXT:foobar/Configuration/TypoScript/setup2.typoscript"
                    # some comment
                ',
                'content' => '
                    @import \'EXT:foobar/Configuration/TypoScript/setup1.typoscript\'
                    foo = bar
                    @import "EXT:foobar/Configuration/TypoScript/setup2.typoscript"
                    # some comment
                ',
                'expectedElements' => [
                    2 => [
                        'matchString' => 'EXT:foobar/Configuration/TypoScript/setup1.typoscript',
                    ],
                    5 => [
                        'matchString' => 'EXT:foobar/Configuration/TypoScript/setup2.typoscript',
                    ],
                ],
                'expectedHasMatched' => true,
            ],
            'No matches returns null' => [
                'text' => '/foobar/Configuration/TypoScript/setup.typoscript',
                'content' => '',
                'expectedElements' => [],
                'expectedHasMatched' => false,
            ],
        ];
    }

    #[DataProvider('extensionPathSoftReferenceParserDataProvider')]
    #[Test]
    public function extensionPathSoftReferenceParserTest(string $text, string $content, array $expectedElements, bool $expectedHasMatched): void
    {
        $subject = $this->getParserByKey('ext_fileref');
        $result = $subject->parse('sys_template', 'include_static_file', 1, $text);
        self::assertSame($content, $result->getContent());
        self::assertSame($expectedElements, $result->getMatchedElements());
        self::assertSame($expectedHasMatched, $result->hasMatched());
    }
}
