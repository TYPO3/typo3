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
 * @version $Id: RenderingConfigurationTest.php 2523 2009-06-02 10:35:40Z k-fish $
 */
/**
 * Testcase for RenderingConfiguration
 *
 * @package Fluid
 * @subpackage Tests
 * @version $Id: RenderingConfigurationTest.php 2523 2009-06-02 10:35:40Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_Rendering_RenderingConfigurationTest_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * RenderingConfiguration
	 * @var Tx_Fluid_Core_Rendering_RenderingConfiguration
	 */
	protected $renderingConfiguration;

	public function setUp() {
		$this->renderingConfiguration = new Tx_Fluid_Core_Rendering_RenderingConfiguration();
	}
	
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function objectAccessorPostProcessorCanBeReadOutAgain() {
		$objectAccessorPostProcessor = $this->getMock('Tx_Fluid_Core_Rendering_ObjectAccessorPostProcessorInterface');
		$this->renderingConfiguration->setObjectAccessorPostProcessor($objectAccessorPostProcessor);
		$this->assertSame($objectAccessorPostProcessor, $this->renderingConfiguration->getObjectAccessorPostProcessor());
	}
}
?>