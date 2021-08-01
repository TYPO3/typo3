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

namespace Unit\DataHandling\SoftReference;

use TYPO3\CMS\Core\Tests\Unit\DataHandling\SoftReference\AbstractSoftReferenceParserTest;

class ExtensionPathSoftReferenceParserTest extends AbstractSoftReferenceParserTest
{
    public function extensionPathSoftReferenceParserDataProvider(): array
    {
        return [
            'Simple EXT: path has a match' => [
                'EXT:foobar/Configuration/TypoScript/setup.typoscript',
                [
                    'content' => 'EXT:foobar/Configuration/TypoScript/setup.typoscript',
                    'elements' => [
                        2 => [
                            'matchString' => 'EXT:foobar/Configuration/TypoScript/setup.typoscript',
                        ],
                    ]
                ]
            ],
            'Multiple EXT: paths have matches' => [
                '
                    @import \'EXT:foobar/Configuration/TypoScript/setup1.typoscript\'
                    foo = bar
                    @import "EXT:foobar/Configuration/TypoScript/setup2.typoscript"
                    # some comment
                    <INCLUDE_TYPOSCRIPT: source="FILE:EXT:foobar/Configuration/TypoScript/setup3.typoscript">
                ',
                [
                    'content' => '
                    @import \'EXT:foobar/Configuration/TypoScript/setup1.typoscript\'
                    foo = bar
                    @import "EXT:foobar/Configuration/TypoScript/setup2.typoscript"
                    # some comment
                    <INCLUDE_TYPOSCRIPT: source="FILE:EXT:foobar/Configuration/TypoScript/setup3.typoscript">
                ',
                    'elements' => [
                        2 => [
                            'matchString' => 'EXT:foobar/Configuration/TypoScript/setup1.typoscript',
                        ],
                        5 => [
                            'matchString' => 'EXT:foobar/Configuration/TypoScript/setup2.typoscript',
                        ],
                        8 => [
                            'matchString' => 'EXT:foobar/Configuration/TypoScript/setup3.typoscript',
                        ],
                    ]
                ]
            ],
            'No matches returns null' => [
                '/foobar/Configuration/TypoScript/setup.typoscript',
                null
            ],
        ];
    }

    /**
     * @test
     * @dataProvider extensionPathSoftReferenceParserDataProvider
     */
    public function extensionPathSoftReferenceParserTest(string $content, ?array $expected): void
    {
        $subject = $this->getParserByKey('ext_fileref');
        $result = $subject->parse('sys_template', 'include_static_file', 1, $content)->toNullableArray();
        self::assertSame($expected, $result);
    }
}
