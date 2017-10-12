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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\Command;
use TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Tests\Unit\Mvc\Cli\Fixture\Command\MockCCommandController;

/**
 * Test case
 */
class CommandTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var array
     */
    protected $singletonInstances;

    protected function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        GeneralUtility::purgeInstances();
    }

    protected function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
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
     * @param string $controllerClassName
     */
    public function constructThrowsExceptionIfCommandClassNameIsInvalid($controllerClassName)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1438782187);
        new \TYPO3\CMS\Extbase\Mvc\Cli\Command($controllerClassName, 'foo');
    }

    public function testIsInternal()
    {
        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'empty'
        );

        static::assertFalse($commandController->isInternal());

        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'internal'
        );

        static::assertTrue($commandController->isInternal());
    }

    public function testIsCliOnly()
    {
        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'empty'
        );

        static::assertFalse($commandController->isCliOnly());

        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'cliOnly'
        );

        static::assertTrue($commandController->isCliOnly());
    }

    public function testIsFlushinCaches()
    {
        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'empty'
        );

        static::assertFalse($commandController->isFlushingCaches());

        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'flushingCaches'
        );

        static::assertTrue($commandController->isFlushingCaches());
    }

    public function testHasArguments()
    {
        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'empty'
        );

        static::assertFalse($commandController->hasArguments());

        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'withArguments'
        );

        static::assertTrue($commandController->hasArguments());
    }

    public function testGetArgumentDefinitions()
    {
        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'empty'
        );

        static::assertSame([], $commandController->getArgumentDefinitions());

        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'withArguments'
        );

        $expected = [
            new CommandArgumentDefinition('foo', true, 'FooParamDescription'),
            new CommandArgumentDefinition('bar', false, 'BarParamDescription'),
        ];

        static::assertEquals($expected, $commandController->getArgumentDefinitions());
    }

    public function testGetDescription()
    {
        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'empty'
        );

        static::assertSame('', $commandController->getDescription());

        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'withDescription'
        );

        $expected = 'Longer Description' . PHP_EOL .
            'Multine' . PHP_EOL . PHP_EOL .
            'Much Multiline';

        static::assertEquals($expected, $commandController->getDescription());
    }

    public function testGetShortDescription()
    {
        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'empty'
        );

        static::assertSame('', $commandController->getShortDescription());

        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'withDescription'
        );

        $expected = 'Short Description';

        static::assertEquals($expected, $commandController->getShortDescription());
    }

    public function testGetRelatedCommandIdentifiers()
    {
        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'empty'
        );

        static::assertSame([], $commandController->getRelatedCommandIdentifiers());

        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'relatedCommandIdentifiers'
        );

        $expected = ['Foo:Bar:Baz'];
        static::assertEquals($expected, $commandController->getRelatedCommandIdentifiers());
    }
}
