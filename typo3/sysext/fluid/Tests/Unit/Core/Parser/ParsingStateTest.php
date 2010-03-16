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
 * Testcase for ParsingState
 *
 * @version $Id: ParsingStateTest.php 3751 2010-01-22 15:56:47Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Core_Parser_ParsingStateTest extends Tx_Extbase_BaseTestCase {

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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setRootNodeCanBeReadOutAgain() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$this->parsingState->setRootNode($rootNode);
		$this->assertSame($this->parsingState->getRootNode(), $rootNode, 'Root node could not be read out again.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function pushAndGetFromStackWorks() {
		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$this->parsingState->pushNodeToStack($rootNode);
		$this->assertSame($rootNode, $this->parsingState->getNodeFromStack($rootNode), 'Node returned from stack was not the right one.');
		$this->assertSame($rootNode, $this->parsingState->popNodeFromStack($rootNode), 'Node popped from stack was not the right one.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function renderCallsTheRightMethodsOnTheRootNode() {
		$renderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContext');

		$rootNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_RootNode');
		$rootNode->expects($this->once())->method('setRenderingContext')->with($renderingContext);

		$rootNode->expects($this->once())->method('evaluate')->will($this->returnValue('T3DD09 Rock!'));
		$this->parsingState->setRootNode($rootNode);
		$renderedValue = $this->parsingState->render($renderingContext);
		$this->assertEquals($renderedValue, 'T3DD09 Rock!', 'The rendered value of the Root Node is not returned by the ParsingState.');
	}

}

?>