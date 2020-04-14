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
     * @test
     */
    public function getPluginNameByActionDetectsPluginNameFromGlobalExtensionConfigurationArray()
    {
        $environmentService = $this->prophesize(EnvironmentService::class);
        $environmentService->isEnvironmentInFrontendMode()->willReturn(true);
        $environmentService->isEnvironmentInBackendMode()->willReturn(false);
        $environmentService = $environmentService->reveal();

        $frontendConfigurationManager = $this->prophesize(FrontendConfigurationManager::class);
        $frontendConfigurationManager->getConfiguration(Argument::cetera())->willReturn([]);

        $objectManager = $this->prophesize(ObjectManagerInterface::class);
        $objectManager->get(Argument::exact(FrontendConfigurationManager::class))->willReturn($frontendConfigurationManager->reveal());

        $configurationManager = new ConfigurationManager(
            $objectManager->reveal(),
            $environmentService
        );

        $extensionService = new ExtensionService();
        $extensionService->injectConfigurationManager($configurationManager);

        $pluginName = $extensionService->getPluginNameByAction('BlogExample', 'Blog', 'testForm');

        self::assertSame('Blogs', $pluginName);
    }
}
