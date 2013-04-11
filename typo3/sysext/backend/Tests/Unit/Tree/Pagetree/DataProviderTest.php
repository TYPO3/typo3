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
 * Testcase
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class DataProviderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $fixture = NULL;

	public function setUp() {
		$GLOBALS['TYPO3_CONF_VARS']['BE']['pageTree']['preloadLimit'] = 0;
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/tree/pagetree/class.t3lib_tree_pagetree_dataprovider.php']['postProcessCollections'] = array();
		$this->fixture = new \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function getRootNodeReturnsNodeWithRootId() {
		$this->assertSame('root', $this->fixture->getRoot()->getId());
	}

	/**
	 * @test
	 */
	public function getRootNodeReturnsExpandedNode() {
		$this->assertTrue($this->fixture->getRoot()->isExpanded());
	}

	/**
	 * @test
	 */
	public function getNodesSetsIsMountPointField() {
		$subpages = array(
			array(
				'uid' => 1,
				'isMountPoint' => FALSE
			),
			array(
				'uid' => 2,
				'isMountPoint' => TRUE
			),
			array(
				'uid' => 3
			)
		);
		$subpagesWithWorkspaceOverlay = array(
			array(
				'uid' => 1,
				'title' => 'Home'
			),
			array(
				'uid' => 2,
				'title' => 'service'
			),
			array(
				'uid' => 3,
				'title' => 'contact'
			)
		);
		$fixture = $this->getMock('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\DataProvider', array('getSubpages', 'getRecordWithWorkspaceOverlay'));
		$fixture->expects($this->once())->method('getSubpages')->will($this->returnValue($subpages));
		$fixture->expects($this->at(1))->method('getRecordWithWorkspaceOverlay')->with(1)->will($this->returnValue($subpagesWithWorkspaceOverlay[0]));
		$fixture->expects($this->at(2))->method('getRecordWithWorkspaceOverlay')->with(2)->will($this->returnValue($subpagesWithWorkspaceOverlay[1]));
		$fixture->expects($this->at(3))->method('getRecordWithWorkspaceOverlay')->with(3)->will($this->returnValue($subpagesWithWorkspaceOverlay[2]));
		$node = new \TYPO3\CMS\Backend\Tree\TreeNode();
		$node->setId(12);
		$nodeCollection = $fixture->getNodes($node);
		$isMountPointResult = array();
		/** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
		foreach ($nodeCollection as $node) {
			$isMountPointResult[] = $node->isMountPoint();
		}
		$this->assertSame(array(FALSE, TRUE, FALSE), $isMountPointResult);
	}

}

?>