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
 * @version $Id: RenderingContextTest.php 2813 2009-07-16 14:02:34Z k-fish $
 */
/**
 * Testcase for ParsingState
 *
 * @version $Id: RenderingContextTest.php 2813 2009-07-16 14:02:34Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_Fluid_Core_Rendering_RenderingContextTest_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * Parsing state
	 * @var Tx_Fluid_Core_Rendering_RenderingContext
	 */
	protected $renderingContext;

	public function setUp() {
		$this->renderingContext = new Tx_Fluid_Core_Rendering_RenderingContext();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function templateVariableContainerCanBeReadCorrectly() {
		$templateVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_TemplateVariableContainer');
		$this->renderingContext->setTemplateVariableContainer($templateVariableContainer);
		$this->assertSame($this->renderingContext->getTemplateVariableContainer(), $templateVariableContainer, 'Template Variable Container could not be read out again.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function controllerContextCanBeReadCorrectly() {
		$controllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext');
		$this->renderingContext->setControllerContext($controllerContext);
		$this->assertSame($this->renderingContext->getControllerContext(), $controllerContext);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function renderingConfiguationCanBeReadCorrectly() {
		$renderingConfiguration = $this->getMock('Tx_Fluid_Core_Rendering_RenderingConfiguration');
		$this->renderingContext->setRenderingConfiguration($renderingConfiguration);
		$this->assertSame($this->renderingContext->getRenderingConfiguration(), $renderingConfiguration);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function ObjectAccessorPostProcessorEnabledCanBeReadCorrectly() {
		$this->assertTrue($this->renderingContext->isObjectAccessorPostProcessorEnabled(), 'The default argument evaluation was not FALSE');
		$this->renderingContext->setObjectAccessorPostProcessorEnabled(FALSE);
		$this->assertFalse($this->renderingContext->isObjectAccessorPostProcessorEnabled());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function viewHelperVariableContainerCanBeReadCorrectly() {
		$viewHelperVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer');
		$this->renderingContext->setViewHelperVariableContainer($viewHelperVariableContainer);
		$this->assertSame($viewHelperVariableContainer, $this->renderingContext->getViewHelperVariableContainer());
	}
}

?>