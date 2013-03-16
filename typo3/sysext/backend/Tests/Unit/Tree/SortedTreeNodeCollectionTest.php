<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Tree;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Stefan Galinski <stefan.galinski@gmail.com>
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
 * Testcase for class \TYPO3\CMS\Backend\Tree\SortedTreeNodeCollection.
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class SortedTreeNodeCollectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	protected function createTestCollection() {
		$nodeCollection = new \TYPO3\CMS\Backend\Tree\SortedTreeNodeCollection();
		$node = new \TYPO3\CMS\Backend\Tree\TreeNode(array('id' => 5));
		$nodeCollection->append($node);
		$node = new \TYPO3\CMS\Backend\Tree\TreeNode(array('id' => 15));
		$nodeCollection->append($node);
		$node = new \TYPO3\CMS\Backend\Tree\TreeNode(array('id' => 3));
		$nodeCollection->append($node);
		return $nodeCollection;
	}

	protected function createTestCollectionWithTwoNodes() {
		$nodeCollection = new \TYPO3\CMS\Backend\Tree\SortedTreeNodeCollection();
		$node = new \TYPO3\CMS\Backend\Tree\TreeNode(array('id' => 5));
		$nodeCollection->append($node);
		$node = new \TYPO3\CMS\Backend\Tree\TreeNode(array('id' => 3));
		$nodeCollection->append($node);
		return $nodeCollection;
	}

	/**
	 * @test
	 */
	public function appendsSorted() {
		$nodeCollection = $this->createTestCollection();
		$expected = array(3, 5, 15);
		$ids = array();
		foreach ($nodeCollection as $node) {
			$ids[] = $node->getId();
		}
		$this->assertSame($expected, $ids);
	}

	/**
	 * @test
	 */
	public function collectionContainsNode() {
		$nodeCollection = $this->createTestCollection();
		$node = new \TYPO3\CMS\Backend\Tree\TreeNode(array('id' => 5));
		$this->assertTrue($nodeCollection->contains($node));
	}

	/**
	 * @test
	 */
	public function searchDataWithBinarySearch() {
		$nodeCollection = $this->createTestCollection();
		$node = new \TYPO3\CMS\Backend\Tree\TreeNode(array('id' => 15));
		$this->assertTrue($nodeCollection->contains($node));
		$node = new \TYPO3\CMS\Backend\Tree\TreeNode(array('id' => 99));
		$this->assertFalse($nodeCollection->contains($node));
		$nodeCollection = $this->createTestCollectionWithTwoNodes();
		$node = new \TYPO3\CMS\Backend\Tree\TreeNode(array('id' => 3));
		$this->assertTrue($nodeCollection->contains($node));
		$node = new \TYPO3\CMS\Backend\Tree\TreeNode(array('id' => 99));
		$this->assertFalse($nodeCollection->contains($node));
	}

}

?>