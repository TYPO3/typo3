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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Utility;

use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
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
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
     */
    public function configurePluginWorksForMinimalisticSetup()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', ['Blog' => 'index']);
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
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
     */
    public function configurePluginCreatesCorrectDefaultTypoScriptSetup()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', ['Blog' => 'index']);
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
    }

    /**
     * @test
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
     */
    public function configurePluginWorksForASingleControllerAction()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', [
            'FirstController' => 'index'
        ]);
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
        self::assertStringContainsString('
	extensionName = MyExtension
	pluginName = Pi1', $staticTypoScript);
        $expectedResult = [
            'controllers' => [
                'MyExtension\Controller\FirstControllerController' => [
                    'className' => 'MyExtension\Controller\FirstControllerController',
                    'alias' => 'FirstController',
                    'actions' => ['index']
                ]
            ],
            'pluginType' => 'list_type'
        ];
        self::assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
    }

    /**
     * @test
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
     */
    public function configurePluginRespectsDefaultActionAsANonCacheableAction()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', [
            'First' => 'index,show,new, create,delete,edit,update'
        ], [
            'First' => 'index,show'
        ]);
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
        self::assertStringContainsString('
	extensionName = MyExtension
	pluginName = Pi1', $staticTypoScript);
        $expectedResult = [
            'controllers' => [
                'MyExtension\Controller\FirstController' => [
                    'className' => 'MyExtension\Controller\FirstController',
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
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
     */
    public function configurePluginRespectsNonDefaultActionAsANonCacheableAction()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', [
            'First' => 'index,show,new, create,delete,edit,update'
        ], [
            'First' => 'new,show'
        ]);
        $staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
        self::assertStringContainsString('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
        self::assertStringContainsString('
	extensionName = MyExtension
	pluginName = Pi1', $staticTypoScript);
        $expectedResult = [
            'controllers' => [
                'MyExtension\Controller\FirstController' => [
                    'className' => 'MyExtension\Controller\FirstController',
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
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
     */
    public function configurePluginWorksForMultipleControllerActionsWithCacheableActionAsDefault()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', [
            'First' => 'index,show,new,create,delete,edit,update',
            'Second' => 'index,show,delete',
            'Third' => 'create'
        ], [
            'First' => 'new,create,edit,update',
            'Third' => 'create'
        ]);
        $expectedResult = [
            'controllers' => [
                'MyExtension\Controller\FirstController' => [
                    'className' => 'MyExtension\Controller\FirstController',
                    'alias' => 'First',
                    'actions' => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                    'nonCacheableActions' => ['new', 'create', 'edit', 'update']
                ],
                'MyExtension\Controller\SecondController' => [
                    'className' => 'MyExtension\Controller\SecondController',
                    'alias' => 'Second',
                    'actions' => ['index', 'show', 'delete']
                ],
                'MyExtension\Controller\ThirdController' => [
                    'className' => 'MyExtension\Controller\ThirdController',
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
     * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
     */
    public function configurePluginWorksForMultipleControllerActionsWithNonCacheableActionAsDefault()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = [];
        ExtensionUtility::configurePlugin('MyExtension', 'Pi1', [
            'First' => 'index,show,new,create,delete,edit,update',
            'Second' => 'index,show,delete',
            'Third' => 'create'
        ], [
            'First' => 'index,new,create,edit,update',
            'Second' => 'delete',
            'Third' => 'create'
        ]);
        $expectedResult = [
            'controllers' => [
                'MyExtension\Controller\FirstController' => [
                    'className' => 'MyExtension\Controller\FirstController',
                    'alias' => 'First',
                    'actions' => ['index', 'show', 'new', 'create', 'delete', 'edit', 'update'],
                    'nonCacheableActions' => ['index', 'new', 'create', 'edit', 'update']
                ],
                'MyExtension\Controller\SecondController' => [
                    'className' => 'MyExtension\Controller\SecondController',
                    'alias' => 'Second',
                    'actions' => ['index', 'show', 'delete'],
                    'nonCacheableActions' => ['delete']
                ],
                'MyExtension\Controller\ThirdController' => [
                    'className' => 'MyExtension\Controller\ThirdController',
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
     */
    public function registerPluginRegistersPluginWithDeprecatedVendorInExtensionName()
    {
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [];
        ExtensionUtility::registerPlugin(
            'TYPO3.CMS.IndexedSearch',
            'Pi2',
            'Testing'
        );
        self::assertSame(
            'indexedsearch_pi2',
            $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0][1]
        );
    }
}
