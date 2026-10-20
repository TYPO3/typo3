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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Tca\TcaPreparation;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Inline children (foreign_field) of an `l10n_mode = exclude` field, whose
 * table is configured with neither a languageField nor an origUid. Each
 * translation of the parent owns copies of the children, which are found
 * again through the origUid that TcaPreparation enforces for the child table.
 *
 * Reproduces https://forge.typo3.org/issues/88452.
 */
final class MonoglotInlineChildWithoutOriginTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
            ],
        );
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        unset(
            $GLOBALS['TCA']['tx_testirreforeignfield_offer']['ctrl']['languageField'],
            $GLOBALS['TCA']['tx_testirreforeignfield_offer']['ctrl']['transOrigPointerField'],
            $GLOBALS['TCA']['tx_testirreforeignfield_offer']['ctrl']['transOrigDiffSourceField'],
            $GLOBALS['TCA']['tx_testirreforeignfield_offer']['ctrl']['translationSource'],
            $GLOBALS['TCA']['tx_testirreforeignfield_offer']['ctrl']['origUid'],
        );
        $GLOBALS['TCA']['tx_testirreforeignfield_hotel']['columns']['offers']['l10n_mode'] = 'exclude';
        $GLOBALS['TCA'] = new TcaPreparation()->prepare($GLOBALS['TCA']);
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
    }

    #[Test]
    public function savingTranslatedParentKeepsCopiedChildren(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/MonoglotWithoutOrigin/HotelWithOffers.csv');
        $this->process([], ['tx_testirreforeignfield_hotel' => [1 => ['localize' => 1]]]);

        $this->process(['tx_testirreforeignfield_hotel' => [2 => ['title' => 'Großes Hotel']]]);
        $this->process(['tx_testirreforeignfield_hotel' => [2 => ['title' => 'Großes Hotel']]]);

        $this->assertCSVDataSet(__DIR__ . '/DataSet/MonoglotWithoutOrigin/LocalizedTranslationSavedResult.csv');
    }

    #[Test]
    public function savingDefaultParentKeepsCopiedChildrenOfTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/MonoglotWithoutOrigin/HotelWithOffers.csv');
        $this->process([], ['tx_testirreforeignfield_hotel' => [1 => ['localize' => 1]]]);

        $this->process(['tx_testirreforeignfield_hotel' => [1 => ['title' => 'Grand Hotel (renovated)', 'offers' => '1,2']]]);
        $this->process(['tx_testirreforeignfield_hotel' => [1 => ['title' => 'Grand Hotel (renovated)', 'offers' => '1,2']]]);

        $this->assertCSVDataSet(__DIR__ . '/DataSet/MonoglotWithoutOrigin/LocalizedDefaultSavedResult.csv');
    }

    #[Test]
    public function copiesWithoutOriginAreReplacedOnlyOnce(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/MonoglotWithoutOrigin/HotelWithGermanTranslation.csv');

        $this->process(['tx_testirreforeignfield_hotel' => [1 => ['title' => 'Grand Hotel (renovated)', 'offers' => '1,2']]]);
        $this->process(['tx_testirreforeignfield_hotel' => [1 => ['title' => 'Grand Hotel (renovated)', 'offers' => '1,2']]]);

        $this->assertCSVDataSet(__DIR__ . '/DataSet/MonoglotWithoutOrigin/CopiesWithoutOriginReplacedResult.csv');
    }

    private function process(array $dataMap, array $commandMap = []): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start($dataMap, $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();
    }
}
