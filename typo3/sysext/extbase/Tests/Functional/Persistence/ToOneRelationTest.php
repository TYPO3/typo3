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
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PersonRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Writing a relation to a single record: clearing, replacing and moving the related record of a relation with at most
 * one record.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle (DataHandler, suffix
 * "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair start from the same fixture in
 * Fixtures/ToOneRelation/Given/ and assert the database against the same result CSV in Fixtures/ToOneRelation/. Both
 * run as the same administrator at the same fixed time. An Extbase test that differs from core is skipped.
 *
 * The DataHandler side sends what the backend form sends: an empty field for a cleared relation, the new value of the
 * field for a replaced one, `NEW...` records that carry the uid of the parent for new inline children and new file
 * references, and a delete command for a child or a file reference the editor deleted.
 *
 * blog_example has five fields for a single related record: `featured_post` of a blog (inline, `foreign_field`
 * `featured_blog`, `relationship` oneToOne, so the relation is stored in the post), `avatar` of a person (file field,
 * `maxitems` 1, stored in the file reference), `author` of a post (select with a foreign table), `pinned_tag` of a
 * person (group, `maxitems` 1) and `main_tag` of a person (select with a relation table, MM, `maxitems` 1).
 * The domain models have a property typed with the related domain object for each of them. Posts, blogs and persons are
 * on the storage page 2, tags 1 and 2 exist.
 */
final class ToOneRelationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/Base.csv');
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
     * The featured post of blog 1 "Tech blog" is cleared through DataHandler, as the backend form sends it after the
     * editor deleted the post: the field `featured_post` of the blog is empty and post 1 "Hello world" is deleted by a
     * command. DataHandler writes the number of posts, 0, into the field `featured_post` of blog 1, marks post 1 as
     * deleted and sets `tstamp` of the blog and of the post to the current time. It leaves the field `featured_blog` of
     * post 1 at 1.
     *
     * @see clearingTheFeaturedPostByExtbase()
     */
    #[Test]
    public function clearingTheFeaturedPostByCore(): void
    {
        // Given: blog 1 "Tech blog" has the featured post 1 "Hello world", blog 2 "Travel blog" has none
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/FeaturedPost.csv');

        // When: the form clears the featured post of blog 1 and deletes the post
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_blog' => [1 => ['featured_post' => '']]],
            ['tx_blogexample_domain_model_post' => [1 => ['delete' => 1]]]
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the post is deleted and the field is empty
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ClearedFeaturedPost.csv');
    }

    /**
     * Extbase sets the featured post of blog 1 "Tech blog" to null and persists the blog with `update()` and
     * `persistAll()`. Extbase writes 0 into the field `featured_post` of blog 1 and sets `tstamp` of the blog, but it
     * does not touch post 1 "Hello world": the post is neither deleted nor released, it keeps `deleted` 0, the old
     * `tstamp` and the uid of blog 1 in its field `featured_blog`.
     *
     * @see clearingTheFeaturedPostByCore()
     */
    #[Test]
    public function clearingTheFeaturedPostByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes 0 into the field `featured_post` of blog 1 but leaves post 1 undeleted (`deleted` 0, old `tstamp`) and still attached to blog 1.');

        // Given: blog 1 "Tech blog" has the featured post 1 "Hello world", blog 2 "Travel blog" has none
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/FeaturedPost.csv');
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: the featured post is cleared and persisted
        $blog->setFeaturedPost(null);
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the post is deleted and the field is empty
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ClearedFeaturedPost.csv');
    }

    /**
     * A new post replaces the featured post of blog 1 "Tech blog" through DataHandler, as the backend form sends it
     * after the editor deleted the old and created a new post: the field `featured_post` of the blog holds `NEW1`,
     * `NEW1` is a new post with its `pid`, its title "Second post" and the uid of the blog in its field
     * `featured_blog`, and post 1 "Hello world" is deleted by a command. DataHandler creates post 2 with blog 1 in
     * `featured_blog` and the current time as `crdate` and `tstamp`, marks post 1 as deleted and writes the number of
     * posts, 1, into the field `featured_post` of blog 1.
     *
     * @see replacingTheFeaturedPostByExtbase()
     */
    #[Test]
    public function replacingTheFeaturedPostByCore(): void
    {
        // Given: blog 1 "Tech blog" has the featured post 1 "Hello world"
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/FeaturedPost.csv');

        // When: a new post replaces the featured post of blog 1, the old post is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tx_blogexample_domain_model_blog' => [1 => ['featured_post' => 'NEW1']],
                'tx_blogexample_domain_model_post' => ['NEW1' => ['pid' => 2, 'title' => 'Second post', 'featured_blog' => 1]],
            ],
            ['tx_blogexample_domain_model_post' => [1 => ['delete' => 1]]]
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and post 1 is deleted and the new post is the featured post of blog 1
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ReplacedFeaturedPost.csv');
    }

    /**
     * Extbase sets a new post with the title "Second post" as the featured post of blog 1 "Tech blog" and persists the
     * blog with `update()` and `persistAll()`. Extbase creates post 2 with blog 1 in `featured_blog` and the current
     * time as `crdate` and `tstamp`, but writes the uid of the new post, 2, into the field `featured_post` of blog 1
     * instead of the number of posts, 1, and leaves post 1 "Hello world" undeleted.
     *
     * @see replacingTheFeaturedPostByCore()
     */
    #[Test]
    public function replacingTheFeaturedPostByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes the uid 2 of the new post into the field `featured_post` of blog 1 instead of the number of posts 1 and leaves post 1 undeleted.');

        // Given: blog 1 "Tech blog" has the featured post 1 "Hello world"
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/FeaturedPost.csv');
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: a new post replaces the featured post and is persisted
        $post = new Post();
        $post->setTitle('Second post');
        $blog->setFeaturedPost($post);
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: post 1 is deleted and the new post is the featured post of blog 1
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ReplacedFeaturedPost.csv');
    }

    /**
     * Post 1 "Hello world" is moved from blog 1 "Tech blog" to blog 2 "Travel blog" through DataHandler: the field
     * `featured_post` of blog 1 is sent empty and the field `featured_post` of blog 2 is sent as 1, without a command.
     * DataHandler writes 0 into the field `featured_post` of blog 1 and the number of posts, 1, into the field
     * `featured_post` of blog 2, changes the field `featured_blog` of post 1 to 2 and sets `tstamp` of both blogs and
     * of the post to the current time.
     *
     * @see movingTheFeaturedPostByExtbase()
     */
    #[Test]
    public function movingTheFeaturedPostByCore(): void
    {
        // Given: blog 1 "Tech blog" has the featured post 1 "Hello world", blog 2 "Travel blog" has none
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/FeaturedPost.csv');

        // When: the post is removed from the field of blog 1 and added to the field of blog 2
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tx_blogexample_domain_model_blog' => [
                    1 => ['featured_post' => ''],
                    2 => ['featured_post' => '1'],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and post 1 is the featured post of blog 2
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/MovedFeaturedPost.csv');
    }

    /**
     * Extbase takes the featured post of blog 1 "Tech blog" with `getFeaturedPost()` and reads its title, which loads
     * the post, sets the featured post of blog 1 to null, sets the post as the featured post of blog 2 "Travel blog"
     * and persists both blogs with `update()` and `persistAll()`. Extbase writes 0 into the field `featured_post` of
     * blog 1 and the uid of the post, 1, into the field `featured_post` of blog 2 and sets `tstamp` of both blogs. It
     * does not write post 1: its field `featured_blog` stays 1 and its `tstamp` stays old.
     *
     * @see movingTheFeaturedPostByCore()
     */
    #[Test]
    public function movingTheFeaturedPostByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes the uid of post 1 into the field `featured_post` of blog 2 but leaves the field `featured_blog` of post 1 at 1 and its `tstamp` old.');

        // Given: blog 1 "Tech blog" has the featured post 1 "Hello world", blog 2 "Travel blog" has none
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/FeaturedPost.csv');
        $blog1 = $this->get(BlogRepository::class)->findByUid(1);
        $blog2 = $this->get(BlogRepository::class)->findByUid(2);

        // When: the featured post is moved from blog 1 to blog 2 and both are persisted
        $post = $blog1->getFeaturedPost();
        $post->getTitle();
        $blog1->setFeaturedPost(null);
        $blog2->setFeaturedPost($post);
        $this->get(BlogRepository::class)->update($blog1);
        $this->get(BlogRepository::class)->update($blog2);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: post 1 is the featured post of blog 2
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/MovedFeaturedPost.csv');
    }

    /**
     * The avatar of person 1 "Jane Doe" is cleared through DataHandler, as the backend form sends it after the editor
     * deleted the file reference: the field `avatar` of the person is empty and file reference 1 is deleted by a
     * command. DataHandler writes the number of references, 0, into the field `avatar` of person 1, marks the reference
     * as deleted and sets `tstamp` of the person and of the reference to the current time.
     *
     * @see clearingTheAvatarByExtbase()
     */
    #[Test]
    public function clearingTheAvatarByCore(): void
    {
        // Given: person 1 "Jane Doe" has the avatar, file reference 1 to the file 1, person 2 "John Roe" has none
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/Avatar.csv');

        // When: the form clears the avatar of Jane and deletes the file reference
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_person' => [1 => ['avatar' => '']]],
            ['sys_file_reference' => [1 => ['delete' => 1]]]
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the file reference is deleted and the field is empty
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ClearedAvatar.csv');
    }

    /**
     * Extbase sets the avatar of person 1 "Jane Doe" to null and persists the person with `update()` and
     * `persistAll()`. Extbase writes 0 into the field `avatar` of person 1 and sets `tstamp` of the person, but it does
     * not touch file reference 1: the reference is not deleted, it keeps `deleted` 0, the old `tstamp` and
     * `uid_foreign` 1.
     *
     * @see clearingTheAvatarByCore()
     */
    #[Test]
    public function clearingTheAvatarByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes 0 into the field `avatar` of person 1 but leaves file reference 1 undeleted (`deleted` 0, old `tstamp`) and still pointing to person 1.');

        // Given: person 1 "Jane Doe" has the avatar, file reference 1 to the file 1, person 2 "John Roe" has none
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/Avatar.csv');
        $jane = $this->get(PersonRepository::class)->findByUid(1);

        // When: the avatar is cleared and persisted
        $jane->setAvatar(null);
        $this->get(PersonRepository::class)->update($jane);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the file reference is deleted and the field is empty
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ClearedAvatar.csv');
    }

    /**
     * A new file reference replaces the avatar of person 1 "Jane Doe" through DataHandler, as the backend form sends it
     * after the editor deleted the old and added a new reference: the field `avatar` of the person holds `NEW1`, `NEW1`
     * is a new `sys_file_reference` with its `pid`, `uid_local` 1 and the uid of the person as `uid_foreign`, and file
     * reference 1 is deleted by a command. DataHandler creates file reference 2 with `tablenames`, `fieldname` avatar,
     * `sorting_foreign` 1 and the current time as `crdate` and `tstamp`, marks reference 1 as deleted and writes the
     * number of references, 1, into the field `avatar` of person 1.
     *
     * @see replacingTheAvatarByExtbase()
     */
    #[Test]
    public function replacingTheAvatarByCore(): void
    {
        // Given: person 1 "Jane Doe" has the avatar, file reference 1 to the file 1
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/Avatar.csv');

        // When: a new file reference replaces the avatar of Jane, the old reference is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tx_blogexample_domain_model_person' => [1 => ['avatar' => 'NEW1']],
                'sys_file_reference' => ['NEW1' => ['pid' => 2, 'uid_local' => 1, 'uid_foreign' => 1]],
            ],
            ['sys_file_reference' => [1 => ['delete' => 1]]]
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and file reference 1 is deleted and the new reference belongs to person 1
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ReplacedAvatar.csv');
    }

    /**
     * Extbase sets a new Extbase file reference for the file 1 on person 1 "Jane Doe" and persists the person with
     * `update()` and `persistAll()`. Extbase creates file reference 2 with `tablenames`, `fieldname` avatar and the
     * current time as `crdate` and `tstamp`, but with `sorting_foreign` 0, writes the uid of the new reference, 2, into
     * the field `avatar` of person 1 instead of the number of references and leaves reference 1 undeleted.
     *
     * @see replacingTheAvatarByCore()
     */
    #[Test]
    public function replacingTheAvatarByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes the uid 2 of the new file reference into the field `avatar` of person 1 instead of the number of references 1, creates the reference with `sorting_foreign` 0 instead of 1 and leaves file reference 1 undeleted.');

        // Given: person 1 "Jane Doe" has the avatar, file reference 1 to the file 1
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/Avatar.csv');
        $jane = $this->get(PersonRepository::class)->findByUid(1);

        // When: a new file reference replaces the avatar and is persisted
        $avatar = new FileReference();
        $avatar->setOriginalResource(
            $this->get(ResourceFactory::class)->createFileReferenceObject(['uid_local' => 1, 'uid_foreign' => 1, 'uid' => uniqid('NEW_')])
        );
        $jane->setAvatar($avatar);
        $this->get(PersonRepository::class)->update($jane);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: file reference 1 is deleted and the new reference belongs to person 1
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ReplacedAvatar.csv');
    }

    /**
     * File reference 1 is moved from person 1 "Jane Doe" to person 2 "John Roe" through DataHandler: the field `avatar`
     * of person 1 is sent empty and the field `avatar` of person 2 is sent as 1, without a command. DataHandler writes
     * 0 into the field `avatar` of person 1 and the number of references, 1, into the field `avatar` of person 2,
     * changes `uid_foreign` of reference 1 to 2 and sets `tstamp` of both persons and of the reference to the current
     * time.
     *
     * @see movingTheAvatarByExtbase()
     */
    #[Test]
    public function movingTheAvatarByCore(): void
    {
        // Given: person 1 "Jane Doe" has the avatar, file reference 1 to the file 1, person 2 "John Roe" has none
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/Avatar.csv');

        // When: the file reference is removed from the field of Jane and added to the field of John
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tx_blogexample_domain_model_person' => [
                    1 => ['avatar' => ''],
                    2 => ['avatar' => '1'],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and file reference 1 belongs to person 2
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/MovedAvatar.csv');
    }

    /**
     * Extbase sets the avatar of person 1 "Jane Doe" to null, sets the same file reference as the avatar of person 2
     * John Roe and persists both persons with `update()` and `persistAll()`. Extbase writes 0 into the field `avatar`
     * of person 1 and the uid of the reference, 1, into the field `avatar` of person 2, which equals the number of
     * references only by coincidence, but it never changes `uid_foreign` of the reference: it still points to person 1
     * and keeps its old `tstamp`.
     *
     * @see movingTheAvatarByCore()
     */
    #[Test]
    public function movingTheAvatarByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes 0 into the field `avatar` of person 1 and the uid 1 of the file reference into the field `avatar` of person 2 but leaves reference 1 pointing to person 1 (`uid_foreign` 1 instead of 2).');

        // Given: person 1 "Jane Doe" has the avatar, file reference 1 to the file 1, person 2 "John Roe" has none
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/Avatar.csv');
        $jane = $this->get(PersonRepository::class)->findByUid(1);
        $john = $this->get(PersonRepository::class)->findByUid(2);

        // When: the avatar is moved from Jane to John and both are persisted
        $avatar = $jane->getAvatar();
        $jane->setAvatar(null);
        $john->setAvatar($avatar);
        $this->get(PersonRepository::class)->update($jane);
        $this->get(PersonRepository::class)->update($john);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: file reference 1 belongs to person 2
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/MovedAvatar.csv');
    }

    /**
     * The author of post 1 "Hello world" is cleared through DataHandler, as the backend form sends it when the editor
     * selects the empty item `--none--`: the field `author` holds 0. DataHandler writes 0 into the field `author` of
     * post 1 and sets `tstamp` of the post to the current time. Person 1 Jane Doe is not touched.
     *
     * @see clearingTheAuthorByExtbase()
     */
    #[Test]
    public function clearingTheAuthorByCore(): void
    {
        // Given: post 1 "Hello world" has the author 1 Jane Doe
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/Authors.csv');

        // When: the author of the post is cleared
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_post' => [1 => ['author' => 0]]],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the field `author` holds 0
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ClearedAuthor.csv');
    }

    /**
     * Extbase sets the author of post 1 "Hello world" to null and persists the post with `update()` and `persistAll()`.
     * Extbase writes 0 into the field `author` of post 1 and sets `tstamp` of the post, like DataHandler.
     *
     * @see clearingTheAuthorByCore()
     */
    #[Test]
    public function clearingTheAuthorByExtbase(): void
    {
        // Given: post 1 "Hello world" has the author 1 Jane Doe
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/Authors.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the author is cleared and persisted
        $post->setAuthor(null);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the field `author` holds 0
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ClearedAuthor.csv');
    }

    /**
     * Person 2 John Roe replaces the author of post 1 "Hello world" through DataHandler, as the backend form sends it
     * when the editor selects the item: the field `author` holds 2. DataHandler writes 2 into the field `author` of
     * post 1 and sets `tstamp` of the post to the current time.
     *
     * @see replacingTheAuthorByExtbase()
     */
    #[Test]
    public function replacingTheAuthorByCore(): void
    {
        // Given: post 1 "Hello world" has the author 1 Jane Doe
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/Authors.csv');

        // When: person 2 "John Roe" replaces the author of the post
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_post' => [1 => ['author' => '2']]],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the field `author` holds the uid 2
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ReplacedAuthor.csv');
    }

    /**
     * Extbase sets person 2 "John Roe" as the author of post 1 "Hello world" and persists the post with `update()` and
     * `persistAll()`. Extbase writes the uid 2 into the field `author` of post 1 and sets `tstamp` of the post, like
     * DataHandler.
     *
     * @see replacingTheAuthorByCore()
     */
    #[Test]
    public function replacingTheAuthorByExtbase(): void
    {
        // Given: post 1 "Hello world" has the author 1 Jane Doe
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/Authors.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: person 2 replaces the author and is persisted
        $post->setAuthor($this->get(PersonRepository::class)->findByUid(2));
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the field `author` holds the uid 2
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ReplacedAuthor.csv');
    }

    /**
     * The pinned tag of person 1 "Jane Doe" is cleared through DataHandler, as the backend form sends it after the
     * editor removed the tag: the field `pinned_tag` is sent as the empty string. DataHandler writes the empty string
     * into the field `pinned_tag` of person 1 and sets `tstamp` of the person to the current time.
     *
     * @see clearingThePinnedTagByExtbase()
     */
    #[Test]
    public function clearingThePinnedTagByCore(): void
    {
        // Given: person 1 "Jane Doe" has the pinned tag 1 "TYPO3"
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/PinnedTag.csv');

        // When: the pinned tag of the person is cleared
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_person' => [1 => ['pinned_tag' => '']]],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the field `pinned_tag` is empty
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ClearedPinnedTag.csv');
    }

    /**
     * Extbase sets the pinned tag of person 1 "Jane Doe" to null and persists the person with `update()` and
     * `persistAll()`. Extbase sets `tstamp` of the person, but writes the string 0 into the field `pinned_tag` instead
     * of the empty string.
     *
     * @see clearingThePinnedTagByCore()
     */
    #[Test]
    public function clearingThePinnedTagByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes the string 0 into the field `pinned_tag` of person 1 instead of the empty string.');

        // Given: person 1 "Jane Doe" has the pinned tag 1 "TYPO3"
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/PinnedTag.csv');
        $person = $this->get(PersonRepository::class)->findByUid(1);

        // When: the pinned tag is cleared and persisted
        $person->setPinnedTag(null);
        $this->get(PersonRepository::class)->update($person);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the field `pinned_tag` is empty
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ClearedPinnedTag.csv');
    }

    /**
     * Tag 2 "PHP" replaces the pinned tag of person 1 "Jane Doe" through DataHandler, as the backend form sends it: the
     * group field sends the value as table name and uid, `tx_blogexample_domain_model_tag_2`. DataHandler writes the
     * plain uid 2 into the field `pinned_tag` of person 1 and sets `tstamp` of the person to the current time.
     *
     * @see replacingThePinnedTagByExtbase()
     */
    #[Test]
    public function replacingThePinnedTagByCore(): void
    {
        // Given: person 1 "Jane Doe" has the pinned tag 1 "TYPO3"
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/PinnedTag.csv');

        // When: tag 2 "PHP" replaces the pinned tag of the person
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_person' => [1 => ['pinned_tag' => 'tx_blogexample_domain_model_tag_2']]],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the field `pinned_tag` holds the uid 2
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ReplacedPinnedTag.csv');
    }

    /**
     * Extbase sets tag 2 "PHP" as the pinned tag of person 1 "Jane Doe" and persists the person with `update()` and
     * `persistAll()`. Extbase writes the plain uid 2 into the field `pinned_tag` of person 1 and sets `tstamp` of the
     * person, like DataHandler.
     *
     * @see replacingThePinnedTagByCore()
     */
    #[Test]
    public function replacingThePinnedTagByExtbase(): void
    {
        // Given: person 1 "Jane Doe" has the pinned tag 1 "TYPO3"
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/PinnedTag.csv');
        $person = $this->get(PersonRepository::class)->findByUid(1);

        // When: tag 2 "PHP" replaces the pinned tag and is persisted
        $person->setPinnedTag($this->get(PersistenceManager::class)->getObjectByIdentifier(2, Tag::class));
        $this->get(PersonRepository::class)->update($person);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the field `pinned_tag` holds the uid 2
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ReplacedPinnedTag.csv');
    }

    /**
     * Tag 2 "PHP" is set as the main tag of person 1 "Jane Doe" through DataHandler, which has no main tag yet.
     * DataHandler writes a relation row into `tx_blogexample_domain_model_tag_mm` with the tag 2 as `uid_local`, the
     * person 1 as `uid_foreign`, `fieldname` main_tag, `sorting` 0 and `sorting_foreign` 1, writes the number of tags,
     * 1, into the field `main_tag` of person 1 and sets `tstamp` of the person to the current time.
     *
     * @see settingTheMainTagByExtbase()
     */
    #[Test]
    public function settingTheMainTagByCore(): void
    {
        // Given: person 1 "Jane Doe" has no main tag and there is no relation row
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/Person.csv');

        // When: tag 2 "PHP" is set as the main tag of the person
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_person' => [1 => ['main_tag' => '2']]],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and a relation row points to tag 2 and the counter is 1
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/SetMainTag.csv');
    }

    /**
     * Extbase sets tag 2 "PHP" as the main tag of person 1 "Jane Doe", which has no main tag yet, and persists the
     * person with `update()` and `persistAll()`. Extbase treats the field `main_tag` like a plain relation, writes the
     * uid of the tag, 2, into the field `main_tag` of person 1 instead of the number of tags, 1, and sets `tstamp` of
     * the person, but it does not write a relation row for tag 2: the relation table stays empty.
     *
     * @see settingTheMainTagByCore()
     */
    #[Test]
    public function settingTheMainTagByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes the uid 2 of the tag into the field `main_tag` of person 1 instead of the number of tags 1 and does not write a relation row into `tx_blogexample_domain_model_tag_mm`.');

        // Given: person 1 "Jane Doe" has no main tag and there is no relation row
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/Person.csv');
        $jane = $this->get(PersonRepository::class)->findByUid(1);

        // When: tag 2 is set as the main tag and is persisted
        $jane->setMainTag($this->get(PersistenceManager::class)->getObjectByIdentifier(2, Tag::class));
        $this->get(PersonRepository::class)->update($jane);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: a relation row points to tag 2 and the counter is 1
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/SetMainTag.csv');
    }

    /**
     * The main tag of person 1 "Jane Doe" is cleared through DataHandler, as the backend form sends it after the editor
     * selected the empty item: the field `main_tag` is sent as the empty string. DataHandler deletes the relation row
     * of tag 1 "TYPO3" from `tx_blogexample_domain_model_tag_mm`, writes the number of tags, 0, into the field
     * `main_tag` of person 1 and sets `tstamp` of the person to the current time.
     *
     * @see clearingTheMainTagByExtbase()
     */
    #[Test]
    public function clearingTheMainTagByCore(): void
    {
        // Given: person 1 "Jane Doe" has the main tag 1 "TYPO3" through one relation row
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/MainTag.csv');

        // When: the main tag of the person is cleared
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_person' => [1 => ['main_tag' => '']]],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the relation row is gone and the counter is 0
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ClearedMainTag.csv');
    }

    /**
     * Extbase sets the main tag of person 1 "Jane Doe" to null and persists the person with `update()` and
     * `persistAll()`. Extbase writes 0 into the field `main_tag` of person 1 and sets `tstamp` of the person, but it
     * does not delete the relation row of tag 1 "TYPO3": the relation row stays in
     * `tx_blogexample_domain_model_tag_mm`.
     *
     * @see clearingTheMainTagByCore()
     */
    #[Test]
    public function clearingTheMainTagByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes 0 into the field `main_tag` of person 1 but leaves the relation row of tag 1 in `tx_blogexample_domain_model_tag_mm`.');

        // Given: person 1 "Jane Doe" has the main tag 1 "TYPO3" through one relation row
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/MainTag.csv');
        $jane = $this->get(PersonRepository::class)->findByUid(1);

        // When: the main tag is cleared and persisted
        $jane->setMainTag(null);
        $this->get(PersonRepository::class)->update($jane);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the relation row is gone and the counter is 0
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ClearedMainTag.csv');
    }

    /**
     * Tag 2 "PHP" replaces the main tag of person 1 "Jane Doe" through DataHandler, as the backend form sends it: the
     * field `main_tag` is sent as 2. DataHandler deletes the relation row of tag 1 "TYPO3", writes a relation row for
     * tag 2 with the person 1 as `uid_foreign`, `fieldname` main_tag, `sorting` 0 and `sorting_foreign` 1, writes the
     * number of tags, 1, into the field `main_tag` of person 1 and sets `tstamp` of the person to the current time.
     *
     * @see replacingTheMainTagByExtbase()
     */
    #[Test]
    public function replacingTheMainTagByCore(): void
    {
        // Given: person 1 "Jane Doe" has the main tag 1 "TYPO3" through one relation row
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/MainTag.csv');

        // When: tag 2 "PHP" replaces the main tag of the person
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_person' => [1 => ['main_tag' => '2']]],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the relation row points to tag 2 and the counter is 1
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ReplacedMainTag.csv');
    }

    /**
     * Extbase sets tag 2 "PHP" as the main tag of person 1 "Jane Doe" and persists the person with `update()` and
     * `persistAll()`. Extbase writes the uid of the tag, 2, into the field `main_tag` of person 1 instead of the number
     * of tags, but it neither deletes the relation row of tag 1 "TYPO3" nor writes a relation row for tag 2.
     *
     * @see replacingTheMainTagByCore()
     */
    #[Test]
    public function replacingTheMainTagByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes the uid 2 of the tag into the field `main_tag` of person 1 instead of the number of tags 1 and leaves the relation row of tag 1 in place without writing one for tag 2.');

        // Given: person 1 "Jane Doe" has the main tag 1 "TYPO3" through one relation row
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/Given/MainTag.csv');
        $jane = $this->get(PersonRepository::class)->findByUid(1);

        // When: tag 2 replaces the main tag and is persisted
        $jane->setMainTag($this->get(PersistenceManager::class)->getObjectByIdentifier(2, Tag::class));
        $this->get(PersonRepository::class)->update($jane);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the relation row points to tag 2 and the counter is 1
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ToOneRelation/ReplacedMainTag.csv');
    }
}
