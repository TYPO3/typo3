<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for ViewHelperNode's evaluateBooleanExpression()
 *
 */
class Tx_Fluid_Tests_Unit_Core_Parser_SyntaxTree_BooleanNodeTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

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
	 */
	public function setUp() {
		$this->renderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContextInterface');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 */
	public function havingMoreThanThreeElementsInTheSyntaxTreeThrowsException() {
		$rootNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_RootNode');
		$rootNode->expects($this->once())->method('getChildNodes')->will($this->returnValue(array(1,2,3,4)));

		new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
	}

	/**
	 * @test
	 */
	public function comparingEqualNumbersReturnsTrue() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('5'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('=='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('5'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertTrue($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function comparingUnequalNumbersReturnsFalse() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('5'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('=='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('3'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertFalse($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function notEqualReturnsFalseIfNumbersAreEqual() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('5'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('!='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('5'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertFalse($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function notEqualReturnsTrueIfNumbersAreNotEqual() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('5'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('!='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('3'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertTrue($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function oddNumberModulo2ReturnsTrue() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('43'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('%'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('2'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertTrue($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function evenNumberModulo2ReturnsFalse() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('42'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('%'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('2'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertFalse($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function greaterThanReturnsTrueIfNumberIsReallyGreater() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('>'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('9'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertTrue($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function greaterThanReturnsFalseIfNumberIsEqual() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('>'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertFalse($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function greaterOrEqualsReturnsTrueIfNumberIsReallyGreater() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('>='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('9'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertTrue($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function greaterOrEqualsReturnsTrueIfNumberIsEqual() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('>='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertTrue($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function greaterOrEqualsReturnFalseIfNumberIsSmaller() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('>='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('11'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertFalse($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function lessThanReturnsTrueIfNumberIsReallyless() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('9'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('<'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertTrue($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function lessThanReturnsFalseIfNumberIsEqual() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('<'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertFalse($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function lessOrEqualsReturnsTrueIfNumberIsReallyLess() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('9'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('<='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertTrue($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function lessOrEqualsReturnsTrueIfNumberIsEqual() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('<='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertTrue($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function lessOrEqualsReturnFalseIfNumberIsBigger() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('11'));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('<='));
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('10'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertFalse($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function lessOrEqualsReturnFalseIfComparingWithANegativeNumber() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('11 <= -2.1'));

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertFalse($booleanNode->evaluate($this->renderingContext));
	}


	/**
	 * @test
	 */
	public function objectsAreComparedStrictly() {
		$object1 = new stdClass();
		$object2 = new stdClass();

		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();

		$object1Node = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode', array('evaluate'), array('foo'));
		$object1Node->expects($this->any())->method('evaluate')->will($this->returnValue($object1));

		$object2Node = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode', array('evaluate'), array('foo'));
		$object2Node->expects($this->any())->method('evaluate')->will($this->returnValue($object2));

		$rootNode->addChildNode($object1Node);
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('=='));
		$rootNode->addChildNode($object2Node);

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertFalse($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function objectsAreComparedStrictlyInUnequalComparison() {
		$object1 = new stdClass();
		$object2 = new stdClass();

		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();

		$object1Node = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode', array('evaluate'), array('foo'));
		$object1Node->expects($this->any())->method('evaluate')->will($this->returnValue($object1));

		$object2Node = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode', array('evaluate'), array('foo'));
		$object2Node->expects($this->any())->method('evaluate')->will($this->returnValue($object2));

		$rootNode->addChildNode($object1Node);
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('!='));
		$rootNode->addChildNode($object2Node);

		$booleanNode = new Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode($rootNode);
		$this->assertTrue($booleanNode->evaluate($this->renderingContext));
	}

	/**
	 * @test
	 */
	public function convertToBooleanProperlyConvertsValuesOfTypeBoolean() {
		$this->assertFalse(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean(FALSE));
		$this->assertTrue(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean(TRUE));
	}

	/**
	 * @test
	 */
	public function convertToBooleanProperlyConvertsValuesOfTypeString() {
		$this->assertFalse(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean(''));
		$this->assertFalse(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean('false'));
		$this->assertFalse(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean('FALSE'));

		$this->assertTrue(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean('true'));
		$this->assertTrue(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean('TRUE'));
	}

	/**
	 * @test
	 */
	public function convertToBooleanProperlyConvertsNumericValues() {
		$this->assertFalse(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean(0));
		$this->assertFalse(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean(-1));
		$this->assertFalse(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean('-1'));
		$this->assertFalse(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean(-.5));

		$this->assertTrue(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean(1));
		$this->assertTrue(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean(.5));
	}

	/**
	 * @test
	 */
	public function convertToBooleanProperlyConvertsValuesOfTypeArray() {
		$this->assertFalse(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean(array()));

		$this->assertTrue(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean(array('foo')));
		$this->assertTrue(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean(array('foo' => 'bar')));
	}

	/**
	 * @test
	 */
	public function convertToBooleanProperlyConvertsObjects() {
		$this->assertFalse(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean(NULL));

		$this->assertTrue(Tx_Fluid_Core_Parser_SyntaxTree_BooleanNode::convertToBoolean(new stdClass()));
	}
}
?>