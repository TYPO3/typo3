<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Stefan Galinski <stefan.galinski@gmail.com>
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
 * Testcase for class t3lib_tree_Node.
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_tree_NodeTest extends tx_phpunit_testcase {
	//////////////////////
	// Utility functions
	//////////////////////

	/**
	 * Returns the absolute fixtures path for this testcase.
	 *
	 * @return string the absolute fixtures path for this testcase, will not be empty
	 */
	private function determineFixturesPath() {
		return t3lib_div::makeInstance('Tx_Phpunit_Service_TestFinder')
			->getAbsoluteCoreTestsPath() . 't3lib/tree/fixtures/';
	}

	protected function setUpNodeTestData() {
		$fixture = new t3lib_tree_Node;
		$fixture->setId('Root');

		$nodeCollection = new t3lib_tree_NodeCollection;
		for ($i = 0; $i < 10; ++$i) {
			$node = new t3lib_tree_Node;
			$node->setId($i);
			$node->setParentNode($fixture);

			$subNodeCollection = new t3lib_tree_NodeCollection;
			for ($j = 0; $j < 5; ++$j) {
				$subNode = new t3lib_tree_RepresentationNode;
				$subNode->setId($j);
				$subNode->setLabel('SubTest');
				$subNode->setType('Type');
				$subNode->setClass('Class');
				$subNode->setIcon('Icon');
				$subNode->setCallbackAction('Callback Action');
				$subNode->setParentNode($node);
				$subNodeCollection->append($subNode);
			}
			$node->setChildNodes($subNodeCollection);
			$nodeCollection->append($node);
		}
		$fixture->setChildNodes($nodeCollection);


		return $fixture;
	}


	///////////////
	// Test cases
	///////////////

	/**
	 * @test
	 */
	public function serializeFixture() {
		$expected = trim(file_get_contents($this->determineFixturesPath() . 'serialized.txt'));
		$fixture = $this->setUpNodeTestData();
		$serializedString = trim($fixture->serialize());
		$this->assertSame($expected, $serializedString);
	}

	/**
	 * @test
	 */
	public function deserializeFixture() {
		$source = trim(file_get_contents($this->determineFixturesPath() . 'serialized.txt'));
		$node = new t3lib_tree_Node();
		$node->unserialize($source);
		$serializedString = $node->serialize();
		$this->assertSame($source, $serializedString);
	}

	/**
	 * @test
	 */
	public function compareNodes() {
		$node = new t3lib_tree_Node(array('id' => '15'));
		$otherNode = new t3lib_tree_Node(array('id' => '5'));
		$compareResult = $node->compareTo($otherNode);

		$otherNode->setId('25');
		$compareResult = $node->compareTo($otherNode);
		$this->assertSame(-1, $compareResult);

		$otherNode->setId('15');
		$compareResult = $node->compareTo($otherNode);
		$this->assertSame(0, $compareResult);
	}
}
?>