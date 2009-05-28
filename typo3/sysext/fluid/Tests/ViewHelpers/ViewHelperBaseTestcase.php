<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id:$
 */
class Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase extends Tx_Extbase_Base_testcase {
	public function setUp() {
		$this->viewHelperVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer');
		$this->templateVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_TemplateVariableContainer');
		$this->uriBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_URIBuilder');
		$this->controllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext');
		$this->controllerContext->expects($this->any())->method('getURIBuilder')->will($this->returnValue($this->uriBuilder));
		$this->tagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder');
	}

	protected function injectDependenciesIntoViewHelper(Tx_Fluid_Core_ViewHelper_AbstractViewHelper $viewHelper) {
		$viewHelper->setViewHelperVariableContainer($this->viewHelperVariableContainer);
		$viewHelper->setTemplateVariableContainer($this->templateVariableContainer);
		$viewHelper->setControllerContext($this->controllerContext);
		if ($viewHelper instanceof _F3_Fluid_Core_ViewHelper_TagBasedViewHelper) {
			$viewHelper->injectTagBuilder($this->tagBuilder);
		}
	}
}
?>