<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Stefan Neufeind <info (at) speedpartner.de>
 * (c) 2014 Markus Klein <klein.t3@mfc-linz.at>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Resource\ResourceCompressor;

/**
 * Testcase for the ResourceCompressor class
 *
 * @author Stefan Neufeind <info (at) speedpartner.de>
 */
class ResourceCompressorTest extends BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceCompressor|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $subject;

	/**
	 * Set up the test
	 */
	public function setUp() {
		parent::setUp();
		$this->subject = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Resource\\ResourceCompressor', array('compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir', 'checkBaseDirectory'));
	}

	/**
	 * @return array
	 */
	public function cssFixStatementsDataProvider() {
		return array(
			'nothing to do - no charset/import/namespace' => array(
				'body { background: #ffffff; }',
				'body { background: #ffffff; }'
			),
			'import in front' => array(
				'@import url(http://www.example.com/css); body { background: #ffffff; }',
				'LF/* moved by compressor */LF@import url(http://www.example.com/css);LFbody { background: #ffffff; }'
			),
			'import in back, without quotes' => array(
				'body { background: #ffffff; } @import url(http://www.example.com/css);',
				'LF/* moved by compressor */LF@import url(http://www.example.com/css);LFbody { background: #ffffff; }'
			),
			'import in back, with double-quotes' => array(
				'body { background: #ffffff; } @import url("http://www.example.com/css");',
				'LF/* moved by compressor */LF@import url("http://www.example.com/css");LFbody { background: #ffffff; }'
			),
			'import in back, with single-quotes' => array(
				'body { background: #ffffff; } @import url(\'http://www.example.com/css\');',
				'LF/* moved by compressor */LF@import url(\'http://www.example.com/css\');LFbody { background: #ffffff; }'
			),
			'import in middle and back, without quotes' => array(
				'body { background: #ffffff; } @import url(http://www.example.com/A); div { background: #000; } @import url(http://www.example.com/B);',
				'LF/* moved by compressor */LF@import url(http://www.example.com/A);LF/* moved by compressor */LF@import url(http://www.example.com/B);LFbody { background: #ffffff; }  div { background: #000; }'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider cssFixStatementsDataProvider
	 */
	public function cssFixStatementsMovesStatementsToTopIfNeeded($input, $expected) {
		$result = $this->subject->_call('cssFixStatements', $input);
		$resultWithReadableLinefeed = str_replace(LF, 'LF', $result);
		$this->assertEquals($expected, $resultWithReadableLinefeed);
	}

	/**
	 * @test
	 */
	public function compressedCssFileIsFlaggedToNotCompressAgain() {
		$fileName = 'fooFile.css';
		$compressedFileName = $fileName . '.gz';
		$testFileFixture = array(
			$fileName => array(
				'file' => $fileName,
				'compress' => TRUE,
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
	public function compressedJsFileIsFlaggedToNotCompressAgain() {
		$fileName = 'fooFile.js';
		$compressedFileName = $fileName . '.gz';
		$testFileFixture = array(
			$fileName => array(
				'file' => $fileName,
				'compress' => TRUE,
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
	public function concatenatedCssFileIsFlaggedToNotConcatenateAgain() {
		$fileName = 'fooFile.css';
		$concatenatedFileName = 'merged_' . $fileName;
		$testFileFixture = array(
			$fileName => array(
				'file' => $fileName,
				'excludeFromConcatenation' => FALSE,
				'media' => 'all',
			)
		);
		$this->subject->expects($this->once())
			->method('createMergedCssFile')
			->will($this->returnValue($concatenatedFileName));
		$this->subject->setRelativePath('');

		$result = $this->subject->concatenateCssFiles($testFileFixture);

		$this->assertArrayHasKey($concatenatedFileName, $result);
		$this->assertArrayHasKey('excludeFromConcatenation', $result[$concatenatedFileName]);
		$this->assertTrue($result[$concatenatedFileName]['excludeFromConcatenation']);
	}

	/**
	 * @test
	 */
	public function concatenatedJsFileIsFlaggedToNotConcatenateAgain() {
		$fileName = 'fooFile.js';
		$concatenatedFileName = 'merged_' . $fileName;
		$testFileFixture = array(
			$fileName => array(
				'file' => $fileName,
				'excludeFromConcatenation' => FALSE,
				'section' => 'top',
			)
		);
		$this->subject->expects($this->once())
			->method('createMergedJsFile')
			->will($this->returnValue($concatenatedFileName));
		$this->subject->setRelativePath('');

		$result = $this->subject->concatenateJsFiles($testFileFixture);

		$this->assertArrayHasKey($concatenatedFileName, $result);
		$this->assertArrayHasKey('excludeFromConcatenation', $result[$concatenatedFileName]);
		$this->assertTrue($result[$concatenatedFileName]['excludeFromConcatenation']);
	}

}