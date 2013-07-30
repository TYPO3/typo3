<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility\File;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Lucas Bremgartner <lb@bexa.ch>
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

use \TYPO3\CMS\Core\Utility\File\BasicFileUtility;

/**
 * Test case
 *
 * @author Lucas Bremgartner <lb@bexa.ch>
 */
class BasicFileUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array
	 */
	protected $iso88591GreaterThan127 = '';

	/**
	 * @var array
	 */
	protected $utf8Latin1Supplement = '';

	/**
	 * @var array
	 */
	protected $utf8Latin1ExtendedA = '';

	/**
	 * Set up this testcase
	 */
	public function setUpCharStrings() {
		// Generate string containing all characters for the iso8859-1 charset, charcode greater than 127
		for ($i = 0xA0; $i <= 0xFF; $i++) $this->iso88591GreaterThan127 .= chr($i);

		// Generate string containing all characters for the utf-8 Latin-1 Supplement (U+0080 to U+00FF)
		// without U+0080 to U+009F: control characters
		// Based on http://www.utf8-chartable.de/unicode-utf8-table.pl
		for ($i = 0xA0; $i <= 0xBF; $i++) $this->utf8Latin1Supplement .= chr(0xC2) . chr($i);
		for ($i = 0x80; $i <= 0xBF; $i++) $this->utf8Latin1Supplement .= chr(0xC3) . chr($i);

		// Generate string containing all characters for the utf-8 Latin-1 Extended-A (U+0100 to U+017F)
		for ($i = 0x80; $i <= 0xBF; $i++) $this->utf8Latin1ExtendedA .= chr(0xC4) . chr($i);
		for ($i = 0x80; $i <= 0xBF; $i++) $this->utf8Latin1ExtendedA .= chr(0xC5) . chr($i);
	}

	///////////////////////
	// Tests concerning cleanFileName
	///////////////////////
	/**
	 * Data provider for cleanFileName with UTF-8 filesystem
	 *
	 * Every array splits into:
	 * - String value fileName
	 * - String value charset (none = '', utf-8, latin1, etc.)
	 * - Expected result (cleaned fileName)
	 */
	public function cleanFileNameUTF8FilesystemDataProvider() {
		$this->setUpCharStrings();
		return array(
			// Characters ordered by ASCII table
			'allowed characters utf-8 (ASCII part)' => array(
				'-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
				'-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'
			),
			// Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
			'replace special characters with _ (not allowed characters) utf-8 (ASCII part)' => array(
				'! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
				'_____________________________'
			),
			'utf-8 (Latin-1 Supplement)' => array(
				$this->utf8Latin1Supplement,
				'________________________________ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ'
			),
			'trim leading and tailing spaces utf-8' => array(
				' test.txt  ',
				'test.txt'
			),
			'remove tailing dot' => array(
				'test.txt.',
				'test.txt'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider cleanFileNameUTF8FilesystemDataProvider
	 */

	public function cleanFileNameUTF8Filesystem($fileName, $expectedResult) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 1;

		$this->assertEquals(
			$expectedResult,
			BasicFileUtility::cleanFileName($fileName)
		);
	}


	/**
	 * Data provider for cleanFileName
	 *
	 * Every array splits into:
	 * - String value fileName
	 * - String value charset (none = '', utf-8, latin1, etc.)
	 * - Expected result (cleaned fileName)
	 */
	public function cleanFileNameDataProvider() {
		$this->setUpCharStrings();
		return array(
			// Characters ordered by ASCII table
			'allowed characters ISO-8859-1' => array(
				'-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
				'ISO-8859-1',
				'-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'
			),
			// Characters ordered by ASCII table
			'allowed characters utf-8' => array(
				'-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
				'utf-8',
				'-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'
			),
			// Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
			'replace special characters with _ (not allowed characters) ISO-8859-1' => array(
				'! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
				'ISO-8859-1',
				'_____________________________'
			),
			// Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
			'replace special characters with _ (not allowed characters) utf-8' => array(
				'! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
				'utf-8',
				'_____________________________'
			),
			'ISO-8859-1 (code > 127)' => array(
				// http://de.wikipedia.org/wiki/ISO_8859-1
				// chr(0xA0) = NBSP (no-break space) => gets trimmed
				$this->iso88591GreaterThan127,
				'ISO-8859-1',
				'_centpound_yen____c_a_____R_____-23_u___1o__1_41_23_4_AAAAAEAAAECEEEEIIIIDNOOOOOExOEUUUUEYTHssaaaaaeaaaeceeeeiiiidnoooooe_oeuuuueythy'
			),
			'utf-8 (Latin-1 Supplement)' => array(
			// chr(0xC2) . chr(0x0A) = NBSP (no-break space) => gets trimmed
				$this->utf8Latin1Supplement,
				'utf-8',
				'_centpound__yen______c_a_______R_______-23__u_____1o__1_41_23_4_AAAAAEAAAECEEEEIIIIDNOOOOOExOEUUUUEYTHssaaaaaeaaaeceeeeiiiidnoooooe_oeuuuueythy'
			),
			'utf-8 (Latin-1 Extended A)' => array(
				$this->utf8Latin1ExtendedA,
				'utf-8',
				'AaAaAaCcCcCcCcDdDdEeEeEeEeEeGgGgGgGgHhHhIiIiIiIiIiIJijJjKk__LlLlLlL_l_LlNnNnNn_n____OOooOoOoOEoeRrRrRrSsSsSsSsTtTtTtUuUuUuUuUuUuWwYyYZzZzZzs'
			),
			'trim leading and tailing spaces ISO-8859-1' => array(
				' test.txt  ',
				'ISO-8859-1',
				'test.txt'
			),
			'trim leading and tailing spaces utf-8' => array(
				' test.txt  ',
				'utf-8',
				'test.txt'
			),
			'remove tailing dot ISO-8859-1' => array(
				'test.txt.',
				'ISO-8859-1',
				'test.txt'
			),
			'remove tailing dot utf-8' => array(
				'test.txt.',
				'utf-8',
				'test.txt'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider cleanFileNameDataProvider
	 */
	public function cleanFileName($fileName, $charset, $expectedResult) {
		$this->assertEquals(
			$expectedResult,
			BasicFileUtility::cleanFileName($fileName, $charset)
		);
	}
}

?>