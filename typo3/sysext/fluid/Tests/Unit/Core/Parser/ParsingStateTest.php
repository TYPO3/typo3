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
 * Testcase for ParsingState
 *
 */
class Tx_Fluid_Tests_Unit_Core_Parser_ParsingStateTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * Parsing state
	 * @var Tx_Fluid_Core_Parser_ParsingState
	 */
	protected $parsingState;

	public function setUp() {
		$this->parsingState = new Tx_Fluid_Core_Parser_ParsingState();
	}
	public function tearDown() {
		unset($this->parsingState);
	}

	/**
	 * @test
	 */
	public function setRootNodeCanBeReadOutAgain() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$this->parsingState->setRootNode($rootNode);
		$this->assertSame($this->parsingState->getRootNode(), $rootNode, 'Root node could not be read out again.');
	}

	/**
	 * @test
	 */
	public function pushAndGetFromStackWorks() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$this->parsingState->pushNodeToStack($rootNode);
		$this->assertSame($rootNode, $this->parsingState->getNodeFromStack($rootNode), 'Node returned from stack was not the right one.');
		$this->assertSame($rootNode, $this->parsingState->popNodeFromStack($rootNode), 'Node popped from stack was not the right one.');
	}

	/**
	 * @test
	 */
	public function renderCallsTheRightMethodsOnTheRootNode() {
		$renderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContextInterface');

		$rootNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_RootNode');
		$rootNode->expects($this->once())->method('evaluate')->with($renderingContext)->will($this->returnValue('T3DD09 Rock!'));
		$this->parsingState->setRootNode($rootNode);
		$renderedValue = $this->parsingState->render($renderingContext);
		$this->assertEquals($renderedValue, 'T3DD09 Rock!', 'The rendered value of the Root Node is not returned by the ParsingState.');
	}

}

?>