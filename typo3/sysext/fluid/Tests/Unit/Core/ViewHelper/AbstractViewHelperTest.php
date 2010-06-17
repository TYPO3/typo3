<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(dirname(__FILE__) . '/../Fixtures/TestViewHelper.php');

/**
 * Testcase for AbstractViewHelper
 *
 * @version $Id: AbstractViewHelperTest.php 4483 2010-06-10 13:57:32Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Core_ViewHelper_AbstractViewHelperTest extends Tx_Extbase_BaseTestCase {
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function argumentsCanBeRegistered() {
		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);

		$viewHelper = $this->getAccessibleMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper', array('render'), array(), '', FALSE);
		$viewHelper->injectReflectionService($mockReflectionService);

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
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception
	 */
	public function registeringTheSameArgumentNameAgainThrowsException() {
		$viewHelper = $this->getAccessibleMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper', array('render'), array(), '', FALSE);

		$name = "shortName";
		$description = "Example desc";
		$type = "string";
		$isRequired = TRUE;

		$viewHelper->_call('registerArgument', $name, $type, $isRequired, $description);
		$viewHelper->_call('registerArgument', $name, "integer", $isRequired, $description);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function prepareArgumentsCallsInitializeArguments() {
		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);

		$viewHelper = $this->getAccessibleMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper', array('render', 'initializeArguments'), array(), '', FALSE);
		$viewHelper->injectReflectionService($mockReflectionService);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array()));
		$viewHelper->expects($this->once())->method('initializeArguments');

		$viewHelper->prepareArguments();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function prepareArgumentsRegistersAnnotationBasedArgumentsWithDescriptionIfDebugModeIsEnabled() { $this->markTestIncomplete("Works differently in v4.");

		Tx_Fluid_Fluid::$debugMode = TRUE;

		$availableClassNames = array(
			'Tx_Fluid_Core_Fixtures_TestViewHelper',
		);
		$reflectionService = new Tx_Extbase_Reflection_Service();
		$reflectionService->setStatusCache($this->getMock('Tx_Fluid_Cache_Frontend_StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('Tx_Fluid_Cache_Frontend_VariableFrontend', array(), array(), '', FALSE));
		// $reflectionService->initialize($availableClassNames);

		$viewHelper = new Tx_Fluid_Core_Fixtures_TestViewHelper();
		$viewHelper->injectReflectionService($reflectionService);

		$expected = array(
			'param1' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('param1', 'integer', 'P1 Stuff', TRUE, null, TRUE),
			'param2' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('param2', 'array', 'P2 Stuff', TRUE, null, TRUE),
			'param3' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('param3', 'string', 'P3 Stuff', FALSE, 'default', TRUE),
		);

		$this->assertEquals($expected, $viewHelper->prepareArguments(), 'Annotation based arguments were not registered.');

		Tx_Fluid_Fluid::$debugMode = FALSE;
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function prepareArgumentsRegistersAnnotationBasedArgumentsWithoutDescriptionIfDebugModeIsDisabled() { $this->markTestIncomplete("Works differently in v4.");

		Tx_Fluid_Fluid::$debugMode = FALSE;

		$availableClassNames = array(
			'Tx_Fluid_Core_Fixtures_TestViewHelper',
		);
		$reflectionService = new Tx_Extbase_Reflection_Service();
		$reflectionService->setStatusCache($this->getMock('Tx_Fluid_Cache_Frontend_StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('Tx_Fluid_Cache_Frontend_VariableFrontend', array(), array(), '', FALSE));
		// $reflectionService->initialize($availableClassNames);

		$viewHelper = new Tx_Fluid_Core_Fixtures_TestViewHelper();
		$viewHelper->injectReflectionService($reflectionService);

		$expected = array(
			'param1' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('param1', 'integer', '', TRUE, null, TRUE),
			'param2' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('param2', 'array', '', TRUE, null, TRUE),
			'param3' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('param3', 'string', '', FALSE, 'default', TRUE),
		);

		$this->assertEquals($expected, $viewHelper->prepareArguments(), 'Annotation based arguments were not registered.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function validateArgumentsCallsPrepareArguments() {
		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);

		$viewHelper = $this->getAccessibleMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper', array('render', 'prepareArguments'), array(), '', FALSE);
		$viewHelper->injectReflectionService($mockReflectionService);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array()));
		$viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array()));

		$viewHelper->validateArguments();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validateArgumentsAcceptsAllObjectsImplemtingArrayAccessAsAnArray() {
		$viewHelper = $this->getAccessibleMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper', array('render', 'prepareArguments'), array(), '', FALSE);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('test' => new ArrayObject)));
		$viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array('test' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('test', 'array', FALSE, 'documentation'))));
		$viewHelper->validateArguments();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function validateArgumentsCallsTheRightValidators() {
		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);

		$viewHelper = $this->getAccessibleMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper', array('render', 'prepareArguments'), array(), '', FALSE);
		$viewHelper->injectReflectionService($mockReflectionService);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('test' => 'Value of argument')));

		$viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array(
			'test' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition("test", "string", FALSE, "documentation")
		)));

		$viewHelper->validateArguments();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException \InvalidArgumentException
	 */
	public function validateArgumentsCallsTheRightValidatorsAndThrowsExceptionIfValidationIsWrong() {
		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);

		$viewHelper = $this->getAccessibleMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper', array('render', 'prepareArguments'), array(), '', FALSE);
		$viewHelper->injectReflectionService($mockReflectionService);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('test' => "test")));

		$viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array(
			'test' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition("test", "stdClass", FALSE, "documentation")
		)));

		$viewHelper->validateArguments();
	}


	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setControllerContextSetsTheControllerContext() {
		$controllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext', array(), array(), '', FALSE);
		$viewHelper = $this->getAccessibleMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper', array('render', 'prepareArguments'), array(), '', FALSE);

		$viewHelper->setControllerContext($controllerContext);
		$this->assertSame($viewHelper->_get('controllerContext'), $controllerContext);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setViewHelperVariableContainerSetsTheViewHelperVariableContainer() {
		$viewHelperVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer');
		$viewHelper = $this->getAccessibleMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper', array('render', 'prepareArguments'), array(), '', FALSE);

		$viewHelper->setViewHelperVariableContainer($viewHelperVariableContainer);
		$this->assertSame($viewHelper->_get('viewHelperVariableContainer'), $viewHelperVariableContainer);
	}

}
?>