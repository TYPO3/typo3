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
 * @package Fluid
 * @subpackage Tests
 * @version $Id$
 */
/**
 * Testcase for DefaultViewHelper
 *
 * @package Fluid
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

include_once(dirname(__FILE__) . '/Fixtures/ConstraintSyntaxTreeNode.php');
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_ViewHelpers_ForViewHelperTest_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
	}
	
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_forExecutesTheLoopCorrectly() {
		$this->viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();
		
		$variableContainer = new Tx_Fluid_Core_VariableContainer(array());
		
		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($variableContainer);		
		$this->viewHelper->variableContainer = $variableContainer;
		$this->viewHelper->setViewHelperNode($viewHelperNode);
		$this->viewHelper->render(array(0,1,2,3), 'innerVariable');
		
		$expectedCallProtocol = array(
			array('innerVariable' => 0),
			array('innerVariable' => 1),
			array('innerVariable' => 2),
			array('innerVariable' => 3)
		);
		$this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');	
	}
}



?>
