<?php
namespace TYPO3\CMS\IndexedSearch\Tests\Unit;

/**
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

/**
 * This class contains unit tests for the indexer
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class IndexerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Indexer instance
	 *
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\IndexedSearch\Indexer
	 */
	protected $fixture = NULL;

	/**
	 * A name of the temporary file
	 *
	 * @var string
	 */
	protected $temporaryFileName = '';

	/**
	 * Sets up the test
	 */
	public function setUp() {
		$this->fixture = $this->getMock('TYPO3\CMS\IndexedSearch\Indexer', array('dummy'));
	}

	/**
	 * Explicitly clean up the indexer object to prevent any memory leaks
	 */
	public function tearDown() {
		if ($this->temporaryFileName) {
			@unlink($this->temporaryFileName);
		}
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function extractHyperLinksDoesNotReturnNonExistingLocalPath() {
		$html = 'test <a href="' . $this->getUniqueId() . '">test</a> test';
		$result = $this->fixture->extractHyperLinks($html);
		$this->assertEquals(1, count($result));
		$this->assertEquals('', $result[0]['localPath']);
	}

	/**
	 * @test
	 */
	public function extractHyperLinksReturnsCorrectFileUsingT3Vars() {
		$this->temporaryFileName = tempnam(sys_get_temp_dir(), 't3unit-');
		$html = 'test <a href="testfile">test</a> test';
		$GLOBALS['T3_VAR']['ext']['indexed_search']['indexLocalFiles'] = array(
			\TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5('testfile') => $this->temporaryFileName,
		);
		$result = $this->fixture->extractHyperLinks($html);
		$this->assertEquals(1, count($result));
		$this->assertEquals($this->temporaryFileName, $result[0]['localPath']);
	}

	/**
	 * @test
	 */
	public function extractHyperLinksRecurnsCorrectPathWithBaseUrl() {
		$baseURL = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
		$html = 'test <a href="' . $baseURL . 'index.php">test</a> test';
		$result = $this->fixture->extractHyperLinks($html);
		$this->assertEquals(1, count($result));
		$this->assertEquals(PATH_site . 'index.php', $result[0]['localPath']);
	}

	/**
	 * @test
	 */
	public function extractHyperLinksFindsCorrectPathWithAbsolutePath() {
		$html = 'test <a href="index.php">test</a> test';
		$result = $this->fixture->extractHyperLinks($html);
		$this->assertEquals(1, count($result));
		$this->assertEquals(PATH_site . 'index.php', $result[0]['localPath']);
	}

	/**
	 * @test
	 */
	public function extractHyperLinksFindsCorrectPathForPathWithinTypo3Directory() {
		$path = substr(PATH_typo3, strlen(PATH_site) - 1);
		$html = 'test <a href="' . $path . 'index.php">test</a> test';
		$result = $this->fixture->extractHyperLinks($html);
		$this->assertEquals(1, count($result));
		$this->assertEquals(PATH_typo3 . 'index.php', $result[0]['localPath']);
	}

	/**
	 * @test
	 */
	public function extractHyperLinksFindsCorrectPathUsingAbsRefPrefix() {
		$absRefPrefix = '/' . $this->getUniqueId();
		$html = 'test <a href="' . $absRefPrefix . 'index.php">test</a> test';
		$GLOBALS['TSFE'] = $this->getMock('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array(), array(), '', FALSE);
		$GLOBALS['TSFE']->config['config']['absRefPrefix'] = $absRefPrefix;
		$result = $this->fixture->extractHyperLinks($html);
		$this->assertEquals(1, count($result));
		$this->assertEquals(PATH_site . 'index.php', $result[0]['localPath']);
	}

	/**
	 * @test
	 */
	public function extractBaseHrefExtractsBaseHref() {
		$baseHref = 'http://example.com/';
		$html = '<html><head><Base Href="' . $baseHref . '" /></head></html>';
		$result = $this->fixture->extractBaseHref($html);
		$this->assertEquals($baseHref, $result);
	}

}
