<?php

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

use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Property\TypeConverter\ArrayConverter;
use TYPO3\CMS\Extbase\Tests\Unit\Utility\Fixtures\MyExtension\Controller\FirstController;
use TYPO3\CMS\Extbase\Tests\Unit\Utility\Fixtures\MyExtension\Controller\SecondController;
use TYPO3\CMS\Extbase\Tests\Unit\Utility\Fixtures\MyExtension\Controller\ThirdController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\PaginateController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Extbase\Utility\ExtensionUtility
 */
class ExtensionUtilityTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->tmpl = new \stdClass();
        $GLOBALS['TSFE']->tmpl->setup = [];
        $GLOBALS['TSFE']->tmpl->setup['tt_content.']['list.']['20.'] = [
            '9' => 'CASE',
            '9.' => [
                'key.' => [
                    'field' => 'layout'
                ],
                0 => '< plugin.tt_news'
            ],
            'extensionname_someplugin' => 'USER',
            'extensionname_someplugin.' => [
                'userFunc' => Bootstrap::class . '->run',
                'extensionName' => 'ExtensionName',
                'pluginName' => 'SomePlugin'
            ],
            'someotherextensionname_secondplugin' => 'USER',
            'someotherextensionname_secondplugin.' => [
                'userFunc' => Bootstrap::class . '->run',
                'extensionName' => 'SomeOtherExtensionName',
                'pluginName' => 'SecondPlugin'
            ],
            'extensionname_thirdplugin' => 'USER',
            'extensionname_thirdplugin.' => [
                'userFunc' => Bootstrap::class . '->run',
                'extensionName' => 'ExtensionName',
                'pluginName' => 'ThirdPlugin'
            ]
        ];
    }

    /**
     * @test
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin
     */
    public function configurePluginWorksForMinimalisticSetup()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', [FirstController::class => 'index']);
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
        self::assertStringContainsString('
	userFunc = TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run
	extensionName = MyExtension
	pluginName = Pi1', $staticTypoScript);
        self::assertStringNotContainsString('USER_INT', $staticTypoScript);
    }

    /**
     * @test
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin
     */
    public function configurePluginCreatesCorrectDefaultTypoScriptSetup()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', [FirstController::class => 'index']);
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
    }

    /**
     * @test
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin
     */
    public function configurePluginWorksForASingleControllerAction()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', [
            FirstController::class => 'index'
        ]);
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
        self::assertStringContainsString('
	extensionName = MyExtension
	pluginName = Pi1', $staticTypoScript);
        $expectedResult = [
            'controllers' => [
                FirstController::class => [
                    'className' => FirstController::class,
                    'alias' => 'First',
                    'actions' => ['index']
                ]
            ],
            'pluginType' => 'list_type'
        ];
        self::assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
    }

    /**
     * @test
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin
     */
    public function configurePluginThrowsExceptionIfExtensionNameIsEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1239891990);
        ExtensionUtility::configurePlugin('', 'SomePlugin', [
            'FirstController' => 'index'
        ]);
    }

    /**
     * @test
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin
     */
    public function configurePluginThrowsExceptionIfPluginNameIsEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1239891988);
        ExtensionUtility::configurePlugin('MyExtension', '', [
            'FirstController' => 'index'
        ]);
    }

    /**
     * @test
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin
     */
    public function configurePluginRespectsDefaultActionAsANonCacheableAction()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', [
            FirstController::class => 'index,show,new, create,delete,edit,update'
        ], [
            FirstController::class => 'index,show'
        ]);
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
        self::assertStringContainsString('
	extensionName = MyExtension
	pluginName = Pi1', $staticTypoScript);
        $expectedResult = [
            'controllers' => [
                FirstController::class => [
                    'className' => FirstController::class,
                    'alias' => 'First',
                    'actions' => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                    'nonCacheableActions' => ['index', 'show']
                ]
            ],
            'pluginType' => 'list_type'
        ];
        self::assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
    }

    /**
     * @test
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin
     */
    public function configurePluginRespectsNonDefaultActionAsANonCacheableAction()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', [
            FirstController::class => 'index,show,new, create,delete,edit,update'
        ], [
            FirstController::class => 'new,show'
        ]);
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
        self::assertStringContainsString('
	extensionName = MyExtension
	pluginName = Pi1', $staticTypoScript);
        $expectedResult = [
            'controllers' => [
                FirstController::class => [
                    'className' => FirstController::class,
                    'alias' => 'First',
                    'actions' => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                    'nonCacheableActions' => ['new', 'show']
                ]
            ],
            'pluginType' => 'list_type'
        ];
        self::assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
    }

    /**
     * @test
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin
     */
    public function configurePluginWorksForMultipleControllerActionsWithCacheableActionAsDefault()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', [
            FirstController::class => 'index,show,new,create,delete,edit,update',
            SecondController::class => 'index,show,delete',
            ThirdController::class => 'create'
        ], [
            FirstController::class => 'new,create,edit,update',
            ThirdController::class => 'create'
        ]);
        $expectedResult = [
            'controllers' => [
                FirstController::class => [
                    'className' => FirstController::class,
                    'alias' => 'First',
                    'actions' => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                    'nonCacheableActions' => ['new', 'create', 'edit', 'update']
                ],
                SecondController::class => [
                    'className' => SecondController::class,
                    'alias' => 'Second',
                    'actions' => ['index', 'show', 'delete']
                ],
                ThirdController::class => [
                    'className' => ThirdController::class,
                    'alias' => 'Third',
                    'actions' => ['create'],
                    'nonCacheableActions' => ['create']
                ]
            ],
            'pluginType' => 'list_type'
        ];
        self::assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
    }

    /**
     * @test
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin
     */
    public function configurePluginWorksForMultipleControllerActionsWithNonCacheableActionAsDefault()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', [
            FirstController::class => 'index,show,new,create,delete,edit,update',
            SecondController::class => 'index,show,delete',
            ThirdController::class => 'create'
        ], [
            FirstController::class => 'index,new,create,edit,update',
            SecondController::class => 'delete',
            ThirdController::class => 'create'
        ]);
        $expectedResult = [
            'controllers' => [
                FirstController::class => [
                    'className' => FirstController::class,
                    'alias' => 'First',
                    'actions' => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                    'nonCacheableActions' => ['index', 'new', 'create', 'edit', 'update']
                ],
                SecondController::class => [
                    'className' => SecondController::class,
                    'alias' => 'Second',
                    'actions' => ['index', 'show', 'delete'],
                    'nonCacheableActions' => ['delete']
                ],
                ThirdController::class => [
                    'className' => ThirdController::class,
                    'alias' => 'Third',
                    'actions' => ['create'],
                    'nonCacheableActions' => ['create']
                ]
            ],
            'pluginType' => 'list_type'
        ];
        self::assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
    }

    /**
     * Tests method combination of registerPlugin() and its dependency addPlugin() to
     * verify plugin icon path resolving works.
     *
     * @test
     */
    public function registerPluginTriggersAddPluginWhichSetsPluginIconPathIfUsingUnderscoredExtensionNameAndIconPathNotGiven()
    {
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [];
        ExtensionUtility::registerPlugin(
            'indexed_search',
            'Pi2',
            'Testing'
        );
        self::assertEquals(
            'EXT:indexed_search/Resources/Public/Icons/Extension.png',
            $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0][2]
        );
        self::assertSame(
            'indexedsearch_pi2',
            $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0][1]
        );
    }

    /**
     * Tests method combination of registerPlugin() and its dependency addPlugin() to
     * verify plugin icon path resolving works.
     *
     * @test
     */
    public function registerPluginTriggersAddPluginWhichSetsPluginIconPathIfUsingUpperCameCasedExtensionNameAndIconPathNotGiven()
    {
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [];
        ExtensionUtility::registerPlugin(
            'IndexedSearch',
            'Pi2',
            'Testing'
        );
        self::assertEquals(
            'EXT:indexed_search/Resources/Public/Icons/Extension.png',
            $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0][2]
        );
        self::assertSame(
            'indexedsearch_pi2',
            $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0][1]
        );
    }

    /**
     * Tests method combination of registerPlugin() and its dependency addPlugin() to
     * verify plugin icon path resolving works.
     *
     * @test
     */
    public function registerPluginTriggersAddPluginWhichSetsPluginIconPathIfIconPathIsGiven()
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
            $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0][2]
        );
    }

    /**
     * A type converter added several times with the exact same class name must
     * not be added more than once to the global array.
     *
     * @test
     */
    public function sameTypeConvertersRegisteredAreAddedOnlyOnce()
    {
        $typeConverterClassName = ArrayConverter::class;

        // the Extbase EXTCONF is not set at all at this point
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'] = [];

        ExtensionUtility::registerTypeConverter($typeConverterClassName);

        self::assertEquals($typeConverterClassName, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'][0]);
        self::assertEquals(1, count($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters']));

        ExtensionUtility::registerTypeConverter($typeConverterClassName);
        self::assertEquals(1, count($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters']));
    }

    /**
     * DataProvider for explodeObjectControllerName
     *
     * @return array
     */
    public function controllerArgumentsAndExpectedObjectName()
    {
        return [
            'Vendor TYPO3\CMS, extension, controller given' => [
                [
                    'vendorName' => 'TYPO3\\CMS',
                    'extensionName' => 'Ext',
                    'subpackageKey' => '',
                    'controllerName' => 'Foo',
                ],
                'TYPO3\\CMS\\Ext\\Controller\\FooController',
            ],
            'Vendor TYPO3\CMS, extension, subpackage, controller given' => [
                [
                    'vendorName' => 'TYPO3\\CMS',
                    'extensionName' => 'Fluid',
                    'subpackageKey' => 'ViewHelpers\\Widget',
                    'controllerName' => 'Paginate',
                ],
                PaginateController::class,
            ],
            'Vendor VENDOR, extension, controller given' => [
                [
                    'vendorName' => 'VENDOR',
                    'extensionName' => 'Ext',
                    'subpackageKey' => '',
                    'controllerName' => 'Foo',
                ],
                'VENDOR\\Ext\\Controller\\FooController',
            ],
            'Vendor VENDOR, extension subpackage, controller given' => [
                [
                    'vendorName' => 'VENDOR',
                    'extensionName' => 'Ext',
                    'subpackageKey' => 'ViewHelpers\\Widget',
                    'controllerName' => 'Foo',
                ],
                'VENDOR\\Ext\\ViewHelpers\\Widget\\Controller\\FooController',
            ],
        ];
    }

    /**
     * @dataProvider controllerArgumentsAndExpectedObjectName
     *
     * @param array $controllerArguments
     * @param string $controllerObjectName
     * @test
     */
    public function getControllerObjectNameResolvesControllerObjectNameCorrectly($controllerArguments, $controllerObjectName)
    {
        self::assertEquals(
            $controllerObjectName,
            ExtensionUtility::getControllerClassName(
                $controllerArguments['vendorName'],
                $controllerArguments['extensionName'],
                $controllerArguments['subpackageKey'],
                $controllerArguments['controllerName']
            )
        );
    }

    /**
     * @return array
     */
    public function checkResolveControllerAliasFromControllerClassNameDataProvider()
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

    /**
     * @dataProvider checkResolveControllerAliasFromControllerClassNameDataProvider
     *
     * @param string $expectedControllerAlias
     * @param string $controllerClassName
     * @test
     */
    public function checkResolveControllerAliasFromControllerClassName(string $expectedControllerAlias, string $controllerClassName)
    {
        self::assertEquals(
            $expectedControllerAlias,
            ExtensionUtility::resolveControllerAliasFromControllerClassName(
                $controllerClassName
            )
        );
    }

    /**
     * @return array
     */
    public function checkResolveVendorFromExtensionAndControllerClassNameDataProvider()
    {
        return [
            'Class in root namespace' => [
                '',
                'IndexedSearch',
                'Foo',
            ],
            'Namespaced class without extension name as namespace part' => [
                '',
                'IndexedSearch',
                'Foo\\Bar\\Baz\\Qux',
            ],
            'Namespaced class without vendor part before extension name part' => [
                '',
                'IndexedSearch',
                'IndexedSearch\\Controller\\SearchController',
            ],
            'Namespaced class with single vendor part' => [
                'Foo',
                'IndexedSearch',
                'Foo\\IndexedSearch\\Controller\\SearchController',
            ],
            'Namespaced class with multiple vendor parts' => [
                'TYPO\\CMS',
                'IndexedSearch',
                'TYPO\\CMS\\IndexedSearch\\Controller\\SearchController',
            ],
        ];
    }

    /**
     * @dataProvider checkResolveVendorFromExtensionAndControllerClassNameDataProvider
     *
     * @param string $expectedVendor
     * @param string $extensionName
     * @param string $controllerClassName
     * @test
     */
    public function checkResolveVendorFromExtensionAndControllerClassName(
        string $expectedVendor,
        string $extensionName,
        string $controllerClassName
    ) {
        self::assertEquals(
            $expectedVendor,
            ExtensionUtility::resolveVendorFromExtensionAndControllerClassName(
                $extensionName,
                $controllerClassName
            )
        );
    }
}
