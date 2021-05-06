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

namespace TYPO3\CMS\Extbase\Tests\Functional\Service;

use Prophecy\Argument;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Service\EnvironmentService;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ExtensionServiceTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    /**
     * @var ExtensionService
     */
    protected $extensionService;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|FrontendConfigurationManager
     */
    protected $frontendConfigurationManager;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|EnvironmentService
     */
    protected $environmentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->environmentService = $this->prophesize(EnvironmentService::class);
        $this->environmentService->isEnvironmentInFrontendMode()->willReturn(true);
        $this->environmentService->isEnvironmentInBackendMode()->willReturn(false);

        $this->frontendConfigurationManager = $this->prophesize(FrontendConfigurationManager::class);

        $this->objectManager = $this->prophesize(ObjectManagerInterface::class);

        $this->extensionService = new ExtensionService();
    }

    /**
     * @test
     */
    public function getPluginNameByActionDetectsPluginNameFromGlobalExtensionConfigurationArray()
    {
        $this->frontendConfigurationManager->getConfiguration(Argument::cetera())->willReturn([]);
        $this->objectManager->get(Argument::any())->willReturn($this->frontendConfigurationManager->reveal());
        $configurationManager = new ConfigurationManager(
            $this->objectManager->reveal(),
            $this->environmentService->reveal()
        );
        $this->extensionService->injectConfigurationManager($configurationManager);

        $pluginName = $this->extensionService->getPluginNameByAction('BlogExample', 'Blog', 'testForm');

        self::assertSame('Blogs', $pluginName);
    }

    /**
     * @test
     */
    public function getTargetPidByPluginSignatureDeterminesTheTargetPidIfDefaultPidIsAuto()
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Service/Fixtures/tt_content_with_single_plugin.xml');

        $this->frontendConfigurationManager->getConfiguration(Argument::cetera())->willReturn(['view' => ['defaultPid' => 'auto']]);
        $this->objectManager->get(Argument::any())->willReturn($this->frontendConfigurationManager->reveal());
        $configurationManager = new ConfigurationManager(
            $this->objectManager->reveal(),
            $this->environmentService->reveal()
        );
        $this->extensionService->injectConfigurationManager($configurationManager);

        $expectedResult = 321;
        $result = $this->extensionService->getTargetPidByPlugin('ExtensionName', 'SomePlugin');
        self::assertEquals($expectedResult, $result);
    }

    /**
     * @test
     */
    public function getTargetPidByPluginSignatureReturnsNullIfTargetPidCouldNotBeDetermined()
    {
        $this->frontendConfigurationManager->getConfiguration(Argument::cetera())->willReturn(['view' => ['defaultPid' => 'auto']]);
        $this->objectManager->get(Argument::any())->willReturn($this->frontendConfigurationManager->reveal());
        $configurationManager = new ConfigurationManager(
            $this->objectManager->reveal(),
            $this->environmentService->reveal()
        );
        $this->extensionService->injectConfigurationManager($configurationManager);

        $result = $this->extensionService->getTargetPidByPlugin('ExtensionName', 'SomePlugin');
        self::assertNull($result);
    }

    /**
     * @test
     */
    public function getTargetPidByPluginSignatureThrowsExceptionIfMoreThanOneTargetPidsWereFound()
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Service/Fixtures/tt_content_with_two_plugins.xml');
        $this->frontendConfigurationManager->getConfiguration(Argument::cetera())->willReturn(['view' => ['defaultPid' => 'auto']]);
        $this->objectManager->get(Argument::any())->willReturn($this->frontendConfigurationManager->reveal());
        $configurationManager = new ConfigurationManager(
            $this->objectManager->reveal(),
            $this->environmentService->reveal()
        );
        $this->extensionService->injectConfigurationManager($configurationManager);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1280773643);
        $this->extensionService->getTargetPidByPlugin('ExtensionName', 'SomePlugin');
    }
}
