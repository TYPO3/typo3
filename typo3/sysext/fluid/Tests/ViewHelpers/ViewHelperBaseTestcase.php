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
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: ViewHelperBaseTestcase.php 2614 2009-06-15 18:13:18Z bwaidelich $
 */
abstract class Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase extends Tx_Extbase_Base_testcase {

	/**
	 * @var Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer
	 */
	protected $viewHelperVariableContainer;

	/**
	 * @var Tx_Fluid_Core_ViewHelper_TemplateVariableContainer
	 */
	protected $templateVariableContainer;

	/**
	 * @var \Tx_Extbase_MVC_Web_Routing_URIBuilder
	 */
	protected $uriBuilder;

	/**
	 * @var \Tx_Extbase_MVC_Controller_ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var Tx_Fluid_Core_ViewHelper_TagBuilder
	 */
	protected $tagBuilder;

	/**
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->viewHelperVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer');
		$this->templateVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_TemplateVariableContainer');
		$this->uriBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_URIBuilder');
		$this->controllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext');
		$this->controllerContext->expects($this->any())->method('getURIBuilder')->will($this->returnValue($this->uriBuilder));
		$this->tagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder');
	}

	/**
	 * @param Tx_Fluid_Core_ViewHelper_AbstractViewHelper $viewHelper
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function injectDependenciesIntoViewHelper(Tx_Fluid_Core_ViewHelper_AbstractViewHelper $viewHelper) {
		$viewHelper->setViewHelperVariableContainer($this->viewHelperVariableContainer);
		$viewHelper->setTemplateVariableContainer($this->templateVariableContainer);
		$viewHelper->setControllerContext($this->controllerContext);
		if ($viewHelper instanceof Tx_Fluid_Core_ViewHelper_TagBasedViewHelper) {
			$viewHelper->injectTagBuilder($this->tagBuilder);
		}
	}
}
?>