<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc;

use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RequestTest extends UnitTestCase
{
    /**
     * @test
     */
    public function aSingleArgumentCanBeSetWithWithArgumentAndRetrievedWithGetArgument(): void
    {
        $request = new Request();
        $request = $request->withArgument('someArgumentName', 'theValue');
        self::assertEquals('theValue', $request->getArgument('someArgumentName'));
    }

    /**
     * @test
     */
    public function withArgumentThrowsExceptionIfTheGivenArgumentNameIsAnEmptyString(): void
    {
        $this->expectException(InvalidArgumentNameException::class);
        $this->expectExceptionCode(1210858767);
        $request = new Request();
        $request = $request->withArgument('', 'theValue');
    }

    /**
     * @test
     */
    public function withArgumentsOverridesAllExistingArguments(): void
    {
        $arguments = ['key1' => 'value1', 'key2' => 'value2'];
        $request = new Request();
        $request = $request->withArgument('someKey', 'shouldBeOverridden');
        $request = $request->withArguments($arguments);
        $actualResult = $request->getArguments();
        self::assertEquals($arguments, $actualResult);
    }

    /**
     * @test
     */
    public function withArgumentShouldSetControllerExtensionNameIfPackageKeyIsGiven(): void
    {
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['withControllerExtensionName'])
            ->getMock();
        $request->method('withControllerExtensionName')->with('MyExtension');
        $request = $request->withArgument('@extension', 'MyExtension');
        self::assertFalse($request->hasArgument('@extension'));
    }

    /**
     * @test
     */
    public function withArgumentShouldSetControllerNameIfControllerIsGiven(): void
    {
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['withControllerName'])
            ->getMock();
        $request->method('withControllerName')->with('MyController');
        $request = $request->withArgument('@controller', 'MyController');
        self::assertFalse($request->hasArgument('@controller'));
    }

    /**
     * @test
     */
    public function withArgumentShouldSetControllerActionNameIfActionIsGiven(): void
    {
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['withControllerActionName'])
            ->getMock();
        $request->method('withControllerActionName')->with('foo');
        $request = $request->withArgument('@action', 'foo');
        self::assertFalse($request->hasArgument('@action'));
    }

    /**
     * @test
     */
    public function withArgumentShouldSetFormatIfFormatIsGiven(): void
    {
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['withFormat'])
            ->getMock();
        $request->method('withFormat')->with('txt');
        $request = $request->withArgument('@format', 'txt');
        self::assertFalse($request->hasArgument('@format'));
    }

    /**
     * @test
     */
    public function internalArgumentsShouldNotBeReturnedAsNormalArgument(): void
    {
        $request = new Request();
        $request = $request->withArgument('__referrer', 'foo');
        self::assertFalse($request->hasArgument('__referrer'));
    }

    /**
     * @test
     */
    public function multipleArgumentsCanBeSetWithWithArgumentsAndRetrievedWithGetArguments(): void
    {
        $arguments = [
            'firstArgument' => 'firstValue',
            'dænishÅrgument' => 'görman välju',
            '3a' => '3v',
        ];
        $request = new Request();
        $request = $request->withArguments($arguments);
        self::assertEquals($arguments, $request->getArguments());
    }

    /**
     * @test
     */
    public function hasArgumentTellsIfAnArgumentExists(): void
    {
        $request = new Request();
        $request = $request->withArgument('existingArgument', 'theValue');
        self::assertTrue($request->hasArgument('existingArgument'));
        self::assertFalse($request->hasArgument('notExistingArgument'));
    }

    /**
     * @test
     */
    public function theActionNameCanBeSetAndRetrieved(): void
    {
        $request = new Request();
        $request = $request->withControllerActionName('theAction');
        self::assertEquals('theAction', $request->getControllerActionName());
    }

    /**
     * @test
     */
    public function theRepresentationFormatCanBeSetAndRetrieved(): void
    {
        $request = new Request();
        $request = $request->withFormat('html');
        self::assertEquals('html', $request->getFormat());
    }

    /**
     * DataProvider for explodeObjectControllerName
     *
     * @return array
     */
    public function controllerArgumentsAndExpectedObjectName(): array
    {
        return [
            'Vendor TYPO3\CMS, extension, controller given' => [
                [
                    'extensionName' => 'Ext',
                    'controllerName' => 'Foo',
                ],
                'TYPO3\\CMS\\Ext\\Controller\\FooController',
            ],
            'Vendor TYPO3\CMS, extension, subpackage, controller given' => [
                [
                    'extensionName' => 'Fluid',
                    'controllerName' => 'Paginate',
                ],
                'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\Controller\\PaginateController',
            ],
            'Vendor VENDOR, extension, controller given' => [
                [
                    'extensionName' => 'Ext',
                    'controllerName' => 'Foo',
                ],
                'VENDOR\\Ext\\Controller\\FooController',
            ],
            'Vendor VENDOR, extension subpackage, controller given' => [
                [
                    'extensionName' => 'Ext',
                    'controllerName' => 'Foo',
                ],
                'VENDOR\\Ext\\ViewHelpers\\Widget\\Controller\\FooController',
            ],
        ];
    }

    /**
     * @dataProvider controllerArgumentsAndExpectedObjectName
     * @test
     */
    public function withControllerObjectNameResolvesControllerObjectNameArgumentsCorrectly(array $controllerArguments, string $controllerObjectName): void
    {
        $request = new Request();
        $request = $request->withControllerObjectName($controllerObjectName);
        $actualControllerArguments = [
            'extensionName' => $request->getControllerExtensionName(),
            'controllerName' => $request->getControllerName(),
        ];
        self::assertSame($controllerArguments, $actualControllerArguments);
    }
}
