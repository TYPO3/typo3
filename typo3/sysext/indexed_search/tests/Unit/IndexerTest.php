<?php
namespace TYPO3\CMS\IndexedSearch\Tests\Unit;

/**
 * This class contains unit tests for the indexer
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage tx_indexedsearch
 */
class IndexerTest extends tx_phpunit_testcase {

	/**
	 * Indexer instance
	 *
	 * @var \TYPO3\CMS\IndexedSearch\Indexer
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
		$this->indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_indexedsearch_indexer');
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
		$html = ('test <a href="' . md5(uniqid(''))) . '">test</a> test';
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
			\TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5('testfile') => $this->temporaryFileName
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
		$baseURL = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
		$html = ('test <a href="' . $baseURL) . 'index.php">test</a> test';
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
		$html = ('test <a href="' . $path) . 'index.php">test</a> test';
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
		$html = ('test <a href="' . $absRefPrefix) . 'index.php">test</a> test';
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
		$html = ('<html><head><Base Href="' . $baseHref) . '" /></head></html>';
		$result = $this->indexer->extractHyperLinks($html);
		$this->assertEquals($baseHref, $result, 'Incorrect base href was extracted');
	}

}


?>