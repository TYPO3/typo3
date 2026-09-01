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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ExtensionServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    #[Test]
    public function getPluginNameByActionDetectsPluginNameFromGlobalExtensionConfigurationArray(): void
    {
        $configurationManagerInterfaceStub = self::createStub(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceStub->method('getConfiguration')->willReturn([]);
        $subject = new ExtensionService($configurationManagerInterfaceStub, new NullFrontend('runtime'));
        self::assertSame('Blogs', $subject->getPluginNameByAction('BlogExample', 'Blog', 'testForm'));
    }

    #[Test]
    public function getTargetPidByPluginSignatureDeterminesTheTargetPidIfDefaultPidIsAuto(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Service/Fixtures/tt_content_with_single_plugin.csv');
        $configurationManagerInterfaceStub = self::createStub(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceStub->method('getConfiguration')->willReturn(['view' => ['defaultPid' => 'auto']]);
        $subject = new ExtensionService($configurationManagerInterfaceStub, new NullFrontend('runtime'));
        self::assertEquals(321, $subject->getTargetPidByPlugin('ExtensionName', 'SomePlugin'));
    }

    #[Test]
    public function getTargetPidByPluginSignatureReturnsNullIfTargetPidCouldNotBeDetermined(): void
    {
        $configurationManagerInterfaceStub = self::createStub(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceStub->method('getConfiguration')->willReturn(['view' => ['defaultPid' => 'auto']]);
        $subject = new ExtensionService($configurationManagerInterfaceStub, new NullFrontend('runtime'));
        self::assertNull($subject->getTargetPidByPlugin('ExtensionName', 'SomePlugin'));
    }

    #[Test]
    public function getTargetPidByPluginSignatureIsDeterminedPerLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Service/Fixtures/tt_content_with_plugin_in_two_languages.csv');
        $configurationManagerInterfaceStub = self::createStub(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceStub->method('getConfiguration')->willReturn(['view' => ['defaultPid' => 'auto']]);
        $context = GeneralUtility::makeInstance(Context::class);
        // A caching runtime cache, so a stale entry of the first language would be returned for the second one
        $subject = new ExtensionService(
            $configurationManagerInterfaceStub,
            new VariableFrontend('runtime', new TransientMemoryBackend())
        );

        $context->setAspect('language', new LanguageAspect(0));
        self::assertSame(321, $subject->getTargetPidByPlugin('ExtensionName', 'SomePlugin'));

        $context->setAspect('language', new LanguageAspect(1));
        self::assertSame(322, $subject->getTargetPidByPlugin('ExtensionName', 'SomePlugin'));
    }

    #[Test]
    public function getTargetPidByPluginSignatureThrowsExceptionIfMoreThanOneTargetPidsWereFound(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Service/Fixtures/tt_content_with_two_plugins.csv');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1280773643);
        $configurationManagerInterfaceStub = self::createStub(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceStub->method('getConfiguration')->willReturn(['view' => ['defaultPid' => 'auto']]);
        $subject = new ExtensionService($configurationManagerInterfaceStub, new NullFrontend('runtime'));
        $subject->getTargetPidByPlugin('ExtensionName', 'SomePlugin');
    }
}
