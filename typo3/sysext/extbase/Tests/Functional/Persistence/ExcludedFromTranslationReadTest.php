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
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Comment;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\PersonRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Reads of fields with 'l10n_mode' => 'exclude' on translations: author, reviewer, date, tags,
 * comments and info of a translated post, and queries for posts by the excluded author.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle
 * (PageRepository::getLanguageOverlay() plus RecordFactory, suffix "ByCore"), once with Extbase
 * (suffix "ByExtbase"). The oracle tests describe correct behaviour and must be green; an Extbase
 * test that differs from core is skipped with a reference to the issue.
 *
 * Fixture Read.csv: post 1 [en] with 2 [de] and 3 [fr] (tags and info, each translation has its
 * own copies), post 7 [en] with 8 [de] (comments, one translation only).
 */
final class ExcludedFromTranslationReadTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en-US'],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de-DE'],
        'FR' => ['id' => 2, 'title' => 'French', 'locale' => 'fr-FR'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/Read.csv');
        $this->writeSiteConfiguration(
            'blog',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
                $this->buildLanguageConfiguration('FR', '/fr/'),
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
     * The German and the French translation of a post store their own copy of author, reviewer and
     * date. Read with the overlay of either language and overlays on, the post returns Anna as author,
     * Ben as reviewer and 2026-01-15 as date.
     *
     * @see excludedValuesOfATranslationAreReadFromItsOwnRowWithOverlaysOnByExtbase()
     */
    #[Test]
    public function excludedValuesOfATranslationAreReadFromItsOwnRowWithOverlaysOnByCore(): void
    {
        // When: the post is read with the overlay of the German and of the French translation, overlays on
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $germanRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON)
        );
        $german = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $germanRow);
        $frenchRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON)
        );
        $french = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $frenchRow);

        // Then: author, reviewer and date are those stored in the translation
        self::assertSame('Anna', $german->get('author')?->get('firstname'));
        self::assertSame('Ben', $german->get('reviewer')?->get('firstname'));
        self::assertSame('2026-01-15', $german->get('date')->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d'));

        self::assertSame('Anna', $french->get('author')?->get('firstname'));
        self::assertSame('Ben', $french->get('reviewer')?->get('firstname'));
        self::assertSame('2026-01-15', $french->get('date')->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d'));
    }

    /**
     * With overlays "on" Extbase drops related persons that have no translation, while core
     * resolves them.
     *
     * @see excludedValuesOfATranslationAreReadFromItsOwnRowWithOverlaysOnByCore()
     */
    #[Test]
    public function excludedValuesOfATranslationAreReadFromItsOwnRowWithOverlaysOnByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // When: the post is read with the overlay of the German and of the French translation, overlays on
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $german = $this->get(PostRepository::class)->findByUid(1);
        $this->get(PersistenceManager::class)->clearState();
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON));
        $french = $this->get(PostRepository::class)->findByUid(1);

        // Then: author, reviewer and date are those stored in the translation
        self::assertSame('Anna', $german->getAuthor()?->getFirstname());
        self::assertSame('Ben', $german->getReviewer()?->getFirstname());
        self::assertSame('2026-01-15', \DateTimeImmutable::createFromInterface($german->getDate())->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d'));

        self::assertSame('Anna', $french->getAuthor()?->getFirstname());
        self::assertSame('Ben', $french->getReviewer()?->getFirstname());
        self::assertSame('2026-01-15', \DateTimeImmutable::createFromInterface($french->getDate())->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d'));
    }

    /**
     * The German and the French translation of a post store their own copy of author, reviewer and
     * date. Read with the overlay of either language and overlays mixed, the post returns Anna as author,
     * Ben as reviewer and 2026-01-15 as date.
     *
     * @see excludedValuesOfATranslationAreReadFromItsOwnRowWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function excludedValuesOfATranslationAreReadFromItsOwnRowWithOverlaysMixedByCore(): void
    {
        // When: the post is read with the overlay of the German and of the French translation, overlays mixed
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $germanRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED)
        );
        $german = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $germanRow);
        $frenchRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED)
        );
        $french = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $frenchRow);

        // Then: author, reviewer and date are those stored in the translation
        self::assertSame('Anna', $german->get('author')?->get('firstname'));
        self::assertSame('Ben', $german->get('reviewer')?->get('firstname'));
        self::assertSame('2026-01-15', $german->get('date')->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d'));

        self::assertSame('Anna', $french->get('author')?->get('firstname'));
        self::assertSame('Ben', $french->get('reviewer')?->get('firstname'));
        self::assertSame('2026-01-15', $french->get('date')->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d'));
    }

    /**
     * @see excludedValuesOfATranslationAreReadFromItsOwnRowWithOverlaysMixedByCore()
     */
    #[Test]
    public function excludedValuesOfATranslationAreReadFromItsOwnRowWithOverlaysMixedByExtbase(): void
    {
        // When: the post is read with the overlay of the German and of the French translation, overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $german = $this->get(PostRepository::class)->findByUid(1);
        $this->get(PersistenceManager::class)->clearState();
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED));
        $french = $this->get(PostRepository::class)->findByUid(1);

        // Then: author, reviewer and date are those stored in the translation
        self::assertSame('Anna', $german->getAuthor()?->getFirstname());
        self::assertSame('Ben', $german->getReviewer()?->getFirstname());
        self::assertSame('2026-01-15', \DateTimeImmutable::createFromInterface($german->getDate())->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d'));

        self::assertSame('Anna', $french->getAuthor()?->getFirstname());
        self::assertSame('Ben', $french->getReviewer()?->getFirstname());
        self::assertSame('2026-01-15', \DateTimeImmutable::createFromInterface($french->getDate())->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d'));
    }

    /**
     * Every translation owns a copy of the info of the default post. Read with the overlay of the
     * German or the French translation and overlays on, the post returns the info of that translation,
     * not the one of the English post.
     *
     * @see infoOfExcludedInlineRelationIsTheCopyOfTheTranslationWithOverlaysOnByExtbase()
     */
    #[Test]
    public function infoOfExcludedInlineRelationIsTheCopyOfTheTranslationWithOverlaysOnByCore(): void
    {
        // When: the post is read with the overlay of the German and of the French translation, overlays on
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $germanRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON)
        );
        $german = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $germanRow);
        $frenchRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON)
        );
        $french = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $frenchRow);

        // Then: the info is the copy owned by that translation
        self::assertSame('Zusätzliche Details', $german->get('additional_info')?->get('name'));
        self::assertSame('Détails supplémentaires', $french->get('additional_info')?->get('name'));
    }

    /**
     * @see infoOfExcludedInlineRelationIsTheCopyOfTheTranslationWithOverlaysOnByCore()
     */
    #[Test]
    public function infoOfExcludedInlineRelationIsTheCopyOfTheTranslationWithOverlaysOnByExtbase(): void
    {
        // When: the post is read with the overlay of the German and of the French translation, overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $german = $this->get(PostRepository::class)->findByUid(1);
        $this->get(PersistenceManager::class)->clearState();
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON));
        $french = $this->get(PostRepository::class)->findByUid(1);

        // Then: the info is the copy owned by that translation
        self::assertSame('Zusätzliche Details', $german->getAdditionalInfo()?->getName());
        self::assertSame('Détails supplémentaires', $french->getAdditionalInfo()?->getName());
    }

    /**
     * Every translation owns a copy of the info of the default post. Read with the overlay of the
     * German or the French translation and overlays mixed, the post returns the info of that translation,
     * not the one of the English post.
     *
     * @see infoOfExcludedInlineRelationIsTheCopyOfTheTranslationWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function infoOfExcludedInlineRelationIsTheCopyOfTheTranslationWithOverlaysMixedByCore(): void
    {
        // When: the post is read with the overlay of the German and of the French translation, overlays mixed
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $germanRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED)
        );
        $german = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $germanRow);
        $frenchRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED)
        );
        $french = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $frenchRow);

        // Then: the info is the copy owned by that translation
        self::assertSame('Zusätzliche Details', $german->get('additional_info')?->get('name'));
        self::assertSame('Détails supplémentaires', $french->get('additional_info')?->get('name'));
    }

    /**
     * @see infoOfExcludedInlineRelationIsTheCopyOfTheTranslationWithOverlaysMixedByCore()
     */
    #[Test]
    public function infoOfExcludedInlineRelationIsTheCopyOfTheTranslationWithOverlaysMixedByExtbase(): void
    {
        // When: the post is read with the overlay of the German and of the French translation, overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $german = $this->get(PostRepository::class)->findByUid(1);
        $this->get(PersistenceManager::class)->clearState();
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED));
        $french = $this->get(PostRepository::class)->findByUid(1);

        // Then: the info is the copy owned by that translation
        self::assertSame('Zusätzliche Details', $german->getAdditionalInfo()?->getName());
        self::assertSame('Détails supplémentaires', $french->getAdditionalInfo()?->getName());
    }

    /**
     * The German translation of the third post owns copies of both comments of the English post.
     * Read with the overlay of the German translation and overlays on, the post returns these two
     * comments.
     *
     * @see commentsOfExcludedInlineRelationAreTheCopiesOfTheTranslationWithOverlaysOnByExtbase()
     */
    #[Test]
    public function commentsOfExcludedInlineRelationAreTheCopiesOfTheTranslationWithOverlaysOnByCore(): void
    {
        // When: the third post is read with the overlay of the German translation, overlays on
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 7])->fetchAssociative();
        $germanRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON)
        );
        $german = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $germanRow);

        // Then: the post returns the two comments of the translation
        $contents = array_map(
            static fn(Record $comment): string => $comment->get('content'),
            iterator_to_array($german->get('comments'), false)
        );
        sort($contents);
        self::assertSame(['Nice read', 'Well written'], $contents);
    }

    /**
     * @see commentsOfExcludedInlineRelationAreTheCopiesOfTheTranslationWithOverlaysOnByCore()
     */
    #[Test]
    public function commentsOfExcludedInlineRelationAreTheCopiesOfTheTranslationWithOverlaysOnByExtbase(): void
    {
        // When: the third post is read with the overlay of the German translation, overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $german = $this->get(PostRepository::class)->findByUid(7);

        // Then: the post returns the two comments of the translation
        $contents = array_map(static fn(Comment $comment): string => $comment->getContent(), $german->getComments()->toArray());
        sort($contents);
        self::assertSame(['Nice read', 'Well written'], $contents);
    }

    /**
     * The German translation of the third post owns copies of both comments of the English post.
     * Read with the overlay of the German translation and overlays mixed, the post returns these two
     * comments.
     *
     * @see commentsOfExcludedInlineRelationAreTheCopiesOfTheTranslationWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function commentsOfExcludedInlineRelationAreTheCopiesOfTheTranslationWithOverlaysMixedByCore(): void
    {
        // When: the third post is read with the overlay of the German translation, overlays mixed
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 7])->fetchAssociative();
        $germanRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED)
        );
        $german = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $germanRow);

        // Then: the post returns the two comments of the translation
        $contents = array_map(
            static fn(Record $comment): string => $comment->get('content'),
            iterator_to_array($german->get('comments'), false)
        );
        sort($contents);
        self::assertSame(['Nice read', 'Well written'], $contents);
    }

    /**
     * @see commentsOfExcludedInlineRelationAreTheCopiesOfTheTranslationWithOverlaysMixedByCore()
     */
    #[Test]
    public function commentsOfExcludedInlineRelationAreTheCopiesOfTheTranslationWithOverlaysMixedByExtbase(): void
    {
        // When: the third post is read with the overlay of the German translation, overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $german = $this->get(PostRepository::class)->findByUid(7);

        // Then: the post returns the two comments of the translation
        $contents = array_map(static fn(Comment $comment): string => $comment->getContent(), $german->getComments()->toArray());
        sort($contents);
        self::assertSame(['Nice read', 'Well written'], $contents);
    }

    /**
     * Every translation owns its relations to the tags PHP and TYPO3. Read with the overlay of the
     * German or the French translation and overlays on, the post returns both tags.
     *
     * @see excludedManyToManyRelationOfATranslationIsReadFromItsOwnRowsWithOverlaysOnByExtbase()
     */
    #[Test]
    public function excludedManyToManyRelationOfATranslationIsReadFromItsOwnRowsWithOverlaysOnByCore(): void
    {
        // When: the post is read with the overlay of the German and of the French translation, overlays on
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $germanRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON)
        );
        $german = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $germanRow);
        $frenchRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON)
        );
        $french = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $frenchRow);

        // Then: the post returns both tags
        $tagsOfGerman = array_map(
            static fn(Record $tag): string => $tag->get('name'),
            iterator_to_array($german->get('tags'), false)
        );
        sort($tagsOfGerman);
        self::assertSame(['PHP', 'TYPO3'], $tagsOfGerman);

        $tagsOfFrench = array_map(
            static fn(Record $tag): string => $tag->get('name'),
            iterator_to_array($french->get('tags'), false)
        );
        sort($tagsOfFrench);
        self::assertSame(['PHP', 'TYPO3'], $tagsOfFrench);
    }

    /**
     * With overlays "on" Extbase drops related tags that have no translation, while core
     * resolves them.
     *
     * @see excludedManyToManyRelationOfATranslationIsReadFromItsOwnRowsWithOverlaysOnByCore()
     */
    #[Test]
    public function excludedManyToManyRelationOfATranslationIsReadFromItsOwnRowsWithOverlaysOnByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // When: the post is read with the overlay of the German and of the French translation, overlays on
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $german = $this->get(PostRepository::class)->findByUid(1);
        $this->get(PersistenceManager::class)->clearState();
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON));
        $french = $this->get(PostRepository::class)->findByUid(1);

        // Then: the post returns both tags
        $tagsOfGerman = array_map(static fn(Tag $tag): string => $tag->getName(), $german->getTags()->toArray());
        sort($tagsOfGerman);
        self::assertSame(['PHP', 'TYPO3'], $tagsOfGerman);

        $tagsOfFrench = array_map(static fn(Tag $tag): string => $tag->getName(), $french->getTags()->toArray());
        sort($tagsOfFrench);
        self::assertSame(['PHP', 'TYPO3'], $tagsOfFrench);
    }

    /**
     * Every translation owns its relations to the tags PHP and TYPO3. Read with the overlay of the
     * German or the French translation and overlays mixed, the post returns both tags.
     *
     * @see excludedManyToManyRelationOfATranslationIsReadFromItsOwnRowsWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function excludedManyToManyRelationOfATranslationIsReadFromItsOwnRowsWithOverlaysMixedByCore(): void
    {
        // When: the post is read with the overlay of the German and of the French translation, overlays mixed
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $germanRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED)
        );
        $german = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $germanRow);
        $frenchRow = $this->get(PageRepository::class)->getLanguageOverlay(
            'tx_blogexample_domain_model_post',
            $row,
            new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED)
        );
        $french = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $frenchRow);

        // Then: the post returns both tags
        $tagsOfGerman = array_map(
            static fn(Record $tag): string => $tag->get('name'),
            iterator_to_array($german->get('tags'), false)
        );
        sort($tagsOfGerman);
        self::assertSame(['PHP', 'TYPO3'], $tagsOfGerman);

        $tagsOfFrench = array_map(
            static fn(Record $tag): string => $tag->get('name'),
            iterator_to_array($french->get('tags'), false)
        );
        sort($tagsOfFrench);
        self::assertSame(['PHP', 'TYPO3'], $tagsOfFrench);
    }

    /**
     * @see excludedManyToManyRelationOfATranslationIsReadFromItsOwnRowsWithOverlaysMixedByCore()
     */
    #[Test]
    public function excludedManyToManyRelationOfATranslationIsReadFromItsOwnRowsWithOverlaysMixedByExtbase(): void
    {
        // When: the post is read with the overlay of the German and of the French translation, overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $german = $this->get(PostRepository::class)->findByUid(1);
        $this->get(PersistenceManager::class)->clearState();
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED));
        $french = $this->get(PostRepository::class)->findByUid(1);

        // Then: the post returns both tags
        $tagsOfGerman = array_map(static fn(Tag $tag): string => $tag->getName(), $german->getTags()->toArray());
        sort($tagsOfGerman);
        self::assertSame(['PHP', 'TYPO3'], $tagsOfGerman);

        $tagsOfFrench = array_map(static fn(Tag $tag): string => $tag->getName(), $french->getTags()->toArray());
        sort($tagsOfFrench);
        self::assertSame(['PHP', 'TYPO3'], $tagsOfFrench);
    }

    /**
     * Anna is the author of the English post and of both its translations. A query for the posts of
     * Anna, run with the overlay of the German or the French translation and overlays on, finds that
     * translation (uid and language) and not the English post.
     *
     * @see queryByExcludedAuthorFindsTheTranslatedPostWithOverlaysOnByExtbase()
     */
    #[Test]
    public function queryByExcludedAuthorFindsTheTranslatedPostWithOverlaysOnByCore(): void
    {
        // When: the posts of Anna are selected and overlaid with the German and with the French translation, overlays on
        $rows = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['author' => 1, 'sys_language_uid' => 0, 'deleted' => 0])->fetchAllAssociative();
        $foundInGerman = [];
        $foundInFrench = [];
        foreach ($rows as $row) {
            $german = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow(
                'tx_blogexample_domain_model_post',
                $this->get(PageRepository::class)->getLanguageOverlay('tx_blogexample_domain_model_post', $row, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON))
            );
            self::assertInstanceOf(Record::class, $german);
            $foundInGerman[] = [$german->getOverlaidUid(), $german->getLanguageId()];
            $french = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow(
                'tx_blogexample_domain_model_post',
                $this->get(PageRepository::class)->getLanguageOverlay('tx_blogexample_domain_model_post', $row, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON))
            );
            self::assertInstanceOf(Record::class, $french);
            $foundInFrench[] = [$french->getOverlaidUid(), $french->getLanguageId()];
        }

        // Then: the translations are found, not the English post
        self::assertSame([[2, 1]], $foundInGerman);
        self::assertSame([[3, 2]], $foundInFrench);
    }

    /**
     * @see queryByExcludedAuthorFindsTheTranslatedPostWithOverlaysOnByCore()
     */
    #[Test]
    public function queryByExcludedAuthorFindsTheTranslatedPostWithOverlaysOnByExtbase(): void
    {
        // Given: the person Anna, who exists in the default language only
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF));
        $anna = $this->get(PersonRepository::class)->findOneBy(['firstname' => 'Anna']);

        // When: the posts of Anna are queried with the overlay of the German and of the French translation, overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $germanPosts = $this->get(PostRepository::class)->findBy(['author' => $anna])->toArray();
        $foundInGerman = array_map(
            static fn(Post $post): array => [$post->_getProperty('_localizedUid') ?? $post->getUid(), (int)$post->_getProperty('_languageUid')],
            $germanPosts
        );
        $this->get(PersistenceManager::class)->clearState();
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON));
        $frenchPosts = $this->get(PostRepository::class)->findBy(['author' => $anna])->toArray();
        $foundInFrench = array_map(
            static fn(Post $post): array => [$post->_getProperty('_localizedUid') ?? $post->getUid(), (int)$post->_getProperty('_languageUid')],
            $frenchPosts
        );

        // Then: the translations are found, not the English post
        self::assertSame([[2, 1]], $foundInGerman);
        self::assertSame([[3, 2]], $foundInFrench);
    }

    /**
     * Anna is the author of the English post and of both its translations. A query for the posts of
     * Anna, run with the overlay of the German or the French translation and overlays mixed, finds that
     * translation (uid and language) and not the English post.
     *
     * @see queryByExcludedAuthorFindsTheTranslatedPostWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function queryByExcludedAuthorFindsTheTranslatedPostWithOverlaysMixedByCore(): void
    {
        // When: the posts of Anna are selected and overlaid with the German and with the French translation, overlays mixed
        $rows = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['author' => 1, 'sys_language_uid' => 0, 'deleted' => 0])->fetchAllAssociative();
        $foundInGerman = [];
        $foundInFrench = [];
        foreach ($rows as $row) {
            $german = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow(
                'tx_blogexample_domain_model_post',
                $this->get(PageRepository::class)->getLanguageOverlay('tx_blogexample_domain_model_post', $row, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED))
            );
            self::assertInstanceOf(Record::class, $german);
            $foundInGerman[] = [$german->getOverlaidUid(), $german->getLanguageId()];
            $french = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow(
                'tx_blogexample_domain_model_post',
                $this->get(PageRepository::class)->getLanguageOverlay('tx_blogexample_domain_model_post', $row, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED))
            );
            self::assertInstanceOf(Record::class, $french);
            $foundInFrench[] = [$french->getOverlaidUid(), $french->getLanguageId()];
        }

        // Then: the translations are found, not the English post
        self::assertSame([[2, 1]], $foundInGerman);
        self::assertSame([[3, 2]], $foundInFrench);
    }

    /**
     * @see queryByExcludedAuthorFindsTheTranslatedPostWithOverlaysMixedByCore()
     */
    #[Test]
    public function queryByExcludedAuthorFindsTheTranslatedPostWithOverlaysMixedByExtbase(): void
    {
        // Given: the person Anna, who exists in the default language only
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF));
        $anna = $this->get(PersonRepository::class)->findOneBy(['firstname' => 'Anna']);

        // When: the posts of Anna are queried with the overlay of the German and of the French translation, overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $germanPosts = $this->get(PostRepository::class)->findBy(['author' => $anna])->toArray();
        $foundInGerman = array_map(
            static fn(Post $post): array => [$post->_getProperty('_localizedUid') ?? $post->getUid(), (int)$post->_getProperty('_languageUid')],
            $germanPosts
        );
        $this->get(PersistenceManager::class)->clearState();
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED));
        $frenchPosts = $this->get(PostRepository::class)->findBy(['author' => $anna])->toArray();
        $foundInFrench = array_map(
            static fn(Post $post): array => [$post->_getProperty('_localizedUid') ?? $post->getUid(), (int)$post->_getProperty('_languageUid')],
            $frenchPosts
        );

        // Then: the translations are found, not the English post
        self::assertSame([[2, 1]], $foundInGerman);
        self::assertSame([[3, 2]], $foundInFrench);
    }
}
