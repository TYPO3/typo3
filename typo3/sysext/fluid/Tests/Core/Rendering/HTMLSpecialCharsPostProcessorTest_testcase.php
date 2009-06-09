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
 * @version $Id: HTMLSpecialCharsPostProcessorTest.php 2523 2009-06-02 10:35:40Z k-fish $
 */
/**
 * Testcase for HTMLSPecialChartPostProcessor
 *
 * @package Fluid
 * @subpackage Tests
 * @version $Id: HTMLSpecialCharsPostProcessorTest.php 2523 2009-06-02 10:35:40Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_Rendering_HTMLSpecialCharsPostProcessorTest_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * RenderingConfiguration
	 * @var Tx_Fluid_Core_Rendering_RenderingConfiguration
	 */
	protected $htmlSpecialCharsPostProcessor;

	public function setUp() {
		$this->htmlSpecialCharsPostProcessor = new Tx_Fluid_Core_Rendering_HTMLSpecialCharsPostProcessor();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_postProcessorReturnsObjectsIfInArgumentsMode() {
		$string = 'Expected <p>';
		$this->assertEquals($string, $this->htmlSpecialCharsPostProcessor->process($string, FALSE));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_postProcessorReturnsChangedObjectsIfInArgumentsMode() {
		$string = 'Expected <p>';
		$expected = 'Expected &lt;p&gt;';
		$this->assertEquals($expected, $this->htmlSpecialCharsPostProcessor->process($string, TRUE));
	}
}
?>