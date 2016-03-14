<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

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

use TYPO3\CMS\Core\Resource\ResourceCompressor;

/**
 * Testcase for the ResourceCompressor class
 */
class ResourceCompressorTest extends BaseTestCase
{
    /**
     * @var ResourceCompressor|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * Set up the test
     */
    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(ResourceCompressor::class, array('compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory'));
    }

    /**
     * @return array
     */
    public function cssFixStatementsDataProvider()
    {
        return array(
            'nothing to do - no charset/import/namespace' => array(
                'body { background: #ffffff; }',
                'body { background: #ffffff; }'
            ),
            'import in front' => array(
                '@import url(http://www.example.com/css); body { background: #ffffff; }',
                'LF/* moved by compressor */LF@import url(http://www.example.com/css);LF/* moved by compressor */LFbody { background: #ffffff; }'
            ),
            'import in back, without quotes' => array(
                'body { background: #ffffff; } @import url(http://www.example.com/css);',
                'LF/* moved by compressor */LF@import url(http://www.example.com/css);LF/* moved by compressor */LFbody { background: #ffffff; }'
            ),
            'import in back, with double-quotes' => array(
                'body { background: #ffffff; } @import url("http://www.example.com/css");',
                'LF/* moved by compressor */LF@import url("http://www.example.com/css");LF/* moved by compressor */LFbody { background: #ffffff; }'
            ),
            'import in back, with single-quotes' => array(
                'body { background: #ffffff; } @import url(\'http://www.example.com/css\');',
                'LF/* moved by compressor */LF@import url(\'http://www.example.com/css\');LF/* moved by compressor */LFbody { background: #ffffff; }'
            ),
            'import in middle and back, without quotes' => array(
                'body { background: #ffffff; } @import url(http://www.example.com/A); div { background: #000; } @import url(http://www.example.com/B);',
                'LF/* moved by compressor */LF@import url(http://www.example.com/A);@import url(http://www.example.com/B);LF/* moved by compressor */LFbody { background: #ffffff; }  div { background: #000; }'
            ),
            'charset declaration is unique' => array(
                'body { background: #ffffff; } @charset "UTF-8"; div { background: #000; }; @charset "UTF-8";',
                '@charset "UTF-8";LF/* moved by compressor */LFbody { background: #ffffff; }  div { background: #000; };'
            ),
            'order of charset, namespace and import is correct' => array(
                'body { background: #ffffff; } @charset "UTF-8"; div { background: #000; }; @import "file2.css"; @namespace url(http://www.w3.org/1999/xhtml);',
                '@charset "UTF-8";LF/* moved by compressor */LF@namespace url(http://www.w3.org/1999/xhtml);LF/* moved by compressor */LF@import "file2.css";LF/* moved by compressor */LFbody { background: #ffffff; }  div { background: #000; };'
            ),
        );
    }

    /**
     * @test
     * @dataProvider cssFixStatementsDataProvider
     * @param string $input
     * @param string $expected
     */
    public function cssFixStatementsMovesStatementsToTopIfNeeded($input, $expected)
    {
        $result = $this->subject->_call('cssFixStatements', $input);
        $resultWithReadableLinefeed = str_replace(LF, 'LF', $result);
        $this->assertEquals($expected, $resultWithReadableLinefeed);
    }

    /**
     * @test
     */
    public function compressedCssFileIsFlaggedToNotCompressAgain()
    {
        $fileName = 'fooFile.css';
        $compressedFileName = $fileName . '.gzip';
        $testFileFixture = array(
            $fileName => array(
                'file' => $fileName,
                'compress' => true,
            )
        );
        $this->subject->expects($this->once())
            ->method('compressCssFile')
            ->with($fileName)
            ->will($this->returnValue($compressedFileName));

        $result = $this->subject->compressCssFiles($testFileFixture);

        $this->assertArrayHasKey($compressedFileName, $result);
        $this->assertArrayHasKey('compress', $result[$compressedFileName]);
        $this->assertFalse($result[$compressedFileName]['compress']);
    }

    /**
     * @test
     */
    public function compressedJsFileIsFlaggedToNotCompressAgain()
    {
        $fileName = 'fooFile.js';
        $compressedFileName = $fileName . '.gzip';
        $testFileFixture = array(
            $fileName => array(
                'file' => $fileName,
                'compress' => true,
            )
        );
        $this->subject->expects($this->once())
            ->method('compressJsFile')
            ->with($fileName)
            ->will($this->returnValue($compressedFileName));

        $result = $this->subject->compressJsFiles($testFileFixture);

        $this->assertArrayHasKey($compressedFileName, $result);
        $this->assertArrayHasKey('compress', $result[$compressedFileName]);
        $this->assertFalse($result[$compressedFileName]['compress']);
    }

    /**
     * @test
     */
    public function concatenatedCssFileIsFlaggedToNotConcatenateAgain()
    {
        $fileName = 'fooFile.css';
        $concatenatedFileName = 'merged_' . $fileName;
        $testFileFixture = array(
            $fileName => array(
                'file' => $fileName,
                'excludeFromConcatenation' => false,
                'media' => 'all',
            )
        );
        $this->subject->expects($this->once())
            ->method('createMergedCssFile')
            ->will($this->returnValue($concatenatedFileName));

        $result = $this->subject->concatenateCssFiles($testFileFixture);

        $this->assertArrayHasKey($concatenatedFileName, $result);
        $this->assertArrayHasKey('excludeFromConcatenation', $result[$concatenatedFileName]);
        $this->assertTrue($result[$concatenatedFileName]['excludeFromConcatenation']);
    }

    /**
     * @test
     */
    public function concatenatedCssFilesAreSeparatedByMediaType()
    {
        $allFileName = 'allFile.css';
        $screenFileName1 = 'screenFile.css';
        $screenFileName2 = 'screenFile2.css';
        $testFileFixture = array(
            $allFileName => array(
                'file' => $allFileName,
                'excludeFromConcatenation' => false,
                'media' => 'all',
            ),
            // use two screen files to check if they are merged into one, even with a different media type
            $screenFileName1 => array(
                'file' => $screenFileName1,
                'excludeFromConcatenation' => false,
                'media' => 'screen',
            ),
            $screenFileName2 => array(
                'file' => $screenFileName2,
                'excludeFromConcatenation' => false,
                'media' => 'screen',
            ),
        );
        $this->subject->expects($this->exactly(2))
            ->method('createMergedCssFile')
            ->will($this->onConsecutiveCalls(
                $this->returnValue('merged_' . $allFileName),
                $this->returnValue('merged_' . $screenFileName1)
            ));

        $result = $this->subject->concatenateCssFiles($testFileFixture);

        $this->assertEquals(array(
            'merged_' . $allFileName,
            'merged_' . $screenFileName1
        ), array_keys($result));
        $this->assertEquals('all', $result['merged_' . $allFileName]['media']);
        $this->assertEquals('screen', $result['merged_' . $screenFileName1]['media']);
    }

    /**
     * @test
     */
    public function concatenatedCssFilesObeyForceOnTopOption()
    {
        $screen1FileName = 'screen1File.css';
        $screen2FileName = 'screen2File.css';
        $screen3FileName = 'screen3File.css';
        $testFileFixture = array(
            $screen1FileName => array(
                'file' => $screen1FileName,
                'excludeFromConcatenation' => false,
                'media' => 'screen',
            ),
            $screen2FileName => array(
                'file' => $screen2FileName,
                'excludeFromConcatenation' => false,
                'media' => 'screen',
            ),
            $screen3FileName => array(
                'file' => $screen3FileName,
                'excludeFromConcatenation' => false,
                'forceOnTop' => true,
                'media' => 'screen',
            ),
        );
        // Replace mocked method getFilenameFromMainDir by passthrough callback
        $this->subject->expects($this->any())->method('getFilenameFromMainDir')->willReturnArgument(0);
        $this->subject->expects($this->once())
            ->method('createMergedCssFile')
            ->with($this->equalTo(array($screen3FileName, $screen1FileName, $screen2FileName)));

        $this->subject->concatenateCssFiles($testFileFixture);
    }

    /**
     * @test
     */
    public function concatenatedCssFilesObeyExcludeFromConcatenation()
    {
        $screen1FileName = 'screen1File.css';
        $screen2FileName = 'screen2File.css';
        $screen3FileName = 'screen3File.css';
        $testFileFixture = array(
            $screen1FileName => array(
                'file' => $screen1FileName,
                'excludeFromConcatenation' => false,
                'media' => 'screen',
            ),
            $screen2FileName => array(
                'file' => $screen2FileName,
                'excludeFromConcatenation' => true,
                'media' => 'screen',
            ),
            $screen3FileName => array(
                'file' => $screen3FileName,
                'excludeFromConcatenation' => false,
                'media' => 'screen',
            ),
        );
        $this->subject->expects($this->any())->method('getFilenameFromMainDir')->willReturnArgument(0);
        $this->subject->expects($this->once())
            ->method('createMergedCssFile')
            ->with($this->equalTo(array($screen1FileName, $screen3FileName)))
            ->will($this->returnValue('merged_screen'));

        $result = $this->subject->concatenateCssFiles($testFileFixture);
        $this->assertEquals(array(
            $screen2FileName,
            'merged_screen'
        ), array_keys($result));
        $this->assertEquals('screen', $result[$screen2FileName]['media']);
        $this->assertEquals('screen', $result['merged_screen']['media']);
    }

    /**
     * @test
     */
    public function concatenatedJsFileIsFlaggedToNotConcatenateAgain()
    {
        $fileName = 'fooFile.js';
        $concatenatedFileName = 'merged_' . $fileName;
        $testFileFixture = array(
            $fileName => array(
                'file' => $fileName,
                'excludeFromConcatenation' => false,
                'section' => 'top',
            )
        );
        $this->subject->expects($this->once())
            ->method('createMergedJsFile')
            ->will($this->returnValue($concatenatedFileName));

        $result = $this->subject->concatenateJsFiles($testFileFixture);

        $this->assertArrayHasKey($concatenatedFileName, $result);
        $this->assertArrayHasKey('excludeFromConcatenation', $result[$concatenatedFileName]);
        $this->assertTrue($result[$concatenatedFileName]['excludeFromConcatenation']);
    }

    /**
     * @return array
     */
    public function calcStatementsDataProvider()
    {
        return array(
            'simple calc' => array(
                'calc(100% - 3px)',
                'calc(100% - 3px)',
            ),
            'complex calc with parentheses at the beginning' => array(
                'calc((100%/20) - 2*3px)',
                'calc((100%/20) - 2*3px)',
            ),
            'complex calc with parentheses at the end' => array(
                'calc(100%/20 - 2*3px - (200px + 3%))',
                'calc(100%/20 - 2*3px - (200px + 3%))',
            ),
            'complex calc with many parentheses' => array(
                'calc((100%/20) - (2 * (3px - (200px + 3%))))',
                'calc((100%/20) - (2 * (3px - (200px + 3%))))',
            ),
        );
    }

    /**
     * @test
     * @dataProvider calcStatementsDataProvider
     * @param string $input
     * @param string $expected
     */
    public function calcFunctionMustRetainWhitespaces($input, $expected)
    {
        $result = $this->subject->_call('compressCssString', $input);
        $this->assertSame($expected, trim($result));
    }

    /**
     * @return array
     */
    public function compressCssFileContentDataProvider()
    {
        $path = dirname(__FILE__) . '/ResourceCompressorTest/Fixtures/';
        return array(
            // File. Tests:
            // - Stripped comments and white-space.
            // - Retain white-space in selectors. (http://drupal.org/node/472820)
            // - Retain pseudo-selectors. (http://drupal.org/node/460448)
            0 => array(
                $path . 'css_input_without_import.css',
                $path . 'css_input_without_import.css.optimized.css'
            ),
            // File. Tests:
            // - Retain comment hacks.
            2 => array(
                $path . 'comment_hacks.css',
                $path . 'comment_hacks.css.optimized.css'
            ),/*
            // File. Tests:
            // - Any @charset declaration at the beginning of a file should be
            //   removed without breaking subsequent CSS.*/
            6 => array(
                $path . 'charset_sameline.css',
                $path . 'charset.css.optimized.css'
            ),
            7 => array(
                $path . 'charset_newline.css',
                $path . 'charset.css.optimized.css'
            ),
        );
    }

    /**
     * Tests optimizing a CSS asset group.
     *
     * @test
     * @dataProvider compressCssFileContentDataProvider
     * @param string $cssFile
     * @param string $expected
     */
    public function compressCssFileContent($cssFile, $expected)
    {
        $cssContent = file_get_contents($cssFile);
        $compressedCss = $this->subject->_call('compressCssString', $cssContent);
        // we have to fix relative paths, if we aren't working on a file in our target directory
        $relativeFilename = str_replace(PATH_site, '', $cssFile);
        if (strpos($relativeFilename, $this->subject->_get('targetDirectory')) === false) {
            $compressedCss = $this->subject->_call('cssFixRelativeUrlPaths', $compressedCss, dirname($relativeFilename) . '/');
        }
        $this->assertEquals(file_get_contents($expected), $compressedCss, 'Group of file CSS assets optimized correctly.');
    }
}
