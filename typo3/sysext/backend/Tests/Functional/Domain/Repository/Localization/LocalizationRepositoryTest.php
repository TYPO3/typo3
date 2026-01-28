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

namespace TYPO3\CMS\Backend\Tests\Functional\Domain\Repository\Localization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LocalizationRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    private LocalizationRepository $subject;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->importCSVDataSet(__DIR__ . '/Fixtures/DefaultPagesAndContent.csv');

        $this->subject = $this->get(LocalizationRepository::class);
    }

    public static function getRecordsToCopyDatabaseResultDataProvider(): array
    {
        return [
            'from language 0 to 1 connected mode' => [
                1,
                1,
                0,
                [
                    ['uid' => 298],
                ],
            ],
            'from language 1 to 2 connected mode' => [
                1,
                2,
                1,
                [
                    ['uid' => 300],
                ],
            ],
            'from language 0 to 1 free mode' => [
                2,
                1,
                0,
                [],
            ],
            'from language 1 to 2 free mode' => [
                2,
                2,
                1,
                [],
            ],
            'from language 0 to 1 free mode copied' => [
                3,
                1,
                0,
                [],
            ],
            'from language 1 to 2 free mode  mode copied' => [
                3,
                2,
                1,
                [],
            ],
        ];
    }

    #[DataProvider('getRecordsToCopyDatabaseResultDataProvider')]
    #[Test]
    public function getRecordsToCopyDatabaseResult(int $pageId, int $destLanguageId, int $languageId, array $expectedResult): void
    {
        $result = $this->subject->getRecordsToCopyDatabaseResult($pageId, $destLanguageId, $languageId);
        $rows = $result->fetchAllAssociative();
        // Extract just uids for comparison since the method now returns full records
        $actualUids = array_map(static fn(array $row) => ['uid' => $row['uid']], $rows);
        self::assertEquals($expectedResult, $actualUids);
    }

    #[Test]
    public function getRecordTranslationReturnsTranslationForContent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        $result = $this->subject->getRecordTranslation('tt_content', 1100, 1);
        self::assertNotNull($result);
        self::assertSame(1101, $result->getUid());
        self::assertSame('[DE] Content Default', $result->toArray()['header']);
    }

    #[Test]
    public function getRecordTranslationReturnsNullForNonExistentTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        $result = $this->subject->getRecordTranslation('tt_content', 1100, 3);
        self::assertNull($result);
    }

    #[Test]
    public function getRecordTranslationExcludesDeletedByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        $result = $this->subject->getRecordTranslation('tt_content', 1200, 1);
        self::assertNull($result);
    }

    #[Test]
    public function getRecordTranslationIncludesDeletedWhenFlagSet(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        $result = $this->subject->getRecordTranslation('tt_content', 1200, 1, 0, true);
        self::assertNotNull($result);
        self::assertSame(1201, $result->getUid());
    }

    #[Test]
    public function getRecordTranslationReturnsWorkspaceVersionInWorkspace(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        // In live workspace (0), should return live version
        $liveResult = $this->subject->getRecordTranslation('tt_content', 1300, 1, 0);
        self::assertNotNull($liveResult);
        self::assertSame(1301, $liveResult->getUid());
        self::assertSame('[DE] Live content translation', $liveResult->toArray()['header']);

        // In workspace 1, should return workspace version with overlaid data
        $wsResult = $this->subject->getRecordTranslation('tt_content', 1300, 1, 1);
        self::assertNotNull($wsResult);
        // The UID remains the live UID after overlay, but data comes from workspace
        self::assertSame('[DE] Workspace content translation', $wsResult->toArray()['header']);
    }

    #[Test]
    public function getRecordTranslationReturnsNullForWorkspaceDeletedRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        // In live workspace, should return the live translation
        $liveResult = $this->subject->getRecordTranslation('tt_content', 1400, 1, 0);
        self::assertNotNull($liveResult);
        self::assertSame(1401, $liveResult->getUid());

        // In workspace 1, the translation is marked as deleted (DELETE_PLACEHOLDER), should return null
        $wsResult = $this->subject->getRecordTranslation('tt_content', 1400, 1, 1);
        self::assertNull($wsResult);
    }

    #[Test]
    public function getPageTranslationsReturnsAllTranslations(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        $result = $this->subject->getPageTranslations(1001);
        self::assertCount(2, $result);
        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
        self::assertSame('[DE] Default Page', $result[1]->toArray()['title']);
        self::assertSame('[FR] Default Page', $result[2]->toArray()['title']);
    }

    #[Test]
    public function getPageTranslationsFiltersByLanguageIds(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        $result = $this->subject->getPageTranslations(1001, [1]);
        self::assertCount(1, $result);
        self::assertArrayHasKey(1, $result);
        self::assertSame('[DE] Default Page', $result[1]->toArray()['title']);
    }

    #[Test]
    public function getPageTranslationsExcludesDeletedByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        $result = $this->subject->getPageTranslations(1004);
        self::assertCount(0, $result);
    }

    #[Test]
    public function getPageTranslationsIncludesDeletedWhenFlagSet(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        $result = $this->subject->getPageTranslations(1004, [], 0, true);
        self::assertCount(1, $result);
        self::assertArrayHasKey(1, $result);
    }

    #[Test]
    public function getPageTranslationsReturnsWorkspaceVersionInWorkspace(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        // In live workspace
        $liveResult = $this->subject->getPageTranslations(1006, [1], 0);
        self::assertCount(1, $liveResult);
        self::assertSame('[DE] Live translation', $liveResult[1]->toArray()['title']);

        // In workspace 1
        $wsResult = $this->subject->getPageTranslations(1006, [1], 1);
        self::assertCount(1, $wsResult);
        self::assertSame('[DE] Workspace translation (modified)', $wsResult[1]->toArray()['title']);
    }

    #[Test]
    public function getPageTranslationsExcludesWorkspaceDeletedRecords(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        // In live workspace, translation exists
        $liveResult = $this->subject->getPageTranslations(1009, [1], 0);
        self::assertCount(1, $liveResult);

        // In workspace 1, translation is marked as deleted
        $wsResult = $this->subject->getPageTranslations(1009, [1], 1);
        self::assertCount(0, $wsResult);
    }

    #[Test]
    public function getRecordTranslationsReturnsAllTranslations(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        $result = $this->subject->getRecordTranslations('tt_content', 1100);
        self::assertCount(2, $result);
        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
        self::assertSame('[DE] Content Default', $result[1]->toArray()['header']);
        self::assertSame('[FR] Content Default', $result[2]->toArray()['header']);
    }

    #[Test]
    public function getRecordTranslationsFiltersByLanguageIds(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        $result = $this->subject->getRecordTranslations('tt_content', 1100, [1]);
        self::assertCount(1, $result);
        self::assertArrayHasKey(1, $result);
        self::assertSame('[DE] Content Default', $result[1]->toArray()['header']);
    }

    #[Test]
    public function getRecordTranslationsExcludesDeletedByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        $result = $this->subject->getRecordTranslations('tt_content', 1200);
        self::assertCount(0, $result);
    }

    #[Test]
    public function getRecordTranslationsIncludesDeletedWhenFlagSet(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        $result = $this->subject->getRecordTranslations('tt_content', 1200, [], 0, true);
        self::assertCount(1, $result);
        self::assertArrayHasKey(1, $result);
    }

    #[Test]
    public function getRecordTranslationsReturnsWorkspaceVersionInWorkspace(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        // In live workspace
        $liveResult = $this->subject->getRecordTranslations('tt_content', 1300, [1], 0);
        self::assertCount(1, $liveResult);
        self::assertSame('[DE] Live content translation', $liveResult[1]->toArray()['header']);

        // In workspace 1
        $wsResult = $this->subject->getRecordTranslations('tt_content', 1300, [1], 1);
        self::assertCount(1, $wsResult);
        self::assertSame('[DE] Workspace content translation', $wsResult[1]->toArray()['header']);
    }

    #[Test]
    public function getRecordTranslationsExcludesWorkspaceDeletedRecords(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationTestData.csv');
        // In live workspace, translation exists
        $liveResult = $this->subject->getRecordTranslations('tt_content', 1400, [1], 0);
        self::assertCount(1, $liveResult);

        // In workspace 1, translation is marked as deleted
        $wsResult = $this->subject->getRecordTranslations('tt_content', 1400, [1], 1);
        self::assertCount(0, $wsResult);
    }
}
