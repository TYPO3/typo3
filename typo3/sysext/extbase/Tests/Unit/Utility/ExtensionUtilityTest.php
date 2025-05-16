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

namespace TYPO3\CMS\Extbase\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Tests\Unit\Utility\Fixtures\ExtensionUtilityAccessibleProxy;
use TYPO3\CMS\Extbase\Tests\Unit\Utility\Fixtures\MyExtension\Controller\FirstController;
use TYPO3\CMS\Extbase\Tests\Unit\Utility\Fixtures\MyExtension\Controller\SecondController;
use TYPO3\CMS\Extbase\Tests\Unit\Utility\Fixtures\MyExtension\Controller\ThirdController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExtensionUtilityTest extends UnitTestCase
{
    #[Test]
    public function configurePluginWorksForMinimalisticSetup(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin(
            'MyExtension',
            'Pi1',
            [FirstController::class => ['index']],
            [],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.myextension_pi1 =< lib.contentElement', $staticTypoScript);
        self::assertStringContainsString('
    20 = EXTBASEPLUGIN
    20 {
        extensionName = MyExtension
        pluginName = Pi1', $staticTypoScript);
        self::assertStringNotContainsString('USER_INT', $staticTypoScript);
    }

    #[Test]
    public function configurePluginCreatesCorrectDefaultTypoScriptSetup(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin(
            'MyExtension',
            'Pi1',
            [FirstController::class => ['index']],
            [],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.myextension_pi1 =< lib.contentElement', $staticTypoScript);
    }

    #[Test]
    public function configurePluginWorksForASingleControllerAction(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin(
            'MyExtension',
            'Pi1',
            [FirstController::class => ['index']],
            [],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.myextension_pi1 =< lib.contentElement', $staticTypoScript);
        self::assertStringContainsString('
    20 = EXTBASEPLUGIN
    20 {
        extensionName = MyExtension
        pluginName = Pi1', $staticTypoScript);
        $expectedResult = [
            'controllers' => [
                FirstController::class => [
                    'className' => FirstController::class,
                    'alias' => 'First',
                    'actions' => ['index'],
                ],
            ],
            'pluginType' => ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        ];
        self::assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
    }

    #[Test]
    public function configurePluginThrowsExceptionIfExtensionNameIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1239891990);
        ExtensionUtility::configurePlugin(
            '',
            'SomePlugin',
            ['FirstController' => ['index']],
            [],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );
    }

    #[Test]
    public function configurePluginThrowsExceptionIfPluginNameIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1239891988);
        ExtensionUtility::configurePlugin(
            'MyExtension',
            '',
            ['FirstController' => ['index']],
            [],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );
    }

    #[Test]
    public function configurePluginRespectsDefaultActionAsANonCacheableAction(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin(
            'MyExtension',
            'Pi1',
            [FirstController::class => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update']],
            [FirstController::class => ['index', 'show']],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.myextension_pi1 =< lib.contentElement', $staticTypoScript);
        self::assertStringContainsString('
    20 = EXTBASEPLUGIN
    20 {
        extensionName = MyExtension
        pluginName = Pi1', $staticTypoScript);
        $expectedResult = [
            'controllers' => [
                FirstController::class => [
                    'className' => FirstController::class,
                    'alias' => 'First',
                    'actions' => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                    'nonCacheableActions' => ['index', 'show'],
                ],
            ],
            'pluginType' => ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        ];
        self::assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
    }

    #[Test]
    public function configurePluginRespectsNonDefaultActionAsANonCacheableAction(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin(
            'MyExtension',
            'Pi1',
            [FirstController::class => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update']],
            [FirstController::class => ['new', 'show']],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.myextension_pi1 =< lib.contentElement', $staticTypoScript);
        self::assertStringContainsString('
    20 = EXTBASEPLUGIN
    20 {
        extensionName = MyExtension
        pluginName = Pi1', $staticTypoScript);
        $expectedResult = [
            'controllers' => [
                FirstController::class => [
                    'className' => FirstController::class,
                    'alias' => 'First',
                    'actions' => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                    'nonCacheableActions' => ['new', 'show'],
                ],
            ],
            'pluginType' => ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        ];
        self::assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
    }

    #[Test]
    public function configurePluginWorksForMultipleControllerActionsWithCacheableActionAsDefault(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin(
            'MyExtension',
            'Pi1',
            [
                FirstController::class => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                SecondController::class => ['index', 'show', 'delete'],
                ThirdController::class => ['create'],
            ],
            [
                FirstController::class => ['new', 'create', 'edit', 'update'],
                ThirdController::class => ['create'],
            ],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );
        $expectedResult = [
            'controllers' => [
                FirstController::class => [
                    'className' => FirstController::class,
                    'alias' => 'First',
                    'actions' => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                    'nonCacheableActions' => ['new', 'create', 'edit', 'update'],
                ],
                SecondController::class => [
                    'className' => SecondController::class,
                    'alias' => 'Second',
                    'actions' => ['index', 'show', 'delete'],
                ],
                ThirdController::class => [
                    'className' => ThirdController::class,
                    'alias' => 'Third',
                    'actions' => ['create'],
                    'nonCacheableActions' => ['create'],
                ],
            ],
            'pluginType' => ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        ];
        self::assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
    }

    #[Test]
    public function configurePluginWorksForMultipleControllerActionsAsArrayWithCacheableActionAsDefault(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin(
            'MyExtension',
            'Pi1',
            [
                FirstController::class => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                SecondController::class => ['index', 'show', 'delete'],
                ThirdController::class => ['create'],
            ],
            [
                FirstController::class => ['new', 'create', 'edit', 'update'],
                ThirdController::class => ['create'],
            ],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );
        $expectedResult = [
            'controllers' => [
                FirstController::class => [
                    'className' => FirstController::class,
                    'alias' => 'First',
                    'actions' => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                    'nonCacheableActions' => ['new', 'create', 'edit', 'update'],
                ],
                SecondController::class => [
                    'className' => SecondController::class,
                    'alias' => 'Second',
                    'actions' => ['index', 'show', 'delete'],
                ],
                ThirdController::class => [
                    'className' => ThirdController::class,
                    'alias' => 'Third',
                    'actions' => ['create'],
                    'nonCacheableActions' => ['create'],
                ],
            ],
            'pluginType' => ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        ];
        self::assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
    }

    #[Test]
    public function configurePluginWorksForMultipleControllerActionsWithNonCacheableActionAsDefault(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin(
            'MyExtension',
            'Pi1',
            [
                FirstController::class => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                SecondController::class => ['index', 'show', 'delete'],
                ThirdController::class => ['create'],
            ],
            [
                FirstController::class => ['index', 'new', 'create', 'edit', 'update'],
                SecondController::class => ['delete'],
                ThirdController::class => ['create'],
            ],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );
        $expectedResult = [
            'controllers' => [
                FirstController::class => [
                    'className' => FirstController::class,
                    'alias' => 'First',
                    'actions' => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                    'nonCacheableActions' => ['index', 'new', 'create', 'edit', 'update'],
                ],
                SecondController::class => [
                    'className' => SecondController::class,
                    'alias' => 'Second',
                    'actions' => ['index', 'show', 'delete'],
                    'nonCacheableActions' => ['delete'],
                ],
                ThirdController::class => [
                    'className' => ThirdController::class,
                    'alias' => 'Third',
                    'actions' => ['create'],
                    'nonCacheableActions' => ['create'],
                ],
            ],
            'pluginType' => ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        ];
        self::assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
    }

    /**
     * Tests method combination of registerPlugin() and its dependency addPlugin() to
     * verify plugin icon path resolving works.
     */
    #[Test]
    #[IgnoreDeprecations]
    public function registerPluginSetsDefaultIconIfUsingUnderscoredExtensionNameAndIconPathNotGiven(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [];
        ExtensionUtility::registerPlugin(
            'indexed_search',
            'Pi2',
            'Testing'
        );
        self::assertSame(
            'content-plugin',
            $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0]['icon']
        );
        self::assertSame(
            'indexedsearch_pi2',
            $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0]['value']
        );
    }

    #[Test]
    #[IgnoreDeprecations]
    public function registerPluginMethodReturnsPluginSignature(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [];
        $result = ExtensionUtility::registerPlugin(
            'indexed_search',
            'Pi2',
            'Testing'
        );
        self::assertSame('indexedsearch_pi2', $result);
    }

    /**
     * Tests method combination of registerPlugin() and its dependency addPlugin() to
     * verify plugin icon path resolving works.
     */
    #[Test]
    #[IgnoreDeprecations]
    public function registerPluginSetsDefaultIconIfUsingUpperCameCasedExtensionNameAndIconPathNotGiven(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [];
        ExtensionUtility::registerPlugin(
            'IndexedSearch',
            'Pi2',
            'Testing'
        );
        self::assertSame(
            'content-plugin',
            $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0]['icon']
        );
        self::assertSame(
            'indexedsearch_pi2',
            $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0]['value']
        );
    }

    /**
     * Tests method combination of registerPlugin() and its dependency addPlugin() to
     * verify plugin icon path resolving works.
     */
    #[Test]
    #[IgnoreDeprecations]
    public function registerPluginTriggersAddPluginWhichSetsPluginIconPathIfIconPathIsGiven(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [];
        ExtensionUtility::registerPlugin(
            'IndexedSearch',
            'Pi2',
            'Testing',
            'EXT:indexed_search/foo.gif'
        );
        self::assertEquals(
            'EXT:indexed_search/foo.gif',
            $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0]['icon']
        );
    }

    public static function checkResolveControllerAliasFromControllerClassNameDataProvider(): array
    {
        return [
            'Class in root namespace without controller suffix' => [
                '',
                'Foo',
            ],
            'Class in root namespace without controller suffix (2)' => [
                '',
                'FooBarBazQuxBlaBlub',
            ],
            'Controller in root namespace' => [
                'Foo',
                'FooController',
            ],
            'Controller in root namespace (lowercase)' => [
                'foo',
                'fooController',
            ],
            'Controller in namespace' => [
                'Foo',
                'TYPO3\\CMS\\Ext\\Controller\\FooController',
            ],
            'Controller in arbitrary namespace' => [
                'Foo',
                'Foo\\Bar\\baz\\qUx\\FooController',
            ],
            'Controller with lowercase suffix' => [
                '',
                'Foo\\Bar\\baz\\qUx\\Foocontroller',
            ],
            'Controller in arbitrary namespace with subfolder in Controller namespace' => [
                'Baz\\Foo',
                'Foo\\Bar\\Controller\\Baz\\FooController',
            ],
        ];
    }

    #[DataProvider('checkResolveControllerAliasFromControllerClassNameDataProvider')]
    #[Test]
    public function checkResolveControllerAliasFromControllerClassName(string $expectedControllerAlias, string $controllerClassName): void
    {
        self::assertEquals(
            $expectedControllerAlias,
            ExtensionUtilityAccessibleProxy::resolveControllerAliasFromControllerClassName($controllerClassName)
        );
    }
}
