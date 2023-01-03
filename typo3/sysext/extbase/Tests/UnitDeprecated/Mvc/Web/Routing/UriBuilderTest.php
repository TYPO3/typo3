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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Mvc\Web\Routing;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UriBuilderTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private ExtensionService&MockObject $mockExtensionService;
    private UriBuilder&MockObject&AccessibleObjectInterface $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockExtensionService = $this->createMock(ExtensionService::class);
        $this->subject = $this->getAccessibleMock(UriBuilder::class, ['build']);
        $this->subject->setRequest($this->createMock(Request::class));
        $this->subject->injectConfigurationManager($this->createMock(ConfigurationManagerInterface::class));
        $this->subject->injectExtensionService($this->mockExtensionService);
        $this->subject->_set('contentObject', $this->createMock(ContentObjectRenderer::class));
    }

    /**
     * @test
     */
    public function removeDefaultControllerAndActionDoesNotModifyArgumentsIfSpecifiedControllerAndActionIsNotEqualToDefaults(): void
    {
        $this->mockExtensionService->expects(self::atLeastOnce())->method('getDefaultControllerNameByPlugin')->with('ExtensionName', 'PluginName')->willReturn('DefaultController');
        $this->mockExtensionService->expects(self::atLeastOnce())->method('getDefaultActionNameByPluginAndController')->with('ExtensionName', 'PluginName', 'SomeController')->willReturn('defaultAction');
        $arguments = ['controller' => 'SomeController', 'action' => 'someAction', 'foo' => 'bar'];
        $extensionName = 'ExtensionName';
        $pluginName = 'PluginName';
        $expectedResult = ['controller' => 'SomeController', 'action' => 'someAction', 'foo' => 'bar'];
        $actualResult = $this->subject->_call('removeDefaultControllerAndAction', $arguments, $extensionName, $pluginName);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function removeDefaultControllerAndActionRemovesControllerIfItIsEqualToTheDefault(): void
    {
        $this->mockExtensionService->expects(self::atLeastOnce())->method('getDefaultControllerNameByPlugin')->with('ExtensionName', 'PluginName')->willReturn('DefaultController');
        $this->mockExtensionService->expects(self::atLeastOnce())->method('getDefaultActionNameByPluginAndController')->with('ExtensionName', 'PluginName', 'DefaultController')->willReturn('defaultAction');
        $arguments = ['controller' => 'DefaultController', 'action' => 'someAction', 'foo' => 'bar'];
        $extensionName = 'ExtensionName';
        $pluginName = 'PluginName';
        $expectedResult = ['action' => 'someAction', 'foo' => 'bar'];
        $actualResult = $this->subject->_call('removeDefaultControllerAndAction', $arguments, $extensionName, $pluginName);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function removeDefaultControllerAndActionRemovesActionIfItIsEqualToTheDefault(): void
    {
        $this->mockExtensionService->expects(self::atLeastOnce())->method('getDefaultControllerNameByPlugin')->with('ExtensionName', 'PluginName')->willReturn('DefaultController');
        $this->mockExtensionService->expects(self::atLeastOnce())->method('getDefaultActionNameByPluginAndController')->with('ExtensionName', 'PluginName', 'SomeController')->willReturn('defaultAction');
        $arguments = ['controller' => 'SomeController', 'action' => 'defaultAction', 'foo' => 'bar'];
        $extensionName = 'ExtensionName';
        $pluginName = 'PluginName';
        $expectedResult = ['controller' => 'SomeController', 'foo' => 'bar'];
        $actualResult = $this->subject->_call('removeDefaultControllerAndAction', $arguments, $extensionName, $pluginName);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function removeDefaultControllerAndActionRemovesControllerAndActionIfBothAreEqualToTheDefault(): void
    {
        $this->mockExtensionService->expects(self::atLeastOnce())->method('getDefaultControllerNameByPlugin')->with('ExtensionName', 'PluginName')->willReturn('DefaultController');
        $this->mockExtensionService->expects(self::atLeastOnce())->method('getDefaultActionNameByPluginAndController')->with('ExtensionName', 'PluginName', 'DefaultController')->willReturn('defaultAction');
        $arguments = ['controller' => 'DefaultController', 'action' => 'defaultAction', 'foo' => 'bar'];
        $extensionName = 'ExtensionName';
        $pluginName = 'PluginName';
        $expectedResult = ['foo' => 'bar'];
        $actualResult = $this->subject->_call('removeDefaultControllerAndAction', $arguments, $extensionName, $pluginName);
        self::assertEquals($expectedResult, $actualResult);
    }
}
