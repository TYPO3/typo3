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
	 * @var \TYPO3\CMS\Core\Resource\ResourceCompressor
	 */
	protected $subject;

	/**
	 * Set up the test
	 */
	public function setUp() {
		parent::setUp();
		$this->subject = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Resource\\ResourceCompressor', array('dummy'));
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

}