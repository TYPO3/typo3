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
        $configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceMock->method('getConfiguration')->willReturn([]);
        $subject = new ExtensionService();
        $subject->injectConfigurationManager($configurationManagerInterfaceMock);
        self::assertSame('Blogs', $subject->getPluginNameByAction('BlogExample', 'Blog', 'testForm'));
    }

    #[Test]
    public function getTargetPidByPluginSignatureDeterminesTheTargetPidIfDefaultPidIsAuto(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Service/Fixtures/tt_content_with_single_plugin.csv');
        $configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceMock->method('getConfiguration')->willReturn(['view' => ['defaultPid' => 'auto']]);
        $subject = new ExtensionService();
        $subject->injectConfigurationManager($configurationManagerInterfaceMock);
        self::assertEquals(321, $subject->getTargetPidByPlugin('ExtensionName', 'SomePlugin'));
    }

    #[Test]
    public function getTargetPidByPluginSignatureReturnsNullIfTargetPidCouldNotBeDetermined(): void
    {
        $configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceMock->method('getConfiguration')->willReturn(['view' => ['defaultPid' => 'auto']]);
        $subject = new ExtensionService();
        $subject->injectConfigurationManager($configurationManagerInterfaceMock);
        self::assertNull($subject->getTargetPidByPlugin('ExtensionName', 'SomePlugin'));
    }

    #[Test]
    public function getTargetPidByPluginSignatureThrowsExceptionIfMoreThanOneTargetPidsWereFound(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Service/Fixtures/tt_content_with_two_plugins.csv');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1280773643);
        $configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceMock->method('getConfiguration')->willReturn(['view' => ['defaultPid' => 'auto']]);
        $subject = new ExtensionService();
        $subject->injectConfigurationManager($configurationManagerInterfaceMock);
        $subject->getTargetPidByPlugin('ExtensionName', 'SomePlugin');
    }
}
