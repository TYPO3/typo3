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
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_ViewHelpers_IfViewHelperTest_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @var Tx_Fluid_TemplateParser
	 */
	protected $templateParser;

	/**
	 * Sets up this test case
	 *
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->templateParser = new Tx_Fluid_Core_TemplateParser();
		$this->templateParser->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_ifReturnsCorrectResultIfConditionTrue() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/IfFixture.html', FILE_TEXT);

		$templateTree = $this->templateParser->parse($templateSource)->getRootNode();
		$context = new Tx_Fluid_Core_VariableContainer(array('condition' => 'true'));
		$context->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());
		$result = $templateTree->render($context);
		$expected = 'RenderSomething';
		$this->assertEquals($expected, $result, 'IF did not return expected result if condition was true');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_ifReturnsCorrectResultIfConditionFalse() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/IfFixture.html', FILE_TEXT);

		$templateTree = $this->templateParser->parse($templateSource)->getRootNode();
		$context = new Tx_Fluid_Core_VariableContainer(array('condition' => FALSE));
		$context->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());
		$result = $templateTree->render($context);
		$expected = '';
		$this->assertEquals($expected, $result, 'IF did not return expected result if condition was false');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_ifThenElseReturnsCorrectResultIfConditionTrue() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/IfThenElseFixture.html', FILE_TEXT);

		$templateTree = $this->templateParser->parse($templateSource)->getRootNode();
		$context = new Tx_Fluid_Core_VariableContainer(array('condition' => 'true'));
		$context->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());
		$result = $templateTree->render($context);
		$expected = 'YEP';
		$this->assertEquals($expected, $result, 'IF-Then-Else did not return expected result if condition was true');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_ifThenElseReturnsCorrectResultIfConditionFalse() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/IfThenElseFixture.html', FILE_TEXT);

		$templateTree = $this->templateParser->parse($templateSource)->getRootNode();
		$context = new Tx_Fluid_Core_VariableContainer(array('condition' => FALSE));
		$context->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());
		$result = $templateTree->render($context);
		$expected = 'NOPE';
		$this->assertEquals($expected, $result, 'IF-Then-Else did not return expected result if condition was false');
	}
}

?>
