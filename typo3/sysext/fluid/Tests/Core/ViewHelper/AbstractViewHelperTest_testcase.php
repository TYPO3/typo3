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

include_once(dirname(__FILE__) . '/../Fixtures/TestViewHelper.php');

/**
 * Testcase for AbstractViewHelper
 *
 * @version $Id: AbstractViewHelperTest.php 2813 2009-07-16 14:02:34Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_ViewHelper_AbstractViewHelperTest_testcase extends Tx_Extbase_Base_testcase {
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function argumentsCanBeRegistered() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_ViewHelper_AbstractViewHelper'), array('render'), array(), '', FALSE);
		$viewHelper->injectReflectionService(t3lib_div::makeInstance('Tx_Extbase_Reflection_Service'));

		$name = "This is a name";
		$description = "Example desc";
		$type = "string";
		$isRequired = TRUE;
		$expected = new Tx_Fluid_Core_ViewHelper_ArgumentDefinition($name, $type, $description, $isRequired);

		$viewHelper->_call('registerArgument', $name, $type, $isRequired, $description);
		$this->assertEquals($viewHelper->prepareArguments(), array($name => $expected), 'Argument definitions not returned correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function prepareArgumentsCallsInitializeArguments() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_ViewHelper_AbstractViewHelper'), array('render', 'initializeArguments'), array(), '', FALSE);
		$viewHelper->injectReflectionService(t3lib_div::makeInstance('Tx_Extbase_Reflection_Service'));

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array()));
		$viewHelper->expects($this->once())->method('initializeArguments');

		$viewHelper->prepareArguments();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function prepareArgumentsRegistersAnnotationBasedArguments() {

		$availableClassNames = array(
			'Tx_Fluid_Core_Fixtures_TestViewHelper',
		);
		$reflectionService = new Tx_Extbase_Reflection_Service();
		// $reflectionService->setCache($this->getMock('Tx_Fluid_Cache_Frontend_VariableFrontend', array(), array(), '', FALSE));
		// $reflectionService->initialize($availableClassNames);

		$viewHelper = new Tx_Fluid_Core_Fixtures_TestViewHelper();
		$viewHelper->injectReflectionService($reflectionService);

		$expected = array(
			'param1' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('param1', 'integer', 'P1 Stuff', TRUE, null, TRUE),
			'param2' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('param2', 'array', 'P2 Stuff', TRUE, null, TRUE),
			'param3' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('param3', 'string', 'P3 Stuff', FALSE, 'default', TRUE),
		);

		$this->assertEquals($expected, $viewHelper->prepareArguments(), 'Annotation based arguments were not registered.');

	}
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function validateArgumentsCallsPrepareArguments() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_ViewHelper_AbstractViewHelper'), array('render', 'prepareArguments'), array(), '', FALSE);
		$viewHelper->injectReflectionService(t3lib_div::makeInstance('Tx_Extbase_Reflection_Service'));

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array()));
		$viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array()));

		$viewHelper->validateArguments();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validateArgumentsAcceptsAllObjectsImplemtingArrayAccessAsAnArray() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_ViewHelper_AbstractViewHelper'), array('render', 'prepareArguments'), array(), '', FALSE);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('test' => new ArrayObject)));
		$viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array('test' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('test', 'array', FALSE, 'documentation'))));
		$viewHelper->validateArguments();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function validateArgumentsCallsTheRightValidators() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_ViewHelper_AbstractViewHelper'), array('render', 'prepareArguments'), array(), '', FALSE);
		$viewHelper->injectReflectionService(t3lib_div::makeInstance('Tx_Extbase_Reflection_Service'));

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('test' => 'Value of argument')));

		$validatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->once())->method('createValidator')->with('string')->will($this->returnValue(new Tx_Extbase_Validation_Validator_TextValidator()));

		$viewHelper->injectValidatorResolver($validatorResolver);

		$viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array(
			'test' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition("test", "string", FALSE, "documentation")
		)));

		$viewHelper->validateArguments();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setControllerContextSetsTheControllerContext() {
		$controllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext');
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_ViewHelper_AbstractViewHelper'), array('render', 'prepareArguments'), array(), '', FALSE);

		$viewHelper->setControllerContext($controllerContext);
		$this->assertSame($viewHelper->_get('controllerContext'), $controllerContext);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setViewHelperVariableContainerSetsTheViewHelperVariableContainer() {
		$viewHelperVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer');
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_ViewHelper_AbstractViewHelper'), array('render', 'prepareArguments'), array(), '', FALSE);

		$viewHelper->setViewHelperVariableContainer($viewHelperVariableContainer);
		$this->assertSame($viewHelper->_get('viewHelperVariableContainer'), $viewHelperVariableContainer);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function objectAccessorPostProcessorDisabledSettingIsReturnedToCorrectly() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_ViewHelper_AbstractViewHelper'), array('render', 'prepareArguments'), array(), '', FALSE);

		$this->assertTrue($viewHelper->isObjectAccessorPostProcessorEnabled());

		$viewHelper->_set('objectAccessorPostProcessorEnabled', FALSE);
		$this->assertFalse($viewHelper->isObjectAccessorPostProcessorEnabled());

		$viewHelper->_set('objectAccessorPostProcessorEnabled', TRUE);
		$this->assertTrue($viewHelper->isObjectAccessorPostProcessorEnabled());
	}
}
?>