<?php
/*                                                                        *
 * This script belongs to the Extbase framework.                            *
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

/**
 * Testcase for the CLI Command class
 */
class Tx_Extbase_Tests_Unit_MVC_CLI_CommandTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_MVC_CLI_Command
	 */
	protected $command;

	/**
	 * @var Tx_Extbase_Reflection_MethodReflection
	 */
	protected $mockMethodReflection;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->command = $this->getAccessibleMock('Tx_Extbase_MVC_CLI_Command', array('getCommandMethodReflection'), array(), '', FALSE);
		$this->mockMethodReflection = $this->getMock('Tx_Extbase_Reflection_MethodReflection', array(), array(), '', FALSE);
		$this->command->expects($this->any())->method('getCommandMethodReflection')->will($this->returnValue($this->mockMethodReflection));
		$this->mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$this->command->injectObjectManager($this->mockObjectManager);
	}

	/**
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function commandIdentifiers() {
		return array(
			array('Tx_ExtensionKey_Command_CacheCommandController', 'flush', 'extension_key:cache:flush'),
			array('Tx_Ext_Command_CookieCommandController', 'bake', 'ext:cookie:bake')
		);
	}

	/**
	 * @test
	 * @dataProvider commandIdentifiers
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructRendersACommandIdentifierByTheGivenControllerAndCommandName($controllerClassName, $commandName, $expectedCommandIdentifier) {
		$command = new Tx_Extbase_MVC_CLI_Command($controllerClassName, $commandName);
		$this->assertEquals($expectedCommandIdentifier, $command->getCommandIdentifier());
	}

	/**
	 * @return array
	 */
	public function invalidCommandClassNames() {
		return array(
			array(''), // CommandClassName must not be empty
			array('Tx_OtherExtensionKey_Foo_Faa_Fuuum_Command_CoffeeCommandController'), // CommandControllers in subpackages are not supported
			array('Foo') // CommandClassName must start with "Tx_"
		);
	}

	/**
	 * @test
	 * @dataProvider invalidCommandClassNames
	 * @expectedException InvalidArgumentException
	 */
	public function constructThrowsExceptionIfCommandClassNameIsInvalid($controllerClassName) {
		new Tx_Extbase_MVC_CLI_Command($controllerClassName, 'foo');
	}


	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasArgumentsReturnsFalseIfCommandExpectsNoArguments() {
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue(array()));
		$this->assertFalse($this->command->hasArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasArgumentsReturnsTrueIfCommandExpectsArguments() {
		$mockParameterReflection = $this->getMock('Tx_Extbase_Reflection_ParameterReflection', array(), array(), '', FALSE);
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue(array($mockParameterReflection)));
		$this->assertTrue($this->command->hasArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getArgumentDefinitionsReturnsEmptyArrayIfCommandExpectsNoArguments() {
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue(array()));
		$this->assertSame(array(), $this->command->getArgumentDefinitions());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getArgumentDefinitionsReturnsArrayOfArgumentDefinitionIfCommandExpectsArguments() {
		$mockParameterReflection = $this->getMock('Tx_Extbase_Reflection_ParameterReflection', array(), array(), '', FALSE);
		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service');
		$mockMethodParameters = array('argument1' => array('optional' => FALSE), 'argument2' => array('optional' => TRUE));
		$mockReflectionService->expects($this->atLeastOnce())->method('getMethodParameters')->will($this->returnValue($mockMethodParameters));
		$this->command->injectReflectionService($mockReflectionService);
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue(array($mockParameterReflection)));
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getTagsValues')->will($this->returnValue(array('param' => array('@param $argument1 argument1 description', '@param $argument2 argument2 description'))));

		$mockCommandArgumentDefinition1 = $this->getMock('Tx_Extbase_MVC_CLI_CommandArgumentDefinition', array(), array(), '', FALSE);
		$mockCommandArgumentDefinition2 = $this->getMock('Tx_Extbase_MVC_CLI_CommandArgumentDefinition', array(), array(), '', FALSE);
		$this->mockObjectManager->expects($this->at(0))->method('get')->with('Tx_Extbase_MVC_CLI_CommandArgumentDefinition', 'argument1', TRUE, 'argument1 description')->will($this->returnValue($mockCommandArgumentDefinition1));
		$this->mockObjectManager->expects($this->at(1))->method('get')->with('Tx_Extbase_MVC_CLI_CommandArgumentDefinition', 'argument2', FALSE, 'argument2 description')->will($this->returnValue($mockCommandArgumentDefinition2));

		$expectedResult = array($mockCommandArgumentDefinition1, $mockCommandArgumentDefinition2);
		$actualResult = $this->command->getArgumentDefinitions();
		$this->assertSame($expectedResult, $actualResult);
	}
}
?>