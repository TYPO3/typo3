<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for ViewHelperNode's evaluateBooleanExpression()
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Tests_Unit_Core_Parser_SyntaxTree_ViewHelperNodeComparatorTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode
	 */
	protected $viewHelperNode;

	/**
	 * @var Tx_Fluid_Core_Rendering_RenderingContextInterface
	 */
	protected $renderingContext;

	/**
	 * Setup fixture
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->viewHelperNode = $this->getAccessibleMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('dummy'), array(), '', FALSE);
		$this->renderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContextInterface');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function havingMoreThanThreeElementsInTheSyntaxTreeThrowsException() {
		$rootNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_RootNode');
		$rootNode->expects($this->once())->method('getChildNodes')->will($this->returnValue(array(1,2,3,4)));

		$this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function comparingEqualNumbersReturnsTrue() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('5'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('=='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('5'));

		$this->assertTrue($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function comparingUnequalNumbersReturnsFalse() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('5'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('=='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('3'));

		$this->assertFalse($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function notEqualReturnsFalseIfNumbersAreEqual() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('5'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('!='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('5'));

		$this->assertFalse($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function notEqualReturnsTrueIfNumbersAreNotEqual() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('5'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('!='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('3'));

		$this->assertTrue($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function oddNumberModulo2ReturnsTrue() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('43'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('%'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('2'));

		$this->assertTrue($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function evenNumberModulo2ReturnsFalse() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('42'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('%'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('2'));

		$this->assertFalse($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function greaterThanReturnsTrueIfNumberIsReallyGreater() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('>'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('9'));

		$this->assertTrue($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function greaterThanReturnsFalseIfNumberIsEqual() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('>'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$this->assertFalse($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function greaterOrEqualsReturnsTrueIfNumberIsReallyGreater() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('>='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('9'));

		$this->assertTrue($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function greaterOrEqualsReturnsTrueIfNumberIsEqual() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('>='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$this->assertTrue($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function greaterOrEqualsReturnFalseIfNumberIsSmaller() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('>='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('11'));

		$this->assertFalse($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function lessThanReturnsTrueIfNumberIsReallyless() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('9'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('<'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$this->assertTrue($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function lessThanReturnsFalseIfNumberIsEqual() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('<'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$this->assertFalse($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function lessOrEqualsReturnsTrueIfNumberIsReallyLess() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('9'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('<='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$this->assertTrue($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function lessOrEqualsReturnsTrueIfNumberIsEqual() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('<='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$this->assertTrue($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function lessOrEqualsReturnFalseIfNumberIsBigger() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('11'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('<='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$this->assertFalse($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function lessOrEqualsReturnFalseIfComparingWithANegativeNumber() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('11 <= -2.1'));

		$this->assertFalse($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function objectsAreComparedStrictly() {
		$object1 = new stdClass();
		$object2 = new stdClass();

		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();

		$object1Node = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode', array('evaluate'));
		$object1Node->expects($this->any())->method('evaluate')->will($this->returnValue($object1));

		$object2Node = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode', array('evaluate'));
		$object2Node->expects($this->any())->method('evaluate')->will($this->returnValue($object2));

		$rootNode->addChildNode($object1Node);
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('=='));
		$rootNode->addChildNode($object2Node);

		$this->assertFalse($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function objectsAreComparedStrictlyInUnequalComparison() {
		$object1 = new stdClass();
		$object2 = new stdClass();

		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();

		$object1Node = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode', array('evaluate'));
		$object1Node->expects($this->any())->method('evaluate')->will($this->returnValue($object1));

		$object2Node = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode', array('evaluate'));
		$object2Node->expects($this->any())->method('evaluate')->will($this->returnValue($object2));

		$rootNode->addChildNode($object1Node);
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('!='));
		$rootNode->addChildNode($object2Node);

		$this->assertTrue($this->viewHelperNode->_call('evaluateBooleanExpression', $rootNode, $this->renderingContext));
	}
}

?>