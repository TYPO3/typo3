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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Repository\TtContentRepository;

/**
 * Reading translations, relations: the file references of a translated content element.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle (PageRepository and
 * RecordFactory, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair read the fixture
 * Fixtures/TranslatedRelationRead/Given.csv and compare the titles of the file references in the order of the field
 * `image`. An Extbase test that differs from core is skipped.
 *
 * The storage page 2 holds the content element 1 "Poster" with the file references "English one" and "English two" and
 * its German translation 2 "Plakat" with the file reference "German one" of its own. The content element 3 "Copied
 * poster" has the file reference "Copied English", its German translation 4 "Kopiertes Plakat" has the copy "Kopiert
 * deutsch", which has the default reference as `l10n_parent`, as DataHandler writes it when it localizes the content
 * element. The content element 5 "Poster with an image" has the file reference "English only", its German translation 6
 * "Plakat ohne eigenes Bild" has none.
 */
final class TranslatedRelationReadTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedRelationRead/Given.csv');
        $this->writeSiteConfiguration(
            'blog',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
            ]
        );
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['storagePid' => 2],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
    }

    /**
     * Content element 1 "Poster" has the two file references "English one" and "English two", its German translation
     * (2) "Plakat" has the file reference "German one" of its own. Read with German and overlays on, the core follows
     * the field `image` of the translation, so it returns "German one" only; the references of the default content
     * element are not part of the result.
     *
     * @see theFileReferencesOfATranslationWithOverlaysOnByExtbase()
     */
    #[Test]
    public function theFileReferencesOfATranslationWithOverlaysOnByCore(): void
    {
        // Given: content element 1 is read with German and overlays on
        $languageAspect = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON);
        $this->get(Context::class)->setAspect('language', $languageAspect);
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')
            ->select(['*'], 'tt_content', ['uid' => 1])->fetchAssociative();
        $row = $this->get(PageRepository::class)->getLanguageOverlay('tt_content', $row, $languageAspect);
        $record = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $row);

        // When: its images are followed
        $titles = array_map(
            static fn(CoreFileReference $reference): string => $reference->getTitle(),
            iterator_to_array($record->get('image'), false)
        );

        // Then: the file reference of the translation is returned
        self::assertSame(['German one'], $titles);
    }

    /**
     * Read with German and overlays on, Extbase returns the file reference of the translation as the core does: the
     * property `image` of content element 1 "Poster" holds "German one" only, not "English one" and "English two".
     *
     * @see theFileReferencesOfATranslationWithOverlaysOnByCore()
     */
    #[Test]
    public function theFileReferencesOfATranslationWithOverlaysOnByExtbase(): void
    {
        // Given: content element 1 is read with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $content = $this->get(TtContentRepository::class)->findByUid(1);

        // When: its images are followed
        $titles = array_map(
            static fn(FileReference $reference): string => $reference->getOriginalResource()->getTitle(),
            $content->getImage()->toArray()
        );

        // Then: the file reference of the translation is returned
        self::assertSame(['German one'], $titles);
    }

    /**
     * Content element 1 "Poster" has the two file references "English one" and "English two", its German translation
     * (2) "Plakat" has the file reference "German one" of its own. Read with German and overlays mixed, the core
     * returns "German one" only, as with overlays on: the references belong to the translation, whatever the overlay
     * type.
     *
     * @see theFileReferencesOfATranslationWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function theFileReferencesOfATranslationWithOverlaysMixedByCore(): void
    {
        // Given: content element 1 is read with German and overlays mixed
        $languageAspect = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED);
        $this->get(Context::class)->setAspect('language', $languageAspect);
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')
            ->select(['*'], 'tt_content', ['uid' => 1])->fetchAssociative();
        $row = $this->get(PageRepository::class)->getLanguageOverlay('tt_content', $row, $languageAspect);
        $record = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $row);

        // When: its images are followed
        $titles = array_map(
            static fn(CoreFileReference $reference): string => $reference->getTitle(),
            iterator_to_array($record->get('image'), false)
        );

        // Then: the file reference of the translation is returned
        self::assertSame(['German one'], $titles);
    }

    /**
     * Read with German and overlays mixed, Extbase returns "German one" only, like the core: the file references of
     * the translation replace those of content element 1 "Poster".
     *
     * @see theFileReferencesOfATranslationWithOverlaysMixedByCore()
     */
    #[Test]
    public function theFileReferencesOfATranslationWithOverlaysMixedByExtbase(): void
    {
        // Given: content element 1 is read with German and overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $content = $this->get(TtContentRepository::class)->findByUid(1);

        // When: its images are followed
        $titles = array_map(
            static fn(FileReference $reference): string => $reference->getOriginalResource()->getTitle(),
            $content->getImage()->toArray()
        );

        // Then: the file reference of the translation is returned
        self::assertSame(['German one'], $titles);
    }

    /**
     * Content element 3 "Copied poster" has the file reference "Copied English", its German translation (4)
     * "Kopiertes Plakat" has the copy "Kopiert deutsch" with the default reference as `l10n_parent`, as DataHandler
     * writes it when it localizes the content element. Read with German and overlays on, the core returns the copy
     * "Kopiert deutsch", the reference whose `uid_foreign` is the translation.
     *
     * @see theCopiedFileReferencesOfATranslationAreItsOwnByExtbase()
     */
    #[Test]
    public function theCopiedFileReferencesOfATranslationAreItsOwnByCore(): void
    {
        // Given: content element 3 is read with German and overlays on
        $languageAspect = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON);
        $this->get(Context::class)->setAspect('language', $languageAspect);
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')
            ->select(['*'], 'tt_content', ['uid' => 3])->fetchAssociative();
        $row = $this->get(PageRepository::class)->getLanguageOverlay('tt_content', $row, $languageAspect);
        $record = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $row);

        // When: its images are followed
        $titles = array_map(
            static fn(CoreFileReference $reference): string => $reference->getTitle(),
            iterator_to_array($record->get('image'), false)
        );

        // Then: the copy of the translation is returned
        self::assertSame(['Kopiert deutsch'], $titles);
    }

    /**
     * Read with German and overlays on, Extbase returns the copy "Kopiert deutsch" of the translation of content
     * element 3 "Copied poster" and not the reference "Copied English" of the default content element, like the core.
     *
     * @see theCopiedFileReferencesOfATranslationAreItsOwnByCore()
     */
    #[Test]
    public function theCopiedFileReferencesOfATranslationAreItsOwnByExtbase(): void
    {
        // Given: content element 3 is read with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $content = $this->get(TtContentRepository::class)->findByUid(3);

        // When: its images are followed
        $titles = array_map(
            static fn(FileReference $reference): string => $reference->getOriginalResource()->getTitle(),
            $content->getImage()->toArray()
        );

        // Then: the copy of the translation is returned
        self::assertSame(['Kopiert deutsch'], $titles);
    }

    /**
     * Content element 5 "Poster with an image" has the file reference "English only", its German translation (6)
     * "Plakat ohne eigenes Bild" has no file reference. Read with German and overlays mixed, the core returns no file
     * reference: the references of a translated record are the references of the translation, there is no fallback to
     * the default record.
     *
     * @see aTranslationWithoutFileReferencesDoesNotFallBackToTheDefaultRecordByExtbase()
     */
    #[Test]
    public function aTranslationWithoutFileReferencesDoesNotFallBackToTheDefaultRecordByCore(): void
    {
        // Given: content element 5 is read with German and overlays mixed
        $languageAspect = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED);
        $this->get(Context::class)->setAspect('language', $languageAspect);
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')
            ->select(['*'], 'tt_content', ['uid' => 5])->fetchAssociative();
        $row = $this->get(PageRepository::class)->getLanguageOverlay('tt_content', $row, $languageAspect);
        $record = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $row);

        // When: its images are followed
        $titles = array_map(
            static fn(CoreFileReference $reference): string => $reference->getTitle(),
            iterator_to_array($record->get('image'), false)
        );

        // Then: no file reference is returned
        self::assertSame([], $titles);
    }

    /**
     * Read with German and overlays mixed, Extbase returns no file reference for the translation (6) of content element
     * 5 "Poster with an image" and does not fall back to "English only" of the default content element, like the core.
     *
     * @see aTranslationWithoutFileReferencesDoesNotFallBackToTheDefaultRecordByCore()
     */
    #[Test]
    public function aTranslationWithoutFileReferencesDoesNotFallBackToTheDefaultRecordByExtbase(): void
    {
        // Given: content element 5 is read with German and overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $content = $this->get(TtContentRepository::class)->findByUid(5);

        // When: its images are followed
        $titles = array_map(
            static fn(FileReference $reference): string => $reference->getOriginalResource()->getTitle(),
            $content->getImage()->toArray()
        );

        // Then: no file reference is returned
        self::assertSame([], $titles);
    }
}
