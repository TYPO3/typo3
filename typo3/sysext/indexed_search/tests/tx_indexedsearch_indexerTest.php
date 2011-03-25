<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Dmitry Dulepov (dmitry.dulepov@gmail.com)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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


require_once(t3lib_extMgm::extPath('indexed_search', 'class.indexer.php'));

/**
  * This class contains unit tests for the indexer
  *
  * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
  * @author Christian Kuhn <lolli@schwarzbu.ch>
  * @package TYPO3
  * @subpackage tx_indexedsearch
  */
class tx_indexedsearch_indexerTest extends tx_phpunit_testcase {

	/**
	 * Indexer instance
	 *
	 * @var tx_indexedsearch_indexer
	 */
	protected $indexer;

	/**
	 * A name of the temporary file
	 *
	 * @var string
	 */
	protected $temporaryFileName = '';

	/**
	 * Sets up the test
	 *
	 * @return void
	 */
	public function setUp() {
		$this->indexer = t3lib_div::makeInstance('tx_indexedsearch_indexer');
	}

	/**
	 * Explicitly cleans up the indexer object to prevent any memory leaks
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->indexer);
		if ($this->temporaryFileName) {
			@unlink($this->temporaryFileName);
		}
	}

	/**
	 * Checks that non-existing files are not returned
	 *
	 * @return void
	 */
	public function testNonExistingLocalPath() {
		$html = 'test <a href="' . md5(uniqid('')) . '">test</a> test';
		$result = $this->indexer->extractHyperLinks($html);

		$this->assertEquals(1, count($result), 'Wrong number of parsed links');
		$this->assertEquals($result[0]['localPath'], '', 'Local path is incorrect');
	}

	/**
	 * Checks that using t3vars returns correct file
	 *
	 * @return void
	 */
	public function testLocalPathWithT3Vars() {
		$this->temporaryFileName = tempnam(sys_get_temp_dir(), 't3unit-');
		$html = 'test <a href="testfile">test</a> test';
		$savedValue = $GLOBALS['T3_VAR']['ext']['indexed_search']['indexLocalFiles'];
		$GLOBALS['T3_VAR']['ext']['indexed_search']['indexLocalFiles'] = array(
			t3lib_div::shortMD5('testfile') => $this->temporaryFileName
		);
		$result = $this->indexer->extractHyperLinks($html);
		$GLOBALS['T3_VAR']['ext']['indexed_search']['indexLocalFiles'] = $savedValue;

		$this->assertEquals(1, count($result), 'Wrong number of parsed links');
		$this->assertEquals($result[0]['localPath'], $this->temporaryFileName, 'Local path is incorrect');
	}

	/**
	 * Tests that a path with baseURL
	 *
	 * @return void
	 */
	public function testLocalPathWithSiteURL() {
		$baseURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		$html = 'test <a href="' . $baseURL . 'index.php">test</a> test';
		$result = $this->indexer->extractHyperLinks($html);

		$this->assertEquals(1, count($result), 'Wrong number of parsed links');
		$this->assertEquals($result[0]['localPath'], PATH_site . 'index.php', 'Local path is incorrect');
	}

	/**
	 * Tests absolute path
	 *
	 * @return void
	 */
	public function testRelativeLocalPath() {
		$html = 'test <a href="index.php">test</a> test';
		$result = $this->indexer->extractHyperLinks($html);
		$this->assertEquals(1, count($result), 'Wrong number of parsed links');
		$this->assertEquals($result[0]['localPath'], PATH_site . 'index.php', 'Local path is incorrect');
	}

	/**
	 * Tests absolute path.
	 *
	 * @return void
	 */
	public function testAbsoluteLocalPath() {
		$path = substr(PATH_typo3, strlen(PATH_site) - 1);
		$html = 'test <a href="' . $path . 'index.php">test</a> test';
		$result = $this->indexer->extractHyperLinks($html);

		$this->assertEquals(1, count($result), 'Wrong number of parsed links');
		$this->assertEquals($result[0]['localPath'], PATH_typo3 . 'index.php', 'Local path is incorrect');
	}

	/**
	 * Tests that a path with the absRefPrefix returns correct result
	 *
	 * @return void
	 */
	public function testLocalPathWithAbsRefPrefix() {
		$absRefPrefix = '/' . md5(uniqid(''));
		$html = 'test <a href="' . $absRefPrefix . 'index.php">test</a> test';
		$savedPrefix = $GLOBALS['TSFE']->config['config']['absRefPrefix'];
		$GLOBALS['TSFE']->config['config']['absRefPrefix'] = $absRefPrefix;
		$result = $this->indexer->extractHyperLinks($html);
		$GLOBALS['TSFE']->config['config']['absRefPrefix'] = $savedPrefix;

		$this->assertEquals(1, count($result), 'Wrong number of parsed links');
		$this->assertEquals($result[0]['localPath'], PATH_site . 'index.php', 'Local path is incorrect');
	}

	/**
	 * Checks that base HREF is extracted correctly
	 *
	 * @return void
	 */
	public function textExtractBaseHref() {
		$baseHref = 'http://example.com/';
		$html = '<html><head><Base Href="' . $baseHref . '" /></head></html>';
		$result = $this->indexer->extractHyperLinks($html);

		$this->assertEquals($baseHref, $result, 'Incorrect base href was extracted');
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/indexed_search/tests/class.tx_indexedsearch_indexer_testcase.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/indexed_search/tests/class.tx_indexedsearch_indexer_testcase.php']);
}

?>