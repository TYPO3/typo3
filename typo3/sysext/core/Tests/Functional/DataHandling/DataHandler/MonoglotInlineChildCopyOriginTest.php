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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Inline children (foreign_field) that are not language-aware (offers have no
 * languageField, only an origUid) are not translated by DataMapProcessor.
 * Instead, every translation of the parent receives its own copy of each child,
 * which is tracked through the child's origUid.
 *
 * The fixture extension's offer table is made non-language-aware in setUp().
 * The inline field `offers` of the hotel is set to `l10n_mode = exclude`, which is
 * what makes DataMapProcessor synchronize the field to the translations at all.
 * The copy behaviour itself is triggered by the non-translatable child table.
 *
 * Reproduces https://forge.typo3.org/issues/111070.
 */
final class MonoglotInlineChildCopyOriginTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8'],
        'FR' => ['id' => 2, 'title' => 'Français', 'locale' => 'fr_FR.UTF8'],
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
                $this->buildLanguageConfiguration('FR', '/fr/'),
            ],
        );
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Make the offer table not language-aware, it keeps its origUid ('t3_origuid')
        unset(
            $GLOBALS['TCA']['tx_testirreforeignfield_offer']['ctrl']['languageField'],
            $GLOBALS['TCA']['tx_testirreforeignfield_offer']['ctrl']['transOrigPointerField'],
            $GLOBALS['TCA']['tx_testirreforeignfield_offer']['ctrl']['transOrigDiffSourceField'],
            $GLOBALS['TCA']['tx_testirreforeignfield_offer']['ctrl']['translationSource'],
        );
        $GLOBALS['TCA']['tx_testirreforeignfield_hotel']['columns']['offers']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
    }

    /**     * Starting state: the default hotel has the offers "Breakfast included" and
     * "Late checkout", its German translation owns a copy of both offers.
     *
     * Action: the offer "Airport shuttle" is added to the default hotel.
     *
     * Expected: the German copy of "Airport shuttle" points to its origin like
     * the German copies of the other offers do (origUid), because the origUid
     * is the only link by which later synchronizations find the copy again.
     */
    #[Test]
    public function copyOfAddedChildKeepsReferenceToItsOrigin(): void
    {
        // Given: a default hotel with a German translation, which owns copies of both offers
        $this->importCSVDataSet(__DIR__ . '/DataSet/MonoglotOrigin/HotelWithGermanTranslation.csv');

        // When: the offer "Airport shuttle" is added to the default hotel
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(
            [
                'tx_testirreforeignfield_offer' => [
                    'NEW1' => ['pid' => 1, 'title' => 'Airport shuttle'],
                ],
                'tx_testirreforeignfield_hotel' => [
                    1 => ['offers' => '1,2,NEW1'],
                ],
            ],
            [],
        );
        $dataHandler->process_datamap();

        // Then: the German copy of "Airport shuttle" has the new default offer as origin
        $this->assertCSVDataSet(__DIR__ . '/DataSet/MonoglotOrigin/AddedChildCopyOriginResult.csv');
    }
}
