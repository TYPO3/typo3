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
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\BookmarkRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Removing records: soft delete and hard delete, translations, inline children and relation rows.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle
 * (DataHandler, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair
 * start from the same fixture in Fixtures/Remove/Given/ and assert the database against the same
 * result CSV in Fixtures/Remove/. Both run as the same administrator at the same fixed time. An
 * Extbase test that differs from core is skipped.
 *
 * Posts, blogs, infos and comments have a soft delete column. A post is translatable and has tags
 * (MM), an info (inline, `additional_info`) and comments (inline, `comments`, mapped with cascade
 * remove). A blog has posts (inline, `posts`, mapped with cascade remove). The table of the
 * bookmark has no delete column and has tags (MM, `tags`) and posts (inline,
 * `posts`, with `foreign_field` and not mapped with cascade remove).
 */
final class RemoveTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/Base.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $GLOBALS['EXEC_TIME'] = 1700000000;
        $this->get(Context::class)->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@1700000000')));
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
     * The English post 1 "Hello world" without translation is deleted through DataHandler. DataHandler keeps the row,
     * sets `deleted` to 1 and sets `tstamp` to the current time.
     *
     * @see deletedRecordIsMarkedDeletedByExtbase()
     */
    #[Test]
    public function deletedRecordIsMarkedDeletedByCore(): void
    {
        // Given: post 1 "Hello world" exists without translation
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/Post.csv');

        // When: post 1 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the post is marked deleted
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedPost.csv');
    }

    /**
     * Extbase keeps the row of post 1 "Hello world", sets `deleted` to 1 and sets `tstamp` to the current time, like
     * DataHandler.
     *
     * @see deletedRecordIsMarkedDeletedByCore()
     */
    #[Test]
    public function deletedRecordIsMarkedDeletedByExtbase(): void
    {
        // Given: post 1 "Hello world" exists without translation
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/Post.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the post is removed and persisted
        $this->get(PostRepository::class)->remove($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the post is marked deleted
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedPost.csv');
    }

    /**
     * The English post 1 "Hello world", which has the German translation 2 "Hallo Welt", is deleted through
     * DataHandler. DataHandler marks the translation deleted as well: `deleted` 1 and the current `tstamp` on both
     * rows.
     *
     * @see deletingTheDefaultRecordDeletesItsTranslationsByExtbase()
     */
    #[Test]
    public function deletingTheDefaultRecordDeletesItsTranslationsByCore(): void
    {
        // Given: post 1 "Hello world" exists with the German translation 2 "Hallo Welt"
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/PostWithTranslation.csv');

        // When: post 1 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the translation is marked deleted as well
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedPostWithTranslation.csv');
    }

    /**
     * Extbase marks only the English post 1 "Hello world" deleted. The German translation 2 "Hallo Welt" keeps
     * `deleted` 0 and its old `tstamp`.
     *
     * @see deletingTheDefaultRecordDeletesItsTranslationsByCore()
     */
    #[Test]
    public function deletingTheDefaultRecordDeletesItsTranslationsByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it marks only the default record deleted and leaves its translations untouched.');

        // Given: post 1 "Hello world" exists with the German translation 2 "Hallo Welt"
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/PostWithTranslation.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the post is removed and persisted
        $this->get(PostRepository::class)->remove($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the translation is marked deleted as well
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedPostWithTranslation.csv');
    }

    /**
     * The German translation 2 "Hallo Welt" of post 1 "Hello world" is deleted through DataHandler. DataHandler marks
     * only the translation deleted; the English post 1 stays untouched.
     *
     * @see deletingATranslationDeletesOnlyTheTranslationByExtbase()
     */
    #[Test]
    public function deletingATranslationDeletesOnlyTheTranslationByCore(): void
    {
        // Given: post 1 "Hello world" exists with the German translation 2 "Hallo Welt"
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/PostWithTranslation.csv');

        // When: the translation 2 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [2 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and only the translation is marked deleted
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedTranslation.csv');
    }

    /**
     * Extbase marks the English post 1 "Hello world" deleted, not the German translation 2 "Hallo Welt", which stays
     * untouched. This is the case although the object was read as the German translation.
     *
     * @see deletingATranslationDeletesOnlyTheTranslationByCore()
     */
    #[Test]
    public function deletingATranslationDeletesOnlyTheTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it marks the default record deleted instead of the translation.');

        // Given: post 1 "Hello world" exists with the German translation 2 "Hallo Welt"
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/PostWithTranslation.csv');
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the German translation is removed and persisted
        $this->get(PostRepository::class)->remove($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: only the translation is marked deleted
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedTranslation.csv');
    }

    /**
     * Post 1 "Hello world" with the info 1 "Extra details" in an inline field is deleted through DataHandler.
     * DataHandler marks the info deleted together with the post.
     *
     * @see deletingARecordDeletesItsInlineChildrenByExtbase()
     */
    #[Test]
    public function deletingARecordDeletesItsInlineChildrenByCore(): void
    {
        // Given: post 1 "Hello world" exists with the info 1 "Extra details" in an inline field without cascade
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/PostWithInfo.csv');

        // When: post 1 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the info is marked deleted
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedPostWithInfo.csv');
    }

    /**
     * Extbase marks only post 1 "Hello world" deleted. The info 1 "Extra details" keeps `deleted` 0 and its old
     * `tstamp`, because the property holding it is not mapped with cascade remove.
     *
     * @see deletingARecordDeletesItsInlineChildrenByCore()
     */
    #[Test]
    public function deletingARecordDeletesItsInlineChildrenByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it leaves the inline children undeleted unless the property is mapped with cascade remove.');

        // Given: post 1 "Hello world" exists with the info 1 "Extra details" in an inline field without cascade
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/PostWithInfo.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the post is removed and persisted
        $this->get(PostRepository::class)->remove($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the info is marked deleted
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedPostWithInfo.csv');
    }

    /**
     * Post 1 "Hello world" with the comments 1 "Nice read" and 2 "Well written" in an inline field is deleted through
     * DataHandler. DataHandler marks both comments deleted together with the post, no matter how the property is mapped
     * in Extbase.
     *
     * @see deletingARecordDeletesItsInlineChildrenWithCascadeRemoveByExtbase()
     */
    #[Test]
    public function deletingARecordDeletesItsInlineChildrenWithCascadeRemoveByCore(): void
    {
        // Given: post 1 "Hello world" exists with the comments 1 and 2 in an inline field with cascade remove
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/PostWithComments.csv');

        // When: post 1 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the comments are marked deleted
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedPostWithComments.csv');
    }

    /**
     * Extbase marks post 1 "Hello world" and both comments 1 "Nice read" and 2 "Well written" deleted, because the
     * property holding the comments is mapped with cascade remove.
     *
     * @see deletingARecordDeletesItsInlineChildrenWithCascadeRemoveByCore()
     */
    #[Test]
    public function deletingARecordDeletesItsInlineChildrenWithCascadeRemoveByExtbase(): void
    {
        // Given: post 1 "Hello world" exists with the comments 1 and 2 in an inline field with cascade remove
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/PostWithComments.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the post is removed and persisted
        $this->get(PostRepository::class)->remove($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the comments are marked deleted
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedPostWithComments.csv');
    }

    /**
     * Blog 1 "Tech blog" with the post 1 "Hello world" in the inline field with cascade remove is deleted through
     * DataHandler; the post has the German translation 2 "Hallo Welt". DataHandler marks the blog, the post and the
     * translation of the post deleted, with the current `tstamp`.
     *
     * @see deletingARecordDeletesTranslatedInlineChildrenWithCascadeRemoveByExtbase()
     */
    #[Test]
    public function deletingARecordDeletesTranslatedInlineChildrenWithCascadeRemoveByCore(): void
    {
        // Given: blog 1 "Tech blog" exists with the post 1 "Hello world" in an inline field with cascade remove, and
        // the German translation 2 "Hallo Welt" of the post
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/BlogWithTranslatedPost.csv');

        // When: blog 1 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_blog' => [1 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the post and its translation are marked deleted
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedBlogWithTranslatedPost.csv');
    }

    /**
     * Extbase marks blog 1 "Tech blog" and the post 1 "Hello world" deleted, because the property holding the post is
     * mapped with cascade remove. The German translation 2 "Hallo Welt" of the post keeps `deleted` 0 and its old
     * `tstamp`.
     *
     * @see deletingARecordDeletesTranslatedInlineChildrenWithCascadeRemoveByCore()
     */
    #[Test]
    public function deletingARecordDeletesTranslatedInlineChildrenWithCascadeRemoveByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: the cascade removes only the child of the default language and leaves its translation undeleted.');

        // Given: blog 1 "Tech blog" exists with the post 1 "Hello world" in an inline field with cascade remove, and
        // the German translation 2 "Hallo Welt" of the post
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/BlogWithTranslatedPost.csv');
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: the blog is removed and persisted
        $this->get(BlogRepository::class)->remove($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the post and its translation are marked deleted
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedBlogWithTranslatedPost.csv');
    }

    /**
     * Bookmark 1 of a table without delete column is deleted through DataHandler. DataHandler removes the row from the
     * table.
     *
     * @see aRecordWithoutDeleteColumnIsRemovedByExtbase()
     */
    #[Test]
    public function aRecordWithoutDeleteColumnIsRemovedByCore(): void
    {
        // Given: Bookmark 1 exists in a table without delete column
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/Bookmark.csv');

        // When: bookmark 1 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_bookmark' => [1 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the row is gone
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/RemovedBookmark.csv');
    }

    /**
     * Extbase removes the row of bookmark 1 from the table, like DataHandler.
     *
     * @see aRecordWithoutDeleteColumnIsRemovedByCore()
     */
    #[Test]
    public function aRecordWithoutDeleteColumnIsRemovedByExtbase(): void
    {
        // Given: Bookmark 1 exists in a table without delete column
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/Bookmark.csv');
        $bookmark = $this->get(BookmarkRepository::class)->findByUid(1);

        // When: the bookmark is removed and persisted
        $this->get(BookmarkRepository::class)->remove($bookmark);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the row is gone
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/RemovedBookmark.csv');
    }

    /**
     * Bookmark 1 of a table without delete column, with the tags 1 "TYPO3" and 2 "PHP", is deleted through DataHandler.
     * DataHandler removes the row and its two rows in the relation table; the tags stay.
     *
     * @see mmRowsOfARemovedRecordByExtbase()
     */
    #[Test]
    public function mmRowsOfARemovedRecordByCore(): void
    {
        // Given: Bookmark 1 exists in a table without delete column, with the tags 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/BookmarkWithTags.csv');

        // When: bookmark 1 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_bookmark' => [1 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the row and its relation rows are gone
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/RemovedBookmarkWithTags.csv');
    }

    /**
     * Extbase removes the row of bookmark 1 and its two rows in the relation table, like DataHandler; the tags stay.
     *
     * @see mmRowsOfARemovedRecordByCore()
     */
    #[Test]
    public function mmRowsOfARemovedRecordByExtbase(): void
    {
        // Given: Bookmark 1 exists in a table without delete column, with the tags 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/BookmarkWithTags.csv');
        $bookmark = $this->get(BookmarkRepository::class)->findByUid(1);

        // When: the bookmark is removed and persisted
        $this->get(BookmarkRepository::class)->remove($bookmark);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the row and its relation rows are gone
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/RemovedBookmarkWithTags.csv');
    }

    /**
     * Post 1 "Hello world" with the tags 1 "TYPO3" and 2 "PHP" is deleted through DataHandler. DataHandler marks the
     * post deleted and keeps its two rows in the relation table.
     *
     * @see mmRowsOfASoftDeletedRecordByExtbase()
     */
    #[Test]
    public function mmRowsOfASoftDeletedRecordByCore(): void
    {
        // Given: post 1 "Hello world" exists with the tags 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/PostWithTags.csv');

        // When: post 1 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the relation rows are as expected
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedPostWithTags.csv');
    }

    /**
     * Extbase marks post 1 "Hello world" deleted and keeps its two rows in the relation table, like DataHandler.
     *
     * @see mmRowsOfASoftDeletedRecordByCore()
     */
    #[Test]
    public function mmRowsOfASoftDeletedRecordByExtbase(): void
    {
        // Given: post 1 "Hello world" exists with the tags 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/PostWithTags.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the post is removed and persisted
        $this->get(PostRepository::class)->remove($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the relation rows are as expected
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/DeletedPostWithTags.csv');
    }

    /**
     * An editor removes the post 1 "Hello world" from the inline field `posts` of bookmark 1 in the backend form. The
     * form sends the remaining list with the post 2 "Second post" as the only entry in the datamap and a delete command
     * for the post 1 in the cmdmap, and DataHandler processes both in one run. DataHandler marks the post 1 deleted
     * with the current `tstamp`; its field `bookmark` pointing to the bookmark keeps the uid. The counter `posts` of
     * the bookmark drops to 1 and the `tstamp` of the post 2 is set to the current time.
     *
     * @see removingAChildFromTheParentByExtbase()
     */
    #[Test]
    public function removingAChildFromTheParentByCore(): void
    {
        // Given: Bookmark 1 exists with the posts 1 and 2, in an inline field with `foreign_field` and without cascade
        // remove
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/BookmarkWithPosts.csv');

        // When: bookmark 1 is saved with the post 2 only and the post 1 is deleted, as the backend form sends it
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_bookmark' => [1 => ['posts' => '2']]],
            ['tx_blogexample_domain_model_post' => [1 => ['delete' => 1]]]
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the post 1 is marked deleted
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/RemovedPostFromBookmark.csv');
    }

    /**
     * Extbase does not delete the post 1 "Hello world". It detaches it: `deleted` stays 0 and its field `bookmark`
     * pointing to the bookmark is set to 0, so the post stays in the table as an orphan. The counter `posts` of the
     * bookmark drops to 1; the post 2 stays untouched.
     *
     * @see removingAChildFromTheParentByCore()
     */
    #[Test]
    public function removingAChildFromTheParentByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it detaches the removed child from the parent instead of deleting it.');

        // Given: Bookmark 1 exists with the posts 1 and 2, in an inline field with `foreign_field` and without cascade
        // remove
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Remove/Given/BookmarkWithPosts.csv');
        $bookmark = $this->get(BookmarkRepository::class)->findByUid(1);
        $helloWorld = array_find($bookmark->getPosts()->toArray(), static fn(Post $post): bool => $post->getTitle() === 'Hello world');

        // When: the post "Hello world" is removed from the bookmark and the bookmark is persisted
        $bookmark->removePost($helloWorld);
        $this->get(BookmarkRepository::class)->update($bookmark);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the post 1 is marked deleted
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Remove/RemovedPostFromBookmark.csv');
    }
}
