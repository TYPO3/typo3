<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Page;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Test case
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class PageRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $typo3DbBackup;

	/**
	 * @var \TYPO3\CMS\Frontend\Page\PageRepository
	 */
	protected $pageSelectObject;

	/**
	 * Sets up this testcase
	 */
	public function setUp() {
		$this->typo3DbBackup = $GLOBALS['TYPO3_DB'];
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTquery', 'sql_fetch_assoc', 'sql_free_result'));
		$this->pageSelectObject = new \TYPO3\CMS\Frontend\Page\PageRepository();
	}

	/**
	 * Tears down this testcase
	 */
	public function tearDown() {
		$GLOBALS['TYPO3_DB'] = $this->typo3DbBackup;
	}

	/**
	 * Tests whether the getPage Hook is called correctly.
	 *
	 * @test
	 */
	public function isGetPageHookCalled() {
		// Create a hook mock object
		$className = uniqid('tx_coretest');
		$getPageHookMock = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepositoryGetPageHookInterface', array('getPage_preProcess'), array(), $className);
		// Register hook mock object
		$GLOBALS['T3_VAR']['getUserObj'][$className] = $getPageHookMock;
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPage'][] = $className;
		// Test if hook is called and register a callback method to check given arguments
		$getPageHookMock->expects($this->once())->method('getPage_preProcess')->will($this->returnCallback(array($this, 'isGetPagePreProcessCalledCallback')));
		$this->pageSelectObject->getPage(42, FALSE);
	}

	/**
	 * Handles the arguments that have been sent to the getPage_preProcess hook
	 */
	public function isGetPagePreProcessCalledCallback() {
		list($uid, $disableGroupAccessCheck, $parent) = func_get_args();
		$this->assertEquals(42, $uid);
		$this->assertFalse($disableGroupAccessCheck);
		$this->assertTrue($parent instanceof \TYPO3\CMS\Frontend\Page\PageRepository);
	}

	/////////////////////////////////////////
	// Tests concerning getPathFromRootline
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function getPathFromRootLineForEmptyRootLineReturnsEmptyString() {
		$this->assertEquals('', $this->pageSelectObject->getPathFromRootline(array()));
	}

	///////////////////////////////
	// Tests concerning getExtURL
	///////////////////////////////
	/**
	 * @test
	 */
	public function getExtUrlForDokType3AndUrlType1AddsHttpSchemeToUrl() {
		$this->assertEquals('http://www.example.com', $this->pageSelectObject->getExtURL(array(
			'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK,
			'urltype' => 1,
			'url' => 'www.example.com'
		)));
	}

	/**
	 * @test
	 */
	public function getExtUrlForDokType3AndUrlType0PrependsSiteUrl() {
		$this->assertEquals(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'hello/world/', $this->pageSelectObject->getExtURL(array(
			'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK,
			'urltype' => 0,
			'url' => 'hello/world/'
		)));
	}

}

?>