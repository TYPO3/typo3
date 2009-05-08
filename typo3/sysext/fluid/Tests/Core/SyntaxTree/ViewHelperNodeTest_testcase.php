<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package
 * @subpackage
 * @version $Id$
 */
/**
 * Testcase for [insert classname here]
 *
 * @package
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
include_once(dirname(__FILE__) . '/../Fixtures/ChildNodeAccessFacetViewHelper.php');
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_SyntaxTree_ViewHelperNodeTest_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_childNodeAccessFacetWorksAsExpected() {
		$childNode = new Tx_Fluid_Core_SyntaxTree_TextNode("Hallo");

		$stubViewHelper = $this->getMock('Tx_Fluid_ChildNodeAccessFacetViewHelper', array('setChildNodes', 'initializeArguments', 'render', 'prepareArguments'));
		$stubViewHelper->expects($this->once())
		               ->method('setChildNodes')
		               ->with($this->equalTo(array($childNode)));
		$mockObjectFactory = $this->getMock('Tx_Fluid_Compatibility_ObjectFactory');
		$mockObjectFactory->expects($this->at(0))->method('create')->with('Tx_Fluid_ViewHelpers_TestViewHelper')->will($this->returnValue($stubViewHelper));

		$variableContainer = new Tx_Fluid_Core_VariableContainer(array($childNode));
		$variableContainer->injectObjectFactory($mockObjectFactory);

		$viewHelperNode = new Tx_Fluid_Core_SyntaxTree_ViewHelperNode('Tx_Fluid_ViewHelpers_TestViewHelper', array());
		$viewHelperNode->addChildNode($childNode);

		$viewHelperNode->render($variableContainer);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_validateArgumentsIsCalledByViewHelperNode() {
		$stubViewHelper = $this->getMock('Tx_Fluid_Core_AbstractViewHelper', array('render', 'validateArguments', 'prepareArguments'));
		$stubViewHelper->expects($this->once())
		               ->method('validateArguments');

		$mockObjectFactory = $this->getMock('Tx_Fluid_Compatibility_ObjectFactory');
		$mockObjectFactory->expects($this->at(0))->method('create')->with('Tx_Fluid_Core_AbstractViewHelper')->will($this->returnValue($stubViewHelper));

		$variableContainer = new Tx_Fluid_Core_VariableContainer(array());
		$variableContainer->injectObjectFactory($mockObjectFactory);

		$viewHelperNode = new Tx_Fluid_Core_SyntaxTree_ViewHelperNode('Tx_Fluid_Core_AbstractViewHelper', array());

		$viewHelperNode->render($variableContainer);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_renderMethodIsCalledWithCorrectArguments() {
		$stubViewHelper = $this->getMock('Tx_Fluid_Core_AbstractViewHelper', array('render', 'validateArguments', 'prepareArguments'));

		$stubViewHelper->expects($this->once())
		               ->method('prepareArguments')->will($this->returnValue(
		               	array(
		               		'param0' => new Tx_Fluid_Core_ArgumentDefinition('param1', 'string', 'Hallo', TRUE, null, FALSE),
		               		'param1' => new Tx_Fluid_Core_ArgumentDefinition('param1', 'string', 'Hallo', TRUE, null, TRUE),
		               		'param2' => new Tx_Fluid_Core_ArgumentDefinition('param2', 'string', 'Hallo', TRUE, null, TRUE)
		               	)
		               ));
		$stubViewHelper->expects($this->once())
		               ->method('render')->with('a', 'b');

		$mockObjectFactory = $this->getMock('Tx_Fluid_Compatibility_ObjectFactory');
		$mockObjectFactory->expects($this->at(0))->method('create')->with('Tx_Fluid_Core_AbstractViewHelper')->will($this->returnValue($stubViewHelper));

		$variableContainer = new Tx_Fluid_Core_VariableContainer(array());
		$variableContainer->injectObjectFactory($mockObjectFactory);

		$viewHelperNode = new Tx_Fluid_Core_SyntaxTree_ViewHelperNode('Tx_Fluid_Core_AbstractViewHelper', array(
			'param2' => new Tx_Fluid_Core_SyntaxTree_TextNode('b'),
			'param1' => new Tx_Fluid_Core_SyntaxTree_TextNode('a'),
		));

		$viewHelperNode->render($variableContainer);
	}
}



?>
