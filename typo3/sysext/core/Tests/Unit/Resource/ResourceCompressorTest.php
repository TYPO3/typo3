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

namespace TYPO3\CMS\Core\Tests\Unit\Resource;

use TYPO3\CMS\Core\Resource\ResourceCompressor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ResourceCompressorTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    public function cssFixStatementsDataProvider(): array
    {
        return [
            'nothing to do - no charset/import/namespace' => [
                'body { background: #ffffff; }',
                'body { background: #ffffff; }',
            ],
            'import in front' => [
                '@import url(http://www.example.com/css); body { background: #ffffff; }',
                'LF/* moved by compressor */LF@import url(http://www.example.com/css);LF/* moved by compressor */LFbody { background: #ffffff; }',
            ],
            'import in back, without quotes' => [
                'body { background: #ffffff; } @import url(http://www.example.com/css);',
                'LF/* moved by compressor */LF@import url(http://www.example.com/css);LF/* moved by compressor */LFbody { background: #ffffff; }',
            ],
            'import in back, with double-quotes' => [
                'body { background: #ffffff; } @import url("http://www.example.com/css");',
                'LF/* moved by compressor */LF@import url("http://www.example.com/css");LF/* moved by compressor */LFbody { background: #ffffff; }',
            ],
            'import in back, with single-quotes' => [
                'body { background: #ffffff; } @import url(\'http://www.example.com/css\');',
                'LF/* moved by compressor */LF@import url(\'http://www.example.com/css\');LF/* moved by compressor */LFbody { background: #ffffff; }',
            ],
            'import in middle and back, without quotes' => [
                'body { background: #ffffff; } @import url(http://www.example.com/A); div { background: #000; } @import url(http://www.example.com/B);',
                'LF/* moved by compressor */LF@import url(http://www.example.com/A);@import url(http://www.example.com/B);LF/* moved by compressor */LFbody { background: #ffffff; }  div { background: #000; }',
            ],
            'charset declaration is unique' => [
                'body { background: #ffffff; } @charset "UTF-8"; div { background: #000; }; @charset "UTF-8";',
                '@charset "UTF-8";LF/* moved by compressor */LFbody { background: #ffffff; }  div { background: #000; };',
            ],
            'order of charset, namespace and import is correct' => [
                'body { background: #ffffff; } @charset "UTF-8"; div { background: #000; }; @import "file2.css"; @namespace url(http://www.w3.org/1999/xhtml);',
                '@charset "UTF-8";LF/* moved by compressor */LF@namespace url(http://www.w3.org/1999/xhtml);LF/* moved by compressor */LF@import "file2.css";LF/* moved by compressor */LFbody { background: #ffffff; }  div { background: #000; };',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider cssFixStatementsDataProvider
     */
    public function cssFixStatementsMovesStatementsToTopIfNeeded(string $input, string $expected): void
    {
        $subject = $this->getAccessibleMock(ResourceCompressor::class, ['compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory']);
        $subject->_call('initialize');
        $result = $subject->_call('cssFixStatements', $input);
        $resultWithReadableLinefeed = str_replace(LF, 'LF', $result);
        self::assertEquals($expected, $resultWithReadableLinefeed);
    }

    /**
     * @test
     */
    public function compressedCssFileIsFlaggedToNotCompressAgain(): void
    {
        $fileName = 'fooFile.css';
        $compressedFileName = $fileName . '.gz';
        $testFileFixture = [
            $fileName => [
                'file' => $fileName,
                'compress' => true,
            ],
        ];
        $subject = $this->getAccessibleMock(ResourceCompressor::class, ['compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory']);
        $subject->expects(self::once())
            ->method('compressCssFile')
            ->with($fileName)
            ->willReturn($compressedFileName);

        $result = $subject->compressCssFiles($testFileFixture);

        self::assertArrayHasKey($compressedFileName, $result);
        self::assertArrayHasKey('compress', $result[$compressedFileName]);
        self::assertFalse($result[$compressedFileName]['compress']);
    }

    /**
     * @test
     */
    public function compressedJsFileIsFlaggedToNotCompressAgain(): void
    {
        $fileName = 'fooFile.js';
        $compressedFileName = $fileName . '.gz';
        $testFileFixture = [
            $fileName => [
                'file' => $fileName,
                'compress' => true,
            ],
        ];
        $subject = $this->getAccessibleMock(ResourceCompressor::class, ['compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory']);
        $subject->expects(self::once())
            ->method('compressJsFile')
            ->with($fileName)
            ->willReturn($compressedFileName);

        $result = $subject->compressJsFiles($testFileFixture);

        self::assertArrayHasKey($compressedFileName, $result);
        self::assertArrayHasKey('compress', $result[$compressedFileName]);
        self::assertFalse($result[$compressedFileName]['compress']);
    }

    /**
     * @test
     */
    public function concatenatedCssFileIsFlaggedToNotConcatenateAgain(): void
    {
        $fileName = 'fooFile.css';
        $concatenatedFileName = 'merged_' . $fileName;
        $testFileFixture = [
            $fileName => [
                'file' => $fileName,
                'excludeFromConcatenation' => false,
                'media' => 'all',
            ],
        ];
        $subject = $this->getAccessibleMock(ResourceCompressor::class, ['compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory']);
        $subject->expects(self::once())
            ->method('createMergedCssFile')
            ->willReturn($concatenatedFileName);

        $result = $subject->concatenateCssFiles($testFileFixture);

        self::assertArrayHasKey($concatenatedFileName, $result);
        self::assertArrayHasKey('excludeFromConcatenation', $result[$concatenatedFileName]);
        self::assertTrue($result[$concatenatedFileName]['excludeFromConcatenation']);
    }

    /**
     * @test
     */
    public function concatenatedCssFilesAreSeparatedByMediaType(): void
    {
        $allFileName = 'allFile.css';
        $screenFileName1 = 'screenFile.css';
        $screenFileName2 = 'screenFile2.css';
        $testFileFixture = [
            $allFileName => [
                'file' => $allFileName,
                'excludeFromConcatenation' => false,
                'media' => 'all',
            ],
            // use two screen files to check if they are merged into one, even with a different media type
            $screenFileName1 => [
                'file' => $screenFileName1,
                'excludeFromConcatenation' => false,
                'media' => 'screen',
            ],
            $screenFileName2 => [
                'file' => $screenFileName2,
                'excludeFromConcatenation' => false,
                'media' => 'screen',
            ],
        ];
        $subject = $this->getAccessibleMock(ResourceCompressor::class, ['compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory']);
        $subject->expects(self::exactly(2))
            ->method('createMergedCssFile')
            ->will(self::onConsecutiveCalls(
                self::returnValue('merged_' . $allFileName),
                self::returnValue('merged_' . $screenFileName1)
            ));

        $result = $subject->concatenateCssFiles($testFileFixture);

        self::assertEquals([
            'merged_' . $allFileName,
            'merged_' . $screenFileName1,
        ], array_keys($result));
        self::assertEquals('all', $result['merged_' . $allFileName]['media']);
        self::assertEquals('screen', $result['merged_' . $screenFileName1]['media']);
    }

    /**
     * @test
     */
    public function concatenatedCssFilesObeyForceOnTopOption(): void
    {
        $screen1FileName = 'screen1File.css';
        $screen2FileName = 'screen2File.css';
        $screen3FileName = 'screen3File.css';
        $testFileFixture = [
            $screen1FileName => [
                'file' => $screen1FileName,
                'excludeFromConcatenation' => false,
                'media' => 'screen',
            ],
            $screen2FileName => [
                'file' => $screen2FileName,
                'excludeFromConcatenation' => false,
                'media' => 'screen',
            ],
            $screen3FileName => [
                'file' => $screen3FileName,
                'excludeFromConcatenation' => false,
                'forceOnTop' => true,
                'media' => 'screen',
            ],
        ];
        $subject = $this->getAccessibleMock(ResourceCompressor::class, ['compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory']);
        // Replace mocked method getFilenameFromMainDir by passthrough callback
        $subject->method('getFilenameFromMainDir')->willReturnArgument(0);
        $subject->expects(self::once())
            ->method('createMergedCssFile')
            ->with(self::equalTo([$screen3FileName, $screen1FileName, $screen2FileName]));

        $subject->concatenateCssFiles($testFileFixture);
    }

    /**
     * @test
     */
    public function concatenatedCssFilesObeyExcludeFromConcatenation(): void
    {
        $screen1FileName = 'screen1File.css';
        $screen2FileName = 'screen2File.css';
        $screen3FileName = 'screen3File.css';
        $testFileFixture = [
            $screen1FileName => [
                'file' => $screen1FileName,
                'excludeFromConcatenation' => false,
                'media' => 'screen',
            ],
            $screen2FileName => [
                'file' => $screen2FileName,
                'excludeFromConcatenation' => true,
                'media' => 'screen',
            ],
            $screen3FileName => [
                'file' => $screen3FileName,
                'excludeFromConcatenation' => false,
                'media' => 'screen',
            ],
        ];
        $subject = $this->getAccessibleMock(ResourceCompressor::class, ['compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory']);
        $subject->method('getFilenameFromMainDir')->willReturnArgument(0);
        $subject->expects(self::once())
            ->method('createMergedCssFile')
            ->with(self::equalTo([$screen1FileName, $screen3FileName]))
            ->willReturn('merged_screen');

        $result = $subject->concatenateCssFiles($testFileFixture);
        self::assertEquals([
            $screen2FileName,
            'merged_screen',
        ], array_keys($result));
        self::assertEquals('screen', $result[$screen2FileName]['media']);
        self::assertEquals('screen', $result['merged_screen']['media']);
    }

    /**
     * @test
     */
    public function concatenateJsFileIsFlaggedToNotConcatenateAgain(): void
    {
        $fileName = 'fooFile.js';
        $concatenatedFileName = 'merged_' . $fileName;
        $testFileFixture = [
            $fileName => [
                'file' => $fileName,
                'excludeFromConcatenation' => false,
                'section' => 'top',
            ],
        ];
        $subject = $this->getAccessibleMock(ResourceCompressor::class, ['compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory']);
        $subject->expects(self::once())
            ->method('createMergedJsFile')
            ->willReturn($concatenatedFileName);

        $result = $subject->concatenateJsFiles($testFileFixture);

        self::assertArrayHasKey($concatenatedFileName, $result);
        self::assertArrayHasKey('excludeFromConcatenation', $result[$concatenatedFileName]);
        self::assertTrue($result[$concatenatedFileName]['excludeFromConcatenation']);
    }

    public function concatenateJsFileAsyncDataProvider(): array
    {
        return [
            'all files have no async' => [
                [
                    [
                        'file' => 'file1.js',
                        'excludeFromConcatenation' => false,
                        'section' => 'top',
                    ],
                    [
                        'file' => 'file2.js',
                        'excludeFromConcatenation' => false,
                        'section' => 'top',
                    ],
                ],
                false,
            ],
            'all files have async false' => [
                [
                    [
                        'file' => 'file1.js',
                        'excludeFromConcatenation' => false,
                        'section' => 'top',
                        'async' => false,
                    ],
                    [
                        'file' => 'file2.js',
                        'excludeFromConcatenation' => false,
                        'section' => 'top',
                        'async' => false,
                    ],
                ],
                false,
            ],
            'all files have async true' => [
                [
                    [
                        'file' => 'file1.js',
                        'excludeFromConcatenation' => false,
                        'section' => 'top',
                        'async' => true,
                    ],
                    [
                        'file' => 'file2.js',
                        'excludeFromConcatenation' => false,
                        'section' => 'top',
                        'async' => true,
                    ],
                ],
                true,
            ],
            'one file async true and one file async false' => [
                [
                    [
                        'file' => 'file1.js',
                        'excludeFromConcatenation' => false,
                        'section' => 'top',
                        'async' => true,
                    ],
                    [
                        'file' => 'file2.js',
                        'excludeFromConcatenation' => false,
                        'section' => 'top',
                        'async' => false,
                    ],
                ],
                false,
            ],
            'one file async true and one file async false but is excluded form concatenation' => [
                [
                    [
                        'file' => 'file1.js',
                        'excludeFromConcatenation' => false,
                        'section' => 'top',
                        'async' => true,
                    ],
                    [
                        'file' => 'file2.js',
                        'excludeFromConcatenation' => true,
                        'section' => 'top',
                        'async' => false,
                    ],
                ],
                true,
            ],
            'one file async false and one file async true but is excluded form concatenation' => [
                [
                    [
                        'file' => 'file1.js',
                        'excludeFromConcatenation' => false,
                        'section' => 'top',
                        'async' => false,
                    ],
                    [
                        'file' => 'file2.js',
                        'excludeFromConcatenation' => true,
                        'section' => 'top',
                        'async' => true,
                    ],
                ],
                false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider concatenateJsFileAsyncDataProvider
     */
    public function concatenateJsFileAddsAsyncPropertyIfAllFilesAreAsync(array $input, bool $expected): void
    {
        $concatenatedFileName = 'merged_foo.js';
        $subject = $this->getAccessibleMock(ResourceCompressor::class, ['compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory']);
        $subject->expects(self::once())
            ->method('createMergedJsFile')
            ->willReturn($concatenatedFileName);

        $result = $subject->concatenateJsFiles($input);

        self::assertSame($expected, $result[$concatenatedFileName]['async']);
    }

    public function calcStatementsDataProvider(): array
    {
        return [
            'simple calc' => [
                'calc(100% - 3px)',
                'calc(100% - 3px)',
            ],
            'complex calc with parentheses at the beginning' => [
                'calc((100%/20) - 2*3px)',
                'calc((100%/20) - 2*3px)',
            ],
            'complex calc with parentheses at the end' => [
                'calc(100%/20 - 2*3px - (200px + 3%))',
                'calc(100%/20 - 2*3px - (200px + 3%))',
            ],
            'complex calc with many parentheses' => [
                'calc((100%/20) - (2 * (3px - (200px + 3%))))',
                'calc((100%/20) - (2 * (3px - (200px + 3%))))',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider calcStatementsDataProvider
     */
    public function calcFunctionMustRetainWhitespaces(string $input, string $expected): void
    {
        $subject = $this->getAccessibleMock(ResourceCompressor::class, ['compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory']);
        $subject->_call('initialize');
        $result = $subject->_call('compressCssString', $input);
        self::assertSame($expected, trim($result));
    }

    /**
     * @test
     */
    public function nomoduleJavascriptIsNotConcatenated(): void
    {
        $fileName = 'fooFile.js';
        $concatenatedFileName = 'merged_' . $fileName;
        $testFileFixture = [
            $fileName => [
                'file' => $fileName,
                'nomodule' => true,
                'section' => 'top',
            ],
        ];

        $subject = $this->getAccessibleMock(ResourceCompressor::class, ['compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory']);
        $result = $subject->concatenateJsFiles($testFileFixture);

        self::assertArrayNotHasKey($concatenatedFileName, $result);
        self::assertTrue($result[$fileName]['nomodule']);
    }

    /**
     * @test
     */
    public function deferJavascriptIsNotConcatenated(): void
    {
        $fileName = 'fooFile.js';
        $concatenatedFileName = 'merged_' . $fileName;
        $testFileFixture = [
            $fileName => [
                'file' => $fileName,
                'defer' => true,
                'section' => 'top',
            ],
        ];

        $subject = $this->getAccessibleMock(ResourceCompressor::class, ['compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory']);
        $result = $subject->concatenateJsFiles($testFileFixture);

        self::assertArrayNotHasKey($concatenatedFileName, $result);
        self::assertTrue($result[$fileName]['defer']);
    }
}
