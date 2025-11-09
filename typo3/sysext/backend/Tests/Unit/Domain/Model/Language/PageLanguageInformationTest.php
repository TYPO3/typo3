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

namespace TYPO3\CMS\Backend\Tests\Unit\Domain\Model\Language;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Domain\Model\Language\LanguageStatus;
use TYPO3\CMS\Backend\Domain\Model\Language\PageLanguageInformation;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PageLanguageInformationTest extends UnitTestCase
{
    private function createLanguageInfo(): PageLanguageInformation
    {
        $defaultLanguage = new SiteLanguage(0, 'en-US', new \TYPO3\CMS\Core\Http\Uri('/'), []);
        $germanLanguage = new SiteLanguage(1, 'de-DE', new \TYPO3\CMS\Core\Http\Uri('/de'), []);
        $frenchLanguage = new SiteLanguage(2, 'fr-FR', new \TYPO3\CMS\Core\Http\Uri('/fr'), []);

        return new PageLanguageInformation(
            pageId: 123,
            availableLanguages: [$defaultLanguage, $germanLanguage, $frenchLanguage],
            languageStatuses: [
                0 => LanguageStatus::Existing,
                1 => LanguageStatus::Existing,
                2 => LanguageStatus::Creatable,
            ],
            existingTranslations: [
                1 => ['uid' => 456, 'sys_language_uid' => 1, 'title' => 'Testseite'],
            ],
            creatableLanguageIds: [2],
            canUserCreateTranslations: true,
            languageItems: [],
        );
    }

    #[Test]
    public function hasTranslationReturnsTrueForExistingTranslation(): void
    {
        $languageInfo = $this->createLanguageInfo();

        self::assertTrue($languageInfo->hasTranslation(1));
    }

    #[Test]
    public function hasTranslationReturnsFalseForNonExistingTranslation(): void
    {
        $languageInfo = $this->createLanguageInfo();

        self::assertFalse($languageInfo->hasTranslation(2));
    }

    #[Test]
    public function canCreateTranslationReturnsTrueForCreatableLanguage(): void
    {
        $languageInfo = $this->createLanguageInfo();

        self::assertTrue($languageInfo->canCreateTranslation(2));
    }

    #[Test]
    public function canCreateTranslationReturnsFalseForExistingLanguage(): void
    {
        $languageInfo = $this->createLanguageInfo();

        self::assertFalse($languageInfo->canCreateTranslation(1));
    }

    #[Test]
    public function getTranslationRecordReturnsRecordForExistingTranslation(): void
    {
        $languageInfo = $this->createLanguageInfo();

        $record = $languageInfo->getTranslationRecord(1);

        self::assertIsArray($record);
        self::assertSame(456, $record['uid']);
        self::assertSame('Testseite', $record['title']);
    }

    #[Test]
    public function getTranslationRecordReturnsNullForNonExistingTranslation(): void
    {
        $languageInfo = $this->createLanguageInfo();

        $record = $languageInfo->getTranslationRecord(2);

        self::assertNull($record);
    }

    #[Test]
    public function getLanguageStatusReturnsCorrectStatus(): void
    {
        $languageInfo = $this->createLanguageInfo();

        self::assertSame(LanguageStatus::Existing, $languageInfo->getLanguageStatus(0));
        self::assertSame(LanguageStatus::Existing, $languageInfo->getLanguageStatus(1));
        self::assertSame(LanguageStatus::Creatable, $languageInfo->getLanguageStatus(2));
    }

    #[Test]
    public function getLanguageStatusReturnsUnavailableForUnknownLanguage(): void
    {
        $languageInfo = $this->createLanguageInfo();

        self::assertSame(LanguageStatus::Unavailable, $languageInfo->getLanguageStatus(999));
    }

    #[Test]
    public function getAllExistingLanguageIdsIncludesDefaultAndTranslations(): void
    {
        $languageInfo = $this->createLanguageInfo();

        $allIds = $languageInfo->getAllExistingLanguageIds();

        self::assertSame([0, 1], $allIds);
    }

    #[Test]
    public function getAllExistingLanguageIdsReturnsOnlyDefaultWhenNoTranslations(): void
    {
        $defaultLanguage = new SiteLanguage(0, 'en-US', new \TYPO3\CMS\Core\Http\Uri('/'), []);

        $languageInfo = new PageLanguageInformation(
            pageId: 123,
            availableLanguages: [$defaultLanguage],
            languageStatuses: [0 => LanguageStatus::Existing],
            existingTranslations: [],
            creatableLanguageIds: [],
            canUserCreateTranslations: false,
            languageItems: [],
        );

        $allIds = $languageInfo->getAllExistingLanguageIds();

        self::assertSame([0], $allIds);
    }
}
