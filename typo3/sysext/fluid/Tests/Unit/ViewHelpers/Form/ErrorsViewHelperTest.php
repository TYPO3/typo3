<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 */

include_once(dirname(__FILE__) . '/../Fixtures/ConstraintSyntaxTreeNode.php');
require_once(dirname(__FILE__) . '/../ViewHelperBaseTestcase.php');

/**
 * @version $Id:$
 */
class Tx_Fluid_ViewHelpers_Form_ErrorsViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {
	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function renderWithoutSpecifiedNameLoopsThroughRootErrors() {
		$mockError1 = $this->getMock('Tx_Extbase_Error_Error', array(), array(), '', FALSE);
		$mockError2 = $this->getMock('Tx_Extbase_Error_Error', array(), array(), '', FALSE);
		$this->request->expects($this->atLeastOnce())->method('getErrors')->will($this->returnValue(array($mockError1, $mockError2)));

		$viewHelper = new Tx_Fluid_ViewHelpers_Form_ErrorsViewHelper();
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());
		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($variableContainer);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->setTemplateVariableContainer($variableContainer);

		$viewHelper->render();

		$expectedCallProtocol = array(
			array('error' => $mockError1),
			array('error' => $mockError2)
		);
		$this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs');
	}

}
?>