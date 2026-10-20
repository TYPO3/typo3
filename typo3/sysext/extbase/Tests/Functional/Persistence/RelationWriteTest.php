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
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Writing relations: relation rows of an MM relation, the order of inline children, file references and lazy MM
 * relations.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle
 * (DataHandler, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair
 * start from the same fixture in Fixtures/RelationWrite/Given/ and assert the database against the same
 * result CSV in Fixtures/RelationWrite/. Both run as the same administrator at the same fixed time. An
 * Extbase test that differs from core is skipped.
 *
 * The DataHandler side sends what the backend form sends: the uids of an MM relation as a comma separated list in the
 * field of the record, new inline children and new file references as NEW... records that carry the uid of the parent
 * and are listed in the field of the parent, and the full list of children in the new order when children are
 * reordered.
 *
 * A post has tags (MM, `tags`, `tx_blogexample_domain_model_tag_mm`) and related posts (MM, `related_posts`,
 * `tx_blogexample_post_post_mm`, mapped lazy). A blog has posts (inline, `posts`, with `foreign_sortby`) and a logo
 * (file, `logo`).
 */
final class RelationWriteTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/Base.csv');
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
     * The tags 1 "TYPO3" and 2 "PHP" are set on post 1 "Hello world" through DataHandler, as the backend form sends
     * them: the field `tags` holds the uid list "1,2". DataHandler writes one row per tag into the relation table with
     * `uid_foreign` 1, the `sorting_foreign` 1 and 2 in the order of the list, `sorting` 0 and the match fields
     * `tablenames` and `fieldname`, writes the number of tags, 2, into the field `tags` of the post and sets its
     * `tstamp` to the current time.
     *
     * @see addingTagsWritesMmRowsInOrderByExtbase()
     */
    #[Test]
    public function addingTagsWritesMmRowsInOrderByCore(): void
    {
        // Given: post 1 exists without tags
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/Post.csv');

        // When: the tags 1 and 2 are set on post 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['tags' => '1,2']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the relation rows carry the order
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/AddedTags.csv');
    }

    /**
     * Extbase adds the tags 1 "TYPO3" and 2 "PHP" to post 1 "Hello world" and persists it with `update()` and
     * `persistAll()`. Extbase writes the same relation rows with the `sorting_foreign` 1 and 2 and writes the number of
     * tags, 2, into the post, like DataHandler.
     *
     * @see addingTagsWritesMmRowsInOrderByCore()
     */
    #[Test]
    public function addingTagsWritesMmRowsInOrderByExtbase(): void
    {
        // Given: post 1 exists without tags
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/Post.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the tags 1 and 2 are added and persisted
        $post->addTag($this->get(PersistenceManager::class)->getObjectByIdentifier(1, Tag::class));
        $post->addTag($this->get(PersistenceManager::class)->getObjectByIdentifier(2, Tag::class));
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the relation rows carry the order
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/AddedTags.csv');
    }

    /**
     * The tags of post 1 "Hello world" are sent in the order 2, 1 through DataHandler, as the backend form sends them
     * after the editor moved a tag: the field `tags` holds the uid list "2,1". DataHandler keeps both relation rows and
     * rewrites their `sorting_foreign`: tag 2 gets the `sorting_foreign` 1, tag 1 gets the `sorting_foreign` 2.
     *
     * @see reorderingTagsRewritesTheSortingByExtbase()
     */
    #[Test]
    public function reorderingTagsRewritesTheSortingByCore(): void
    {
        // Given: post 1 exists with the tags 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/PostWithTags.csv');

        // When: the tags are sent in the order 2, 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['tags' => '2,1']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the sorting of the relation rows is swapped
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/ReorderedTags.csv');
    }

    /**
     * Extbase detaches both tags from post 1 "Hello world", attaches them again in the order 2, 1 and persists the post
     * with `update()` and `persistAll()`. Extbase keeps both relation rows and rewrites their `sorting_foreign` like
     * DataHandler: tag 2 gets the `sorting_foreign` 1, tag 1 gets the `sorting_foreign` 2.
     *
     * @see reorderingTagsRewritesTheSortingByCore()
     */
    #[Test]
    public function reorderingTagsRewritesTheSortingByExtbase(): void
    {
        // Given: post 1 exists with the tags 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/PostWithTags.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the tags are put in the order 2, 1 and persisted
        $tags = $post->getTags()->toArray();
        foreach ($tags as $tag) {
            $post->removeTag($tag);
        }
        foreach (array_reverse($tags) as $tag) {
            $post->addTag($tag);
        }
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the sorting of the relation rows is swapped
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/ReorderedTags.csv');
    }

    /**
     * Only tag 2 "PHP" is sent for post 1 "Hello world" through DataHandler, as the backend form sends the remaining
     * tags after the editor removed tag 1. DataHandler deletes the relation row of tag 1, renumbers the
     * `sorting_foreign` of the remaining row of tag 2 to 1 and writes the number of tags, 1, into the post.
     *
     * @see removingOneTagRemovesItsMmRowByExtbase()
     */
    #[Test]
    public function removingOneTagRemovesItsMmRowByCore(): void
    {
        // Given: post 1 exists with the tags 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/PostWithTags.csv');

        // When: only tag 2 is sent
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['tags' => '2']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the relation row of tag 1 is gone
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/RemovedTag.csv');
    }

    /**
     * Extbase removes tag 1 "TYPO3" from post 1 "Hello world" and persists the post with `update()` and `persistAll()`.
     * Extbase deletes the relation row of tag 1 and writes the number of tags, 1, into the post, but leaves the
     * `sorting_foreign` of the remaining row of tag 2 at 2 instead of renumbering it to 1.
     *
     * @see removingOneTagRemovesItsMmRowByCore()
     */
    #[Test]
    public function removingOneTagRemovesItsMmRowByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it deletes the relation row of the removed tag but leaves the remaining row at the `sorting_foreign` 2 instead of renumbering it to 1.');

        // Given: post 1 exists with the tags 1 and 2
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/PostWithTags.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: tag 1 is removed and persisted
        $post->removeTag($this->get(PersistenceManager::class)->getObjectByIdentifier(1, Tag::class));
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the relation row of tag 1 is gone
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/RemovedTag.csv');
    }

    /**
     * Two new posts are sent as inline children of blog 1 "Tech blog" through DataHandler, as the backend form sends
     * them: the field `posts` of the blog holds "NEW1,NEW2" and each post is a NEW record with its `pid` and the uid of
     * the blog in its field `blog`. DataHandler creates both posts with the uid of blog 1, the `sorting` 1 and 2
     * (`foreign_sortby`) and the current time as `crdate` and `tstamp`, and writes the number of posts, 2, into the
     * blog.
     *
     * @see addingInlineChildrenKeepsTheirOrderByExtbase()
     */
    #[Test]
    public function addingInlineChildrenKeepsTheirOrderByCore(): void
    {
        // Given: blog 1 exists without posts
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/Blog.csv');

        // When: two new posts are sent as the inline children of blog 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_blogexample_domain_model_blog' => [1 => ['posts' => 'NEW1,NEW2']],
            'tx_blogexample_domain_model_post' => [
                'NEW1' => ['pid' => 2, 'title' => 'First post', 'blog' => 1],
                'NEW2' => ['pid' => 2, 'title' => 'Second post', 'blog' => 1],
            ],
        ], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the posts carry the uid of the blog, their order and the counter
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/AddedPosts.csv');
    }

    /**
     * Extbase adds two new posts to blog 1 "Tech blog" and persists the blog with `update()` and `persistAll()`.
     * Extbase creates both posts with the uid of blog 1, the `sorting` 1 and 2 and the current time as `crdate` and
     * `tstamp`, and writes the number of posts, 2, into the blog, like DataHandler.
     *
     * @see addingInlineChildrenKeepsTheirOrderByCore()
     */
    #[Test]
    public function addingInlineChildrenKeepsTheirOrderByExtbase(): void
    {
        // Given: blog 1 exists without posts
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/Blog.csv');
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: two new posts are added and persisted
        $firstPost = new Post();
        $firstPost->setTitle('First post');
        $secondPost = new Post();
        $secondPost->setTitle('Second post');
        $blog->addPost($firstPost);
        $blog->addPost($secondPost);
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the posts carry the uid of the blog, their order and the counter
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/AddedPosts.csv');
    }

    /**
     * The posts of blog 1 "Tech blog" are sent in the order 2, 1 through DataHandler, as the backend form sends them
     * after the editor moved a post: the field `posts` holds the uid list "2,1". DataHandler rewrites the `sorting` of
     * the posts: post 2 gets the `sorting` 1, post 1 gets the `sorting` 2. It sets `tstamp` of both posts and of the
     * blog to the current time.
     *
     * @see reorderingInlineChildrenByExtbase()
     */
    #[Test]
    public function reorderingInlineChildrenByCore(): void
    {
        // Given: blog 1 exists with the posts 1 "Hello world" and 2 "Second post"
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/BlogWithPosts.csv');

        // When: the posts are sent in the order 2, 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_blog' => [1 => ['posts' => '2,1']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the sorting of the posts is swapped
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/ReorderedPosts.csv');
    }

    /**
     * Extbase detaches both posts from blog 1 "Tech blog", attaches them again in the order 2, 1 and persists the blog
     * with `update()` and `persistAll()`. Extbase rewrites the `sorting` of the posts like DataHandler, post 2 gets the
     * `sorting` 1 and post 1 the `sorting` 2, and sets `tstamp` of both posts and of the blog to the current time.
     *
     * @see reorderingInlineChildrenByCore()
     */
    #[Test]
    public function reorderingInlineChildrenByExtbase(): void
    {
        // Given: blog 1 exists with the posts 1 "Hello world" and 2 "Second post"
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/BlogWithPosts.csv');
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: the posts are put in the order 2, 1 and persisted
        $posts = $blog->getPosts()->toArray();
        foreach ($posts as $post) {
            $blog->removePost($post);
        }
        foreach (array_reverse($posts) as $post) {
            $blog->addPost($post);
        }
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the sorting of the posts is swapped
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/ReorderedPosts.csv');
    }

    /**
     * A new reference to the file 1 is sent as the logo of blog 1 "Tech blog" through DataHandler, as the backend form
     * sends it: the field `logo` of the blog holds "NEW1" and the reference is a NEW `sys_file_reference` record with
     * its `pid`, `uid_local` 1 and the uid of the blog as `uid_foreign`. DataHandler completes the row with
     * `tablenames` and `fieldname` logo, the `sorting_foreign` 1 and the current time as `crdate` and `tstamp`, and
     * writes the number of references, 1, into the blog.
     *
     * @see addingAFileReferenceByExtbase()
     */
    #[Test]
    public function addingAFileReferenceByCore(): void
    {
        // Given: blog 1 exists without logo, the file 1 exists
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/BlogWithFile.csv');

        // When: a new reference to the file 1 is sent as the logo of blog 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_blogexample_domain_model_blog' => [1 => ['logo' => 'NEW1']],
            'sys_file_reference' => ['NEW1' => ['pid' => 2, 'uid_local' => 1, 'uid_foreign' => 1]],
        ], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error, the reference row is complete and the counter is set
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/AddedFileReference.csv');
    }

    /**
     * Extbase attaches a new Extbase file reference, built from a core file reference object for the file 1, to blog 1
     * "Tech blog" and persists the blog with `update()` and `persistAll()`. Extbase writes the same
     * `sys_file_reference` row as DataHandler, with `tablenames`, `fieldname` logo, `sorting_foreign` 1 and the current
     * time as `crdate` and `tstamp`, and writes the number of references, 1, into the blog.
     *
     * @see addingAFileReferenceByCore()
     */
    #[Test]
    public function addingAFileReferenceByExtbase(): void
    {
        // Given: blog 1 exists without logo, the file 1 exists
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/BlogWithFile.csv');
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: a new reference to the file 1 is attached and persisted
        $reference = new FileReference();
        $reference->setOriginalResource(
            $this->get(ResourceFactory::class)->createFileReferenceObject(['uid_local' => 1, 'uid_foreign' => 1, 'uid' => uniqid('NEW_')])
        );
        $blog->getLogo()->attach($reference);
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the reference row is complete and the counter is set
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/AddedFileReference.csv');
    }

    /**
     * The related posts 2 and 3 are sent again for post 1 "Hello world" through DataHandler, as the backend form sends
     * them when the editor saves without changing the relation: the field `related_posts` holds the uid list "2,3" that
     * is already stored. DataHandler leaves both relation rows as they are and the number of related posts at 2, and
     * sets `tstamp` of post 1 to the current time.
     *
     * @see replacingAnUnreadLazyStorageWithTheSameRelatedPostsByExtbase()
     */
    #[Test]
    public function replacingAnUnreadLazyStorageWithTheSameRelatedPostsByCore(): void
    {
        // Given: post 1 exists with the related posts 2 and 3
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/PostWithRelatedPosts.csv');

        // When: the same related posts 2 and 3 are sent
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['related_posts' => '2,3']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the relation rows are unchanged
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/ReplacedRelatedPostsWithSame.csv');
    }

    /**
     * Extbase loads post 1 "Hello world", never reads its related posts, sets a new `ObjectStorage` holding the posts 2
     * and 3 on it and persists the post with `update()` and `persistAll()`. Because the lazy storage was never read,
     * Extbase does not know the stored relation, so it inserts the rows of both related posts again and `persistAll()`
     * fails with an SQL error for a UNIQUE constraint on the relation table, while DataHandler leaves the two rows as
     * they are.
     *
     * @see replacingAnUnreadLazyStorageWithTheSameRelatedPostsByCore()
     */
    #[Test]
    public function replacingAnUnreadLazyStorageWithTheSameRelatedPostsByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: replacing a lazy storage that was never read inserts all relation rows again and `persistAll()` throws a UNIQUE constraint error.');

        // Given: post 1 exists with the related posts 2 and 3
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/PostWithRelatedPosts.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the lazy storage is replaced by a new one with the same related posts, without reading it first, and persisted
        /** @var ObjectStorage<Post> $relatedPosts */
        $relatedPosts = new ObjectStorage();
        $relatedPosts->attach($this->get(PostRepository::class)->findByUid(2));
        $relatedPosts->attach($this->get(PostRepository::class)->findByUid(3));
        $post->setRelatedPosts($relatedPosts);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the relation rows are unchanged
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/ReplacedRelatedPostsWithSame.csv');
    }

    /**
     * Only post 3 is sent as related post of post 1 "Hello world" through DataHandler, as the backend form sends the
     * remaining post after the editor removed post 2. DataHandler deletes the relation row of post 2, renumbers the
     * `sorting` of the row of post 3 to 1, writes the number of related posts, 1, into post 1 and sets its `tstamp` to
     * the current time.
     *
     * @see replacingAnUnreadLazyStorageWithFewerRelatedPostsByExtbase()
     */
    #[Test]
    public function replacingAnUnreadLazyStorageWithFewerRelatedPostsByCore(): void
    {
        // Given: post 1 exists with the related posts 2 and 3
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/PostWithRelatedPosts.csv');

        // When: only the related post 3 is sent
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['related_posts' => '3']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the relation row of post 2 is gone
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/ReplacedRelatedPostsWithFewer.csv');
    }

    /**
     * Extbase loads post 1 "Hello world", never reads its related posts, sets a new `ObjectStorage` holding only post 3
     * on it and persists the post with `update()` and `persistAll()`. Because the lazy storage was never read, Extbase
     * inserts a row for post 3 although it exists and `persistAll()` fails with an SQL error for a UNIQUE constraint on
     * the relation table.
     *
     * @see replacingAnUnreadLazyStorageWithFewerRelatedPostsByCore()
     */
    #[Test]
    public function replacingAnUnreadLazyStorageWithFewerRelatedPostsByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: replacing a lazy storage that was never read inserts the rows again and `persistAll()` throws a UNIQUE constraint error.');

        // Given: post 1 exists with the related posts 2 and 3
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/Given/PostWithRelatedPosts.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the lazy storage is replaced by a new one with post 3 only, without reading it first, and persisted
        /** @var ObjectStorage<Post> $relatedPosts */
        $relatedPosts = new ObjectStorage();
        $relatedPosts->attach($this->get(PostRepository::class)->findByUid(3));
        $post->setRelatedPosts($relatedPosts);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the relation row of post 2 is gone
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationWrite/ReplacedRelatedPostsWithFewer.csv');
    }
}
