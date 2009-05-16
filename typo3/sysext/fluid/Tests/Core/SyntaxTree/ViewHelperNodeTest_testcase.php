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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_childNodeAccessFacetWorksAsExpected() {
		$childNode = $this->getMock('Tx_Fluid_Core_SyntaxTree_TextNode', array(), array('foo'));

		$mockViewHelper = $this->getMock('Tx_Fluid_ChildNodeAccessFacetViewHelper', array('setChildNodes', 'initializeArguments', 'render', 'prepareArguments'));
		$mockViewHelper->expects($this->once())->method('setChildNodes')->with($this->equalTo(array($childNode)));

		$mockViewHelperArguments = $this->getMock('Tx_Fluid_Core_ViewHelperArguments', array(), array(), '', FALSE);

		$mockObjectFactory = $this->getMock('Tx_Fluid_Compatibility_ObjectFactory');
		$mockObjectFactory->expects($this->at(0))->method('create')->with('Tx_Fluid_ViewHelpers_TestViewHelper')->will($this->returnValue($mockViewHelper));
		$mockObjectFactory->expects($this->at(1))->method('create')->with('Tx_Fluid_Core_ViewHelperArguments')->will($this->returnValue($mockViewHelperArguments));

		$mockVariableContainer = $this->getMock('Tx_Fluid_Core_VariableContainer');
		$mockVariableContainer->expects($this->at(0))->method('getObjectFactory')->will($this->returnValue($mockObjectFactory));

		$viewHelperNode = new Tx_Fluid_Core_SyntaxTree_ViewHelperNode('Tx_Fluid_ViewHelpers_TestViewHelper', array());
		$viewHelperNode->addChildNode($childNode);
		$viewHelperNode->setVariableContainer($mockVariableContainer);
		$viewHelperNode->render();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_validateArgumentsIsCalledByViewHelperNode() {
		$mockViewHelper = $this->getMock('Tx_Fluid_Core_AbstractViewHelper', array('render', 'validateArguments', 'prepareArguments'));
		$mockViewHelper->expects($this->once())->method('validateArguments');

		$mockViewHelperArguments = $this->getMock('Tx_Fluid_Core_ViewHelperArguments', array(), array(), '', FALSE);

		$mockObjectFactory = $this->getMock('Tx_Fluid_Compatibility_ObjectFactory');
		$mockObjectFactory->expects($this->at(0))->method('create')->with('Tx_Fluid_Core_AbstractViewHelper')->will($this->returnValue($mockViewHelper));
		$mockObjectFactory->expects($this->at(1))->method('create')->with('Tx_Fluid_Core_ViewHelperArguments')->will($this->returnValue($mockViewHelperArguments));

		$mockVariableContainer = $this->getMock('Tx_Fluid_Core_VariableContainer');
		$mockVariableContainer->expects($this->at(0))->method('getObjectFactory')->will($this->returnValue($mockObjectFactory));

		$viewHelperNode = new Tx_Fluid_Core_SyntaxTree_ViewHelperNode('Tx_Fluid_Core_AbstractViewHelper', array());
		$viewHelperNode->setVariableContainer($mockVariableContainer);
		$viewHelperNode->render();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_renderMethodIsCalledWithCorrectArguments() {
		$arguments = array(
			'param0' => new Tx_Fluid_Core_ArgumentDefinition('param1', 'string', 'Hallo', TRUE, null, FALSE),
			'param1' => new Tx_Fluid_Core_ArgumentDefinition('param1', 'string', 'Hallo', TRUE, null, TRUE),
			'param2' => new Tx_Fluid_Core_ArgumentDefinition('param2', 'string', 'Hallo', TRUE, null, TRUE)
		);

		$mockViewHelper = $this->getMock('Tx_Fluid_Core_AbstractViewHelper', array('render', 'validateArguments', 'prepareArguments'));
		$mockViewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue($arguments));
		$mockViewHelper->expects($this->once())->method('render')->with('a', 'b');

		$mockViewHelperArguments = $this->getMock('Tx_Fluid_Core_ViewHelperArguments', array(), array(), '', FALSE);

		$mockObjectFactory = $this->getMock('Tx_Fluid_Compatibility_ObjectFactory');
		$mockObjectFactory->expects($this->at(0))->method('create')->with('Tx_Fluid_Core_AbstractViewHelper')->will($this->returnValue($mockViewHelper));
		$mockObjectFactory->expects($this->at(1))->method('create')->with('Tx_Fluid_Core_ViewHelperArguments')->will($this->returnValue($mockViewHelperArguments));

		$mockVariableContainer = $this->getMock('Tx_Fluid_Core_VariableContainer');
		$mockVariableContainer->expects($this->at(0))->method('getObjectFactory')->will($this->returnValue($mockObjectFactory));

		$viewHelperNode = new Tx_Fluid_Core_SyntaxTree_ViewHelperNode('Tx_Fluid_Core_AbstractViewHelper', array(
			'param2' => new Tx_Fluid_Core_SyntaxTree_TextNode('b'),
			'param1' => new Tx_Fluid_Core_SyntaxTree_TextNode('a'),
		));
		$viewHelperNode->setVariableContainer($mockVariableContainer);
		$viewHelperNode->render();
	}

}



?>
