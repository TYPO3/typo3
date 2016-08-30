<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Cli;

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
 * Test case
 */
class CommandTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Cli\Command|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $command;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\MethodReflection
     */
    protected $mockMethodReflection;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->command = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Cli\Command::class, ['getCommandMethodReflection'], [], '', false);
        $this->mockMethodReflection = $this->getMock(\TYPO3\CMS\Extbase\Reflection\MethodReflection::class, [], [], '', false);
        $this->command->expects($this->any())->method('getCommandMethodReflection')->will($this->returnValue($this->mockMethodReflection));
        $this->mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $this->command->_set('objectManager', $this->mockObjectManager);
    }

    /**
     * @return array
     */
    public function commandIdentifiers()
    {
        return [
            ['Tx_ExtensionKey_Command_CacheCommandController', 'flush', 'extension_key:cache:flush'],
            ['Tx_Ext_Command_CookieCommandController', 'bake', 'ext:cookie:bake'],
            ['Tx_OtherExtensionKey_Foo_Faa_Fuuum_Command_CoffeeCommandController', 'brew', 'other_extension_key:coffee:brew'],
        ];
    }

    /**
     * @test
     * @dataProvider commandIdentifiers
     * @param string $controllerClassName
     * @param string $commandName
     * @param string $expectedCommandIdentifier
     */
    public function constructRendersACommandIdentifierByTheGivenControllerAndCommandName($controllerClassName, $commandName, $expectedCommandIdentifier)
    {
        $command = new \TYPO3\CMS\Extbase\Mvc\Cli\Command($controllerClassName, $commandName);
        $this->assertEquals($expectedCommandIdentifier, $command->getCommandIdentifier());
    }

    /**
     * @return array
     */
    public function invalidCommandClassNames()
    {
        return [
            [''],
            // CommandClassName must not be empty
            ['Foo']
        ];
    }

    /**
     * @test
     * @dataProvider invalidCommandClassNames
     * @expectedException \InvalidArgumentException
     * @param string $controllerClassName
     */
    public function constructThrowsExceptionIfCommandClassNameIsInvalid($controllerClassName)
    {
        new \TYPO3\CMS\Extbase\Mvc\Cli\Command($controllerClassName, 'foo');
    }

    /**
     * @test
     */
    public function hasArgumentsReturnsFalseIfCommandExpectsNoArguments()
    {
        $this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue([]));
        $this->assertFalse($this->command->hasArguments());
    }

    /**
     * @test
     */
    public function hasArgumentsReturnsTrueIfCommandExpectsArguments()
    {
        $mockParameterReflection = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ParameterReflection::class, [], [], '', false);
        $this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue([$mockParameterReflection]));
        $this->assertTrue($this->command->hasArguments());
    }

    /**
     * @test
     */
    public function getArgumentDefinitionsReturnsEmptyArrayIfCommandExpectsNoArguments()
    {
        $this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue([]));
        $this->assertSame([], $this->command->getArgumentDefinitions());
    }

    /**
     * @test
     */
    public function getArgumentDefinitionsReturnsArrayOfArgumentDefinitionIfCommandExpectsArguments()
    {
        $mockParameterReflection = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ParameterReflection::class, [], [], '', false);
        $mockReflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $mockMethodParameters = ['argument1' => ['optional' => false], 'argument2' => ['optional' => true]];
        $mockReflectionService->expects($this->atLeastOnce())->method('getMethodParameters')->will($this->returnValue($mockMethodParameters));
        $this->command->_set('reflectionService', $mockReflectionService);
        $this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue([$mockParameterReflection]));
        $this->mockMethodReflection->expects($this->atLeastOnce())->method('getTagsValues')->will($this->returnValue(['param' => ['@param $argument1 argument1 description', '@param $argument2 argument2 description']]));
        $mockCommandArgumentDefinition1 = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition::class, [], [], '', false);
        $mockCommandArgumentDefinition2 = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition::class, [], [], '', false);
        $this->mockObjectManager->expects($this->at(0))->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition::class, 'argument1', true, 'argument1 description')->will($this->returnValue($mockCommandArgumentDefinition1));
        $this->mockObjectManager->expects($this->at(1))->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition::class, 'argument2', false, 'argument2 description')->will($this->returnValue($mockCommandArgumentDefinition2));
        $expectedResult = [$mockCommandArgumentDefinition1, $mockCommandArgumentDefinition2];
        $actualResult = $this->command->getArgumentDefinitions();
        $this->assertSame($expectedResult, $actualResult);
    }
}
