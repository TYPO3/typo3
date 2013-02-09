<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Parser\SyntaxTree;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Fluid".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for NumericNode
 *
 */
class NumericNodeTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function renderReturnsProperIntegerGivenInConstructor() {
		$string = '1';
		$node = new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NumericNode($string);
		$this->assertEquals($node->evaluate($this->getMock('TYPO3\CMS\Fluid\Core\Rendering\RenderingContext')), 1, 'The rendered value of a numeric node does not match the string given in the constructor.');
	}

	/**
	 * @test
	 */
	public function renderReturnsProperFloatGivenInConstructor() {
		$string = '1.1';
		$node = new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NumericNode($string);
		$this->assertEquals($node->evaluate($this->getMock('TYPO3\CMS\Fluid\Core\Rendering\RenderingContext')), 1.1, 'The rendered value of a numeric node does not match the string given in the constructor.');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
	 */
	public function constructorThrowsExceptionIfNoNumericGiven() {
		new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NumericNode('foo');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
	 */
	public function addChildNodeThrowsException() {
		$node = new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NumericNode('1');
		$node->addChildNode(clone $node);
	}
}
?>