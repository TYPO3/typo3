<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Mvc\Cli;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\Command;
use TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Tests\UnitDeprecated\Mvc\Cli\Fixture\Command\MockCCommandController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CommandTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

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

    /**
     * @return array
     */
    public function commandIdentifiers()
    {
        return [

            ['Tx\ExtensionKey\Command\CacheCommandController', 'flush', 'extension_key:cache:flush'],
            ['Tx\Ext\Command\CookieCommandController', 'bake', 'ext:cookie:bake'],
            ['Tx\OtherExtensionKey\Foo\Faa\Fuuum\Command\CoffeeCommandController', 'brew', 'other_extension_key:coffee:brew'],
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

        $expected = 'Longer Description' . LF .
            'Multine' . LF . LF .
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
