<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Tree;

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
 * Testcase for class \TYPO3\CMS\Backend\Tree\TreeNodeCollection.
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class TreeNodeCollectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function sortNodes() {
		$nodeCollection = new \TYPO3\CMS\Backend\Tree\TreeNodeCollection(array(
			array('serializeClassName' => 'TYPO3\\CMS\\Backend\\Tree\\TreeNode', 'id' => 15),
			array('serializeClassName' => 'TYPO3\\CMS\\Backend\\Tree\\TreeNode', 'id' => 25),
			array('serializeClassName' => 'TYPO3\\CMS\\Backend\\Tree\\TreeNode', 'id' => 5),
			array('serializeClassName' => 'TYPO3\\CMS\\Backend\\Tree\\TreeNode', 'id' => 2),
			array('serializeClassName' => 'TYPO3\\CMS\\Backend\\Tree\\TreeNode', 'id' => 150),
			array('serializeClassName' => 'TYPO3\\CMS\\Backend\\Tree\\TreeNode', 'id' => 67)
		));
		$nodeCollection->asort();
		$expected = array(2, 5, 15, 25, 67, 150);
		$ids = array();
		foreach ($nodeCollection as $node) {
			$ids[] = $node->getId();
		}
		$this->assertSame($expected, $ids);
	}

}
