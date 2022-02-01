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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ExtensionServiceTest extends FunctionalTestCase
{
    use ProphecyTrait;

    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected ExtensionService $extensionService;

    /** @var ObjectProphecy<FrontendConfigurationManager> */
    protected ObjectProphecy $frontendConfigurationManager;

    /** @var ObjectProphecy<ContainerInterface> */
    protected ObjectProphecy $containerProphecy;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $this->frontendConfigurationManager = $this->prophesize(FrontendConfigurationManager::class);
        $this->containerProphecy = $this->prophesize(ContainerInterface::class);
        $this->extensionService = new ExtensionService();
    }

    /**
     * @test
     */
    public function getPluginNameByActionDetectsPluginNameFromGlobalExtensionConfigurationArray(): void
    {
        $this->frontendConfigurationManager->getConfiguration(Argument::cetera())->willReturn([]);
        $this->containerProphecy->get(Argument::any())->willReturn($this->frontendConfigurationManager->reveal());
        $configurationManager = new ConfigurationManager($this->containerProphecy->reveal());
        $this->extensionService->injectConfigurationManager($configurationManager);

        $pluginName = $this->extensionService->getPluginNameByAction('BlogExample', 'Blog', 'testForm');

        self::assertSame('Blogs', $pluginName);
    }

    /**
     * @test
     */
    public function getTargetPidByPluginSignatureDeterminesTheTargetPidIfDefaultPidIsAuto(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Service/Fixtures/tt_content_with_single_plugin.xml');

        $this->frontendConfigurationManager->getConfiguration(Argument::cetera())->willReturn(['view' => ['defaultPid' => 'auto']]);
        $this->containerProphecy->get(Argument::any())->willReturn($this->frontendConfigurationManager->reveal());
        $configurationManager = new ConfigurationManager($this->containerProphecy->reveal());
        $this->extensionService->injectConfigurationManager($configurationManager);

        $expectedResult = 321;
        $result = $this->extensionService->getTargetPidByPlugin('ExtensionName', 'SomePlugin');
        self::assertEquals($expectedResult, $result);
    }

    /**
     * @test
     */
    public function getTargetPidByPluginSignatureReturnsNullIfTargetPidCouldNotBeDetermined(): void
    {
        $this->frontendConfigurationManager->getConfiguration(Argument::cetera())->willReturn(['view' => ['defaultPid' => 'auto']]);
        $this->containerProphecy->get(Argument::any())->willReturn($this->frontendConfigurationManager->reveal());
        $configurationManager = new ConfigurationManager($this->containerProphecy->reveal());
        $this->extensionService->injectConfigurationManager($configurationManager);

        $result = $this->extensionService->getTargetPidByPlugin('ExtensionName', 'SomePlugin');
        self::assertNull($result);
    }

    /**
     * @test
     */
    public function getTargetPidByPluginSignatureThrowsExceptionIfMoreThanOneTargetPidsWereFound(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Service/Fixtures/tt_content_with_two_plugins.xml');
        $this->frontendConfigurationManager->getConfiguration(Argument::cetera())->willReturn(['view' => ['defaultPid' => 'auto']]);
        $this->containerProphecy->get(Argument::any())->willReturn($this->frontendConfigurationManager->reveal());
        $configurationManager = new ConfigurationManager($this->containerProphecy->reveal());
        $this->extensionService->injectConfigurationManager($configurationManager);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1280773643);
        $this->extensionService->getTargetPidByPlugin('ExtensionName', 'SomePlugin');
    }
}
