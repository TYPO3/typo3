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
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * The reference index (`sys_refindex`) after attaching tags and related posts to a post and detaching them again.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle
 * (DataHandler, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair
 * start from the same fixture in Fixtures/ReferenceIndex/Given/ and assert `sys_refindex` against
 * the same result CSV in Fixtures/ReferenceIndex/. Both run as the same administrator at the same
 * fixed time, and the Extbase tests switch the feature toggle `extbase.enableHistoryTracking` on.
 * An Extbase test that differs from core is skipped.
 *
 * Fixture Posts.csv: post 1 "Hello world" [en] with the translation 2 "Hallo Welt" [de], the
 * posts 3 "Third post" and 4 "Fourth post" [en] without translation, and the tags 1 "TYPO3" and
 * 2 "PHP". Post 4 has the tag 1 attached. The MM relation `tags` of a post is the opposite side
 * of `items` of a tag, so the reference index holds its rows on the tag. The MM relation `related_posts`
 * is defined on the post itself, so the index holds its rows on the post; the scenarios for it import
 * Given/RelatedPosts.csv, which adds post 5 "Fifth post" [en] with the related post 3. The fixture import
 * does not fill `sys_refindex`, so scenarios that start from existing index rows build them first.
 */
final class ReferenceIndexTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/Given/Posts.csv');
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

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = false;
        parent::tearDown();
    }

    /**
     * The tags 1 "TYPO3" and 2 "PHP" are attached to post 3 "Third post" through DataHandler. The MM relation `tags`
     * of a post is the opposite side of `items` of a tag, so the reference index has no row for the field `tags` of the
     * post. DataHandler also updates the reference index of the tags and lists post 3 for the field `items` of both
     * tags, next to post 4 for tag 1, with the position of the post in `sorting`.
     *
     * @see referenceIndexOfTheOppositeSideAfterAddingTagsByExtbase()
     */
    #[Test]
    public function referenceIndexOfTheOppositeSideAfterAddingTagsByCore(): void
    {
        // When: the tags 1 and 2 are attached to post 3
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [3 => ['tags' => '1,2']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the reference index lists post 3 for both tags
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/OppositeSideAddedTags.csv');
    }

    /**
     * Extbase updates only the reference index of post 3, as `Backend::updateObject()` calls
     * `ReferenceIndex::updateRefIndexTable()` for the changed object alone. Post 3 has no rows, so the rows of the
     * tags for `items` are not written.
     *
     * @see referenceIndexOfTheOppositeSideAfterAddingTagsByCore()
     */
    #[Test]
    public function referenceIndexOfTheOppositeSideAfterAddingTagsByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it updates only the reference index of the changed post, not that of its tags, so the rows for `items` are not written.');

        // Given: history tracking is switched on, post 3 and the tags 1 and 2 are read
        // @phpstan-ignore deadCode.unreachable
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = true;
        $post = $this->get(PostRepository::class)->findByUid(3);
        $persistenceManager = $this->get(PersistenceManager::class);

        // When: both tags are attached and persisted
        $post->addTag($persistenceManager->getObjectByIdentifier(1, Tag::class));
        $post->addTag($persistenceManager->getObjectByIdentifier(2, Tag::class));
        $this->get(PostRepository::class)->update($post);
        $persistenceManager->persistAll();

        // Then: the reference index lists post 3 for both tags
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/OppositeSideAddedTags.csv');
    }

    /**
     * The only tag of post 4 "Fourth post" is detached through DataHandler. The reference index of tag 1 "TYPO3" was
     * built beforehand and lists post 4 for the field `items`. DataHandler also updates the reference index of the
     * tag, so it is empty afterwards.
     *
     * @see referenceIndexOfTheOppositeSideAfterRemovingTheLastTagByExtbase()
     */
    #[Test]
    public function referenceIndexOfTheOppositeSideAfterRemovingTheLastTagByCore(): void
    {
        // Given: the reference index of the tag 1 "TYPO3" is built, it lists post 4 for the field `items`
        $this->get(ReferenceIndex::class)->updateRefIndexTable('tx_blogexample_domain_model_tag', 1);

        // When: the tags of post 4 are set to none
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [4 => ['tags' => '']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the reference index is empty
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/OppositeSideRemovedTag.csv');
    }

    /**
     * Extbase updates only the reference index of post 4, as `Backend::updateObject()` calls
     * `ReferenceIndex::updateRefIndexTable()` for the changed object alone. Post 4 has no rows, so the row of tag 1
     * for post 4 stays in the reference index.
     *
     * @see referenceIndexOfTheOppositeSideAfterRemovingTheLastTagByCore()
     */
    #[Test]
    public function referenceIndexOfTheOppositeSideAfterRemovingTheLastTagByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it updates only the reference index of the changed post, not that of its tag, so the row for `items` stays.');

        // Given: history tracking is switched on, the reference index of tag 1 is built and post 4 is read
        // @phpstan-ignore deadCode.unreachable
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = true;
        $this->get(ReferenceIndex::class)->updateRefIndexTable('tx_blogexample_domain_model_tag', 1);
        $post = $this->get(PostRepository::class)->findByUid(4);

        // When: its tag is detached and persisted
        $post->removeTag($post->getTags()->toArray()[0]);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the reference index is empty
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/OppositeSideRemovedTag.csv');
    }

    /**
     * The posts 4 "Fourth post" and 5 "Fifth post" are made related posts of post 3 "Third post" through DataHandler.
     * `related_posts` is defined on the post itself and has no opposite field, so the reference index holds one row
     * per related post for the field `related_posts` of post 3, with the position of the related post in `sorting`.
     *
     * @see referenceIndexAfterAddingRelatedPostsByExtbase()
     */
    #[Test]
    public function referenceIndexAfterAddingRelatedPostsByCore(): void
    {
        // Given: post 5 exists
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/Given/RelatedPosts.csv');

        // When: the posts 4 and 5 are set as related posts of post 3
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [3 => ['related_posts' => '4,5']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the reference index has one row per related post
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/AddedRelatedPosts.csv');
    }

    /**
     * Extbase updates the reference index to the same two rows for post 3 as DataHandler, as the relation is defined
     * on the changed post.
     *
     * @see referenceIndexAfterAddingRelatedPostsByCore()
     */
    #[Test]
    public function referenceIndexAfterAddingRelatedPostsByExtbase(): void
    {
        // Given: history tracking is switched on, post 5 exists, the posts 3, 4 and 5 are read
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = true;
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/Given/RelatedPosts.csv');
        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(3);

        // When: the posts 4 and 5 are added as related posts and persisted
        $post->addRelatedPost($postRepository->findByUid(4));
        $post->addRelatedPost($postRepository->findByUid(5));
        $postRepository->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the reference index has one row per related post
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/AddedRelatedPosts.csv');
    }

    /**
     * The only related post of post 5 "Fifth post", post 3 "Third post", is removed through DataHandler. The reference
     * index of post 5 was built beforehand and lists post 3 for the field `related_posts`. It is empty afterwards.
     *
     * @see referenceIndexAfterRemovingTheLastRelatedPostByExtbase()
     */
    #[Test]
    public function referenceIndexAfterRemovingTheLastRelatedPostByCore(): void
    {
        // Given: post 5 exists and its reference index is built
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/Given/RelatedPosts.csv');
        $this->get(ReferenceIndex::class)->updateRefIndexTable('tx_blogexample_domain_model_post', 5);

        // When: the related posts of post 5 are set to none
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [5 => ['related_posts' => '']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the reference index is empty
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/RemovedRelatedPost.csv');
    }

    /**
     * Extbase removes the reference index row of post 5, the index is empty afterwards as with DataHandler.
     *
     * @see referenceIndexAfterRemovingTheLastRelatedPostByCore()
     */
    #[Test]
    public function referenceIndexAfterRemovingTheLastRelatedPostByExtbase(): void
    {
        // Given: history tracking is switched on, post 5 exists, its reference index is built and it is read
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = true;
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/Given/RelatedPosts.csv');
        $this->get(ReferenceIndex::class)->updateRefIndexTable('tx_blogexample_domain_model_post', 5);
        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(5);

        // When: its related post is removed and persisted
        $post->getRelatedPosts()->detach($post->getRelatedPosts()->toArray()[0]);
        $postRepository->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the reference index is empty
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/RemovedRelatedPost.csv');
    }
}
