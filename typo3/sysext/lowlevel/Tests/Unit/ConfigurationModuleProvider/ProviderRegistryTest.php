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

namespace TYPO3\CMS\Lowlevel\Tests\Unit\ConfigurationModuleProvider;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderInterface;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProviderRegistryTest extends UnitTestCase
{
    #[Test]
    public function getGroupedProvidersOrdersGroupsByDefinitionAndKeepsRegistrationOrderInsideGroup(): void
    {
        $subject = new ProviderRegistry();
        $subject->registerProvider($this->createProvider(), ['identifier' => 'extension']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'form', 'group' => 'frameworks']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'events', 'group' => 'events']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'http', 'group' => 'requestHandling']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'sites', 'group' => 'sites']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'user', 'group' => 'backend']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'tca', 'group' => 'data']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'services', 'group' => 'core']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'confVars', 'group' => 'core']);

        $grouped = $subject->getGroupedProviders();

        self::assertSame(['core', 'data', 'backend', 'sites', 'requestHandling', 'events', 'frameworks', 'extensions'], array_keys($grouped));
        self::assertSame(['services', 'confVars'], array_keys($grouped['core']));
    }

    #[Test]
    public function getGroupedProvidersPutsCustomGroupsAfterKnownGroupsAndBeforeDefaultGroup(): void
    {
        $subject = new ProviderRegistry();
        $subject->registerProvider($this->createProvider(), ['identifier' => 'default']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'second', 'group' => 'My second group']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'sites', 'group' => 'sites']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'first', 'group' => 'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:group']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'secondAgain', 'group' => 'My second group']);

        $grouped = $subject->getGroupedProviders();

        self::assertSame(['sites', 'My second group', 'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:group', 'extensions'], array_keys($grouped));
        self::assertSame(['second', 'secondAgain'], array_keys($grouped['My second group']));
    }

    #[Test]
    public function getGroupedProvidersTreatsEmptyGroupAsDefaultGroup(): void
    {
        $subject = new ProviderRegistry();
        $subject->registerProvider($this->createProvider(), ['identifier' => 'foo', 'group' => '']);

        self::assertSame(['extensions'], array_keys($subject->getGroupedProviders()));
    }

    #[Test]
    public function getGroupedProvidersCombinesExplicitAndImplicitExtensionsGroup(): void
    {
        $subject = new ProviderRegistry();
        $subject->registerProvider($this->createProvider(), ['identifier' => 'explicit', 'group' => 'extensions']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'implicit']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'core', 'group' => 'core']);

        $grouped = $subject->getGroupedProviders();

        self::assertSame(['core', 'extensions'], array_keys($grouped));
        self::assertSame(['explicit', 'implicit'], array_keys($grouped['extensions']));
    }

    #[Test]
    public function getGroupedProvidersOmitsEmptyGroupsIncludingExtensions(): void
    {
        $subject = new ProviderRegistry();
        self::assertSame([], $subject->getGroupedProviders());

        $subject->registerProvider($this->createProvider(), ['identifier' => 'core', 'group' => 'core']);

        self::assertSame(['core'], array_keys($subject->getGroupedProviders()));
    }

    #[Test]
    public function getProvidersKeepsRegistrationOrderIndependentOfGroups(): void
    {
        $subject = new ProviderRegistry();
        $subject->registerProvider($this->createProvider(), ['identifier' => 'first']);
        $subject->registerProvider($this->createProvider(), ['identifier' => 'second', 'group' => 'core']);

        self::assertSame(['first', 'second'], array_keys($subject->getProviders()));
    }

    #[Test]
    public function getGroupLabelTranslatesKnownGroup(): void
    {
        $languageService = $this->createMock(LanguageService::class);
        $languageService->expects($this->once())->method('translate')->with('group.sites', 'lowlevel.messages')->willReturn('Sites');

        self::assertSame('Sites', new ProviderRegistry()->getGroupLabel('sites', $languageService));
    }

    #[Test]
    public function getGroupLabelFallsBackToGroupKeyIfKnownGroupLabelIsEmpty(): void
    {
        $languageService = self::createStub(LanguageService::class);
        $languageService->method('translate')->willReturn('');

        self::assertSame('sites', new ProviderRegistry()->getGroupLabel('sites', $languageService));
    }

    #[Test]
    public function getGroupLabelTranslatesLocalizationReference(): void
    {
        $group = 'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:group';
        $languageService = $this->createMock(LanguageService::class);
        $languageService->expects($this->once())->method('sL')->with($group)->willReturn('My group');

        self::assertSame('My group', new ProviderRegistry()->getGroupLabel($group, $languageService));
    }

    #[Test]
    public function getGroupLabelFallsBackToGroupIfLocalizationReferenceIsNotResolved(): void
    {
        $group = 'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:missing';
        $languageService = self::createStub(LanguageService::class);
        $languageService->method('sL')->willReturn('');

        self::assertSame($group, new ProviderRegistry()->getGroupLabel($group, $languageService));
    }

    #[Test]
    public function getGroupLabelUsesPlainTextWithColonAsIs(): void
    {
        $languageService = $this->createMock(LanguageService::class);
        $languageService->expects($this->never())->method('sL');
        $languageService->expects($this->never())->method('translate');

        self::assertSame('Shop: Settings', new ProviderRegistry()->getGroupLabel('Shop: Settings', $languageService));
    }

    private function createProvider(): ProviderInterface
    {
        return new class implements ProviderInterface {
            public function __invoke(array $attributes): self
            {
                return $this;
            }

            public function getIdentifier(): string
            {
                return '';
            }

            public function getLabel(): string
            {
                return '';
            }

            public function getConfiguration(): array
            {
                return [];
            }
        };
    }
}
