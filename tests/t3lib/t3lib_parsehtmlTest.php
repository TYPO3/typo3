<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 Oliver Hader <oliver.hader@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Testcase for class t3lib_parsehtml
 *
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_parsehtmlTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * @var t3lib_parsehtml
	 */
	protected $fixture;

	/**
	 * Sets up this test case.
	 *
	 * @return void
	 */
	protected function setUp() {
		$this->fixture = new t3lib_parsehtml();
	}

	/**
	 * Tears down this test case.
	 *
	 * @return void
	 */
	protected function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @return array
	 */
	public function cDataWillRemainUnmodifiedDataProvider() {
		return array(
			'single-line CDATA' => array(
				'/*<![CDATA[*/ <hello world> /*]]>*/',
				'/*<![CDATA[*/ <hello world> /*]]>*/',
			),
			'multi-line CDATA #1' => array(
				'/*<![CDATA[*/' . LF . '<hello world> /*]]>*/',
				'/*<![CDATA[*/' . LF . '<hello world> /*]]>*/',
			),
			'multi-line CDATA #2' => array(
				'/*<![CDATA[*/ <hello world>' . LF . '/*]]>*/',
				'/*<![CDATA[*/ <hello world>' . LF . '/*]]>*/',
			),
			'multi-line CDATA #3' => array(
				'/*<![CDATA[*/' . LF . '<hello world>' . LF . '/*]]>*/',
				'/*<![CDATA[*/' . LF . '<hello world>' . LF . '/*]]>*/',
			),
		);
	}

	/**
	 * @test
	 * @param string $source
	 * @param string $expected
	 * @dataProvider cDataWillRemainUnmodifiedDataProvider
	 */
	public function xHtmlCleaningDoesNotModifyCDATA($source, $expected) {
		$result = $this->fixture->XHTML_clean($source);
		$this->assertSame($expected, $result);
	}

}
?>