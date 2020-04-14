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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Configuration;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Extbase\Configuration\Exception\ParseErrorException;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FrontendConfigurationManagerTest extends UnitTestCase
{
    /**
     * @var ContentObjectRenderer|MockObject
     */
    protected $mockContentObject;

    /**
     * @var FrontendConfigurationManager|MockObject|AccessibleObjectInterface
     */
    protected $frontendConfigurationManager;

    /**
     * @var TypoScriptService|MockObject|AccessibleObjectInterface
     */
    protected $mockTypoScriptService;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->tmpl = new \stdClass();
        $this->mockContentObject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['getTreeList'])
            ->getMock();
        $this->frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            ['dummy'],
            [],
            '',
            false
        );
        $this->frontendConfigurationManager->_set('contentObject', $this->mockContentObject);
        $this->mockTypoScriptService = $this->getAccessibleMock(TypoScriptService::class);
        $this->frontendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
    }

    /**
     * @test
     */
    public function overrideControllerConfigurationWithSwitchableControllerActionsFromFlexFormMergesNonCacheableActions(): void
    {
        $frameworkConfiguration = [
            'pluginName' => 'Pi1',
            'extensionName' => 'SomeExtension',
            'controllerConfiguration' => [
                'MyExtension\\Controller\\Controller1' => [
                    'alias' => 'Controller1',
                    'actions' => ['action1 , action2']
                ],
                'MyExtension\\Controller\\Controller2' => [
                    'alias' => 'Controller2',
                    'actions' => ['action2', 'action1', 'action3'],
                    'nonCacheableActions' => ['action2', 'action3']
                ]
            ]
        ];
        $flexFormConfiguration = [
            'switchableControllerActions' => 'Controller1  -> action2;\\MyExtension\\Controller\\Controller2->action3;  Controller2->action1'
        ];
        $expectedResult = [
            'pluginName' => 'Pi1',
            'extensionName' => 'SomeExtension',
            'controllerConfiguration' => [
                'MyExtension\\Controller\\Controller1' => [
                    'className' => 'MyExtension\\Controller\\Controller1',
                    'alias' => 'Controller1',
                    'actions' => ['action2']
                ],
                'MyExtension\\Controller\\Controller2' => [
                    'className' => 'MyExtension\\Controller\\Controller2',
                    'alias' => 'Controller2',
                    'actions' => ['action3', 'action1'],
                    'nonCacheableActions' => [1 => 'action3']
                ]
            ]
        ];
        $actualResult = $this->frontendConfigurationManager->_call(
            'overrideControllerConfigurationWithSwitchableControllerActionsFromFlexForm',
            $frameworkConfiguration,
            $flexFormConfiguration
        );
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function overrideControllerConfigurationWithSwitchableControllerActionsFromFlexFormReturnsUnchangedFrameworkConfigurationIfNoFlexFormConfigurationIsFound(
    ): void {
        $frameworkConfiguration = [
            'pluginName' => 'Pi1',
            'extensionName' => 'SomeExtension',
            'controllerConfiguration' => [
                'Controller1' => [
                    'controller' => 'Controller1',
                    'actions' => 'action1 , action2'
                ],
                'Controller2' => [
                    'controller' => 'Controller2',
                    'actions' => 'action2 , action1,action3',
                    'nonCacheableActions' => 'action2, action3'
                ]
            ]
        ];
        $flexFormConfiguration = [];
        $actualResult = $this->frontendConfigurationManager->_call(
            'overrideControllerConfigurationWithSwitchableControllerActionsFromFlexForm',
            $frameworkConfiguration,
            $flexFormConfiguration
        );
        self::assertSame($frameworkConfiguration, $actualResult);
    }

    /**
     * @test
     */
    public function overrideControllerConfigurationWithSwitchableControllerActionsThrowsExceptionIfFlexFormConfigurationIsInvalid(): void
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionCode(1257146403);
        $frameworkConfiguration = [
            'pluginName' => 'Pi1',
            'extensionName' => 'SomeExtension',
            'controllerConfiguration' => [
                'Controller1' => [
                    'actions' => ['action1 , action2']
                ],
                'Controller2' => [
                    'actions' => ['action2', 'action1', 'action3'],
                    'nonCacheableActions' => ['action2', 'action3']
                ]
            ]
        ];
        $flexFormConfiguration = [
            'switchableControllerActions' => 'Controller1->;Controller2->action3;Controller2->action1'
        ];
        $this->frontendConfigurationManager->_call(
            'overrideControllerConfigurationWithSwitchableControllerActionsFromFlexForm',
            $frameworkConfiguration,
            $flexFormConfiguration
        );
    }
}
