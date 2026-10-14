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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TranslationDiffSourceLanguageFieldsTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/TranslationDiffSource/Content.csv');
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
    }

    /**
     * Starting state: a tt_content record "English header" in the default language (uid 1).
     * Action: the record is localized to German (language 1) through DataHandler's localize command.
     * Expected: the diffsource JSON of the translation does not contain the translation source field
     * (l10n_source), because it is 0 in the default language record by definition.
     */
    #[Test]
    public function diffSourceOfTranslationDoesNotContainTranslationSource(): void
    {
        // Given: a default language record and the field names from TCA.
        $ctrl = $GLOBALS['TCA']['tt_content']['ctrl'];
        $translationSourceField = $ctrl['translationSource'];

        // When: the record is localized to German.
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['tt_content' => [1 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();

        // Then: the diffsource of the translation does not contain the translation source field.
        $translationUid = $dataHandler->copyMappingArray_merged['tt_content'][1];
        $translation = BackendUtility::getRecord('tt_content', $translationUid);
        $diffSource = json_decode($translation[$ctrl['transOrigDiffSourceField']], true);
        self::assertSame([], array_values(array_intersect(array_keys($diffSource), [$translationSourceField])));
    }

    /**
     * Starting state: a tt_content record "English header" in the default language (uid 1).
     * Action: the record is localized to German (language 1) through DataHandler's localize command.
     * Expected: the diffsource JSON of the translation contains neither the language field
     * (sys_language_uid) nor the origin pointer field (l18n_parent).
     */
    #[Test]
    public function diffSourceOfTranslationDoesNotContainLanguageFieldAndOriginPointer(): void
    {
        // Given: a default language record and the field names from TCA.
        $ctrl = $GLOBALS['TCA']['tt_content']['ctrl'];
        $languageFields = [$ctrl['languageField'], $ctrl['transOrigPointerField']];

        // When: the record is localized to German.
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['tt_content' => [1 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();

        // Then: the diffsource of the translation contains neither of these fields.
        $translationUid = $dataHandler->copyMappingArray_merged['tt_content'][1];
        $translation = BackendUtility::getRecord('tt_content', $translationUid);
        $diffSource = json_decode($translation[$ctrl['transOrigDiffSourceField']], true);
        self::assertSame([], array_values(array_intersect(array_keys($diffSource), $languageFields)));
    }
}
