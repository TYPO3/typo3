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

namespace TYPO3\CMS\Backend\Tests\Functional\Localization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Localization\Event\ModifyLocalizationHandlerIsAvailableEvent;
use TYPO3\CMS\Backend\Localization\LocalizationHandlerRegistry;
use TYPO3\CMS\Backend\Localization\LocalizationInstructions;
use TYPO3\CMS\Backend\Localization\LocalizationMode;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LocalizationHandlerRegistryTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
        'DE' => ['id' => 2, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF-8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Controller/Page/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Controller/Page/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Controller/Page/Fixtures/tt_content-default-language.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
                $this->buildLanguageConfiguration('DE', '/de'),
            ]
        );
    }

    public static function modifyLocalizationHandlerIsAvailableModifiesHardCodedAlwaysTrueOfManualHandlerDataSets(): \Generator
    {
        yield 'with LocalizationMode::COPY and uid 1 keeping manual handler' => [
            'mode' => LocalizationMode::COPY,
            'uid' => 1,
            'overrideHandlerIsAvailableFlag' => null,
            'expectedHandlerNames' => ['manual'],
        ];
        yield 'with LocalizationMode::COPY and uid 2 removing manual handler' => [
            'mode' => LocalizationMode::COPY,
            'uid' => 2,
            'overrideHandlerIsAvailableFlag' => false,
            'expectedHandlerNames' => [],
        ];
        yield 'with LocalizationMode::COPY and uid 3 keeping manual handler' => [
            'mode' => LocalizationMode::COPY,
            'uid' => 3,
            'overrideHandlerIsAvailableFlag' => null,
            'expectedHandlerNames' => ['manual'],
        ];
        yield 'with LocalizationMode::TRANSLATE and uid 1 keeping manual handler' => [
            'mode' => LocalizationMode::TRANSLATE,
            'uid' => 1,
            'overrideHandlerIsAvailableFlag' => null,
            'expectedHandlerNames' => ['manual'],
        ];
        yield 'with LocalizationMode::TRANSLATE and uid 2 removing manual handler' => [
            'mode' => LocalizationMode::TRANSLATE,
            'uid' => 2,
            'overrideHandlerIsAvailableFlag' => false,
            'expectedHandlerNames' => [],
        ];
        yield 'with LocalizationMode::TRANSLATE and uid 3 keeping manual handler' => [
            'mode' => LocalizationMode::TRANSLATE,
            'uid' => 3,
            'overrideHandlerIsAvailableFlag' => null,
            'expectedHandlerNames' => ['manual'],
        ];
    }

    /**
     * @param string[] $expectedHandlerNames
     */
    #[DataProvider('modifyLocalizationHandlerIsAvailableModifiesHardCodedAlwaysTrueOfManualHandlerDataSets')]
    #[Test]
    public function modifyLocalizationHandlerIsAvailableModifiesHardCodedAlwaysTrueOfManualHandler(
        LocalizationMode $mode,
        int $uid,
        ?bool $overrideHandlerIsAvailableFlag,
        array $expectedHandlerNames,
    ): void {
        $dispatchedEventsCount = 0;
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'custom-modify-localization-handler-is-availalbe-event-listener',
            static function (ModifyLocalizationHandlerIsAvailableEvent $event) use ($mode, &$dispatchedEventsCount, $overrideHandlerIsAvailableFlag) {
                $dispatchedEventsCount++;
                if ($mode === $event->instructions->mode
                    && $overrideHandlerIsAvailableFlag !== null
                ) {
                    $event->isAvailable = $overrideHandlerIsAvailableFlag;
                }
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(ModifyLocalizationHandlerIsAvailableEvent::class, 'custom-modify-localization-handler-is-availalbe-event-listener');
        $localizationHandlerRegistry = $this->get(LocalizationHandlerRegistry::class);
        $availableHandlers = $localizationHandlerRegistry->getAvailableHandlers(new LocalizationInstructions(
            mainRecordType: 'tt_content',
            recordUid: $uid,
            sourceLanguageId: self::LANGUAGE_PRESETS['EN']['id'],
            targetLanguageId: self::LANGUAGE_PRESETS['DA']['id'],
            mode: $mode,
            additionalData: []
        ));
        self::assertSame($expectedHandlerNames, array_keys($availableHandlers));
        self::assertSame(1, $dispatchedEventsCount);
    }
}
