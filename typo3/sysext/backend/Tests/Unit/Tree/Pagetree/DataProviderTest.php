<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Tree\Pagetree;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Stefan Galinski <stefan.galinski@gmail.com>
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
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @TODO: Refactor the subject class and make it better testable, especially getNodes()
 */
class DataProviderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $subject = NULL;

	public function setUp() {
		$GLOBALS['TYPO3_CONF_VARS']['BE']['pageTree']['preloadLimit'] = 0;
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/tree/pagetree/class.t3lib_tree_pagetree_dataprovider.php']['postProcessCollections'] = array();
		$GLOBALS['LOCKED_RECORDS'] = array();
		/** @var $backendUserMock \TYPO3\CMS\Core\Authentication\BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject */
		$backendUserMock = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication', array(), array(), '', FALSE);
		$GLOBALS['BE_USER'] = $backendUserMock;

		$this->subject = new \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider();

	}

	/**
	 * @test
	 */
	public function getRootNodeReturnsNodeWithRootId() {
		$this->assertSame('root', $this->subject->getRoot()->getId());
	}

	/**
	 * @test
	 */
	public function getRootNodeReturnsExpandedNode() {
		$this->assertTrue($this->subject->getRoot()->isExpanded());
	}
}
