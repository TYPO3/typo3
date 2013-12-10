<?php
/***************************************************************
* Copyright notice
*
* (c) 2011 Oliver Klee (typo3-coding@oliverklee.de)
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

/**
 * Testcase for the tx_em_Tools class.
 *
 * @package TYPO3
 * @subpackage tx_em
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_em_ToolsTest extends Tx_Phpunit_TestCase {
	/*
	 * Tests concerning sanitizeFileName
	 */
	/**
	 * @return array<array><string>
	 *
	 * @see sanitizeFilenameReturnsValidCharactersUnchanged
	 */
	public function validFilenameDataProvider() {
		return array(
			'empty string' => array(''),
			'lowercase alphanumeric characters' => array('abcxyz'),
			'uppercase alphanumeric characters' => array('ABCXYZ'),
			'digits' => array('0123456789'),
			'hyphens' => array('---'),
			'underscores' => array('___'),
			'dots' => array('...'),
		);
	}

	/**
	 * @test
	 *
	 * @dataProvider validFilenameDataProvider
	 *
	 * @param string $filename filename with valid characters only
	 */
	public function sanitizeFilenameReturnsValidCharactersUnchanged($filename) {
		$this->assertSame(
			$filename,
			tx_em_Tools::sanitizeFileName($filename)
		);
	}

	/**
	 * @return array<array><string>
	 *
	 * @see sanitizeFilenameCutsInvalidCharacters
	 */
	public function invalidFilenameDataProvider() {
		return array(
			'space' => array('a b', 'ab'),
			'slash' => array('a/b', 'ab'),
			'zero byte' => array('a' . chr(0) . 'b', 'ab'),
			'hash sign' => array('a#b', 'ab'),
		);
	}

	/**
	 * @test
	 *
	 * @dataProvider invalidFilenameDataProvider
	 *
	 * @param string $filename filename with invalid characters
	 * @param string $sanitizedFilename filename without invalid characters
	 */
	public function sanitizeFilenameCutsInvalidCharacters($filename, $sanitizedFilename) {
		$this->assertSame(
			$sanitizedFilename,
			tx_em_Tools::sanitizeFileName($filename)
		);
	}


	/*
	 * Tests concerning sanitizeDirectoryName
	 */
	/**
	 * @return array<array><string>
	 *
	 * @see sanitizeDirectorynameReturnsValidCharactersUnchanged
	 */
	public function validDirectorynameDataProvider() {
		return array(
			'empty string' => array(''),
			'lowercase alphanumeric characters' => array('abcxyz'),
			'uppercase alphanumeric characters' => array('ABCXYZ'),
			'digits' => array('0123456789'),
			'hyphens' => array('---'),
			'underscores' => array('___'),
			'dots' => array('...'),
			'slashes' => array('///'),
		);
	}

	/**
	 * @test
	 *
	 * @dataProvider validDirectorynameDataProvider
	 *
	 * @param string $directoryname directoryname with valid characters only
	 */
	public function sanitizeDirectorynameReturnsValidCharactersUnchanged($directoryname) {
		$this->assertSame(
			$directoryname,
			tx_em_Tools::sanitizeDirectoryName($directoryname)
		);
	}

	/**
	 * @return array<array><string>
	 *
	 * @see sanitizeDirectorynameCutsInvalidCharacters
	 */
	public function invalidDirectorynameDataProvider() {
		return array(
			'space' => array('a b', 'ab'),
			'zero byte' => array('a' . chr(0) . 'b', 'ab'),
			'hash sign' => array('a#b', 'ab'),
		);
	}

	/**
	 * @test
	 *
	 * @dataProvider invalidDirectorynameDataProvider
	 *
	 * @param string $directoryname directoryname with invalid characters
	 * @param string $sanitizedDirectoryname directoryname without invalid characters
	 */
	public function sanitizeDirectorynameCutsInvalidCharacters($directoryname, $sanitizedDirectoryname) {
		$this->assertSame(
			$sanitizedDirectoryname,
			tx_em_Tools::sanitizeDirectoryName($directoryname)
		);
	}


	/*
	 * Tests concerning arrayToView
	 */

	/**
	 * @test
	 */
	public function arrayToViewForEmptyArrayReturnsEmptyString() {
		$this->assertSame(
			'',
			tx_em_Tools::arrayToView(array())
		);
	}

	/**
	 * @test
	 */
	public function arrayToViewHtmlspecialCharsArrayElement() {
		$this->assertSame(
			'&quot;a &gt; b&quot; &amp; c',
			tx_em_Tools::arrayToView(array('"a > b" & c'))
		);
	}

	/**
	 * @test
	 */
	public function arrayToViewSeparatesArrayElementsByBr() {
		$this->assertSame(
			'one line<br />' . LF . 'another line',
			tx_em_Tools::arrayToView(array('one line', 'another line'))
		);
	}

	/**
	 * @test
	 */
	public function arrayToViewConvertsLinefeedToBreak() {
		$this->assertSame(
			'one line<br />' . LF . 'another line',
			tx_em_Tools::arrayToView(array('one line' . LF . 'another line'))
		);
	}
}
?>