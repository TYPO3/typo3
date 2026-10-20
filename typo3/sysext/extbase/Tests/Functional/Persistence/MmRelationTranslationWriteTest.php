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
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Writing MM relations through a translation: adding, reordering and removing related records, and saving a
 * translation whose relation rows point to the default records.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle
 * (DataHandler, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair
 * start from the same fixture in Fixtures/MmRelationTranslationWrite/Given/ and assert the database against the same
 * result CSV in Fixtures/MmRelationTranslationWrite/. Both run as the same administrator at the same fixed time. An
 * Extbase test that differs from core is skipped.
 *
 * The relation under test is `related_posts` of the post, an MM relation (`tx_blogexample_post_post_mm`) to other
 * posts. The field is translatable and has no `l10n_mode`, so the German translation of a post has relation rows of
 * its own: `uid_local` is the uid of the translation. Post 1 "Hello world" is the English record, post 2 "Hallo Welt"
 * its German translation. The posts 7 and 8 have no translation. The posts 3 and 5 have the German translations
 * 4 and 6.
 */
final class MmRelationTranslationWriteTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/Given/Base.csv');
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
     * Post 7 "Fourth post" is set as the related post of the German translation 2 "Hallo Welt" through DataHandler, as
     * the backend form sends it: the field `related_posts` of the translation holds the uid list "7". DataHandler
     * writes the relation row with the uid of the translation, `uid_local` 2, writes the number of related posts, 1,
     * into the translation, sets its `tstamp` to the current time and leaves the relation row of the default record 1
     * untouched.
     *
     * @see addingARelatedPostToATranslationByExtbase()
     */
    #[Test]
    public function addingARelatedPostToATranslationByCore(): void
    {
        // Given: post 1 has the related post 8, its German translation 2 has no related posts
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/Given/TranslationWithoutRelatedPosts.csv');

        // When: post 7 is set as the related post of the translation 2
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [2 => ['related_posts' => '7']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the relation row belongs to the translation
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/AddedRelatedPostToTranslation.csv');
    }

    /**
     * Extbase reads post 1 with the language German, adds post 7 "Fourth post" as related post and persists the post
     * with `update()` and `persistAll()`. Extbase writes the new relation row with the uid of the translation,
     * `uid_local` 2, and the number of related posts, 1, into the translation, like DataHandler.
     *
     * @see addingARelatedPostToATranslationByCore()
     */
    #[Test]
    public function addingARelatedPostToATranslationByExtbase(): void
    {
        // Given: post 1 has the related post 8, its German translation 2 has no related posts
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/Given/TranslationWithoutRelatedPosts.csv');
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: post 7 is added as related post of the translation and persisted
        $post->addRelatedPost($this->get(PostRepository::class)->findByUid(7));
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the relation row belongs to the translation
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/AddedRelatedPostToTranslation.csv');
    }

    /**
     * The related posts of the German translation 2 "Hallo Welt" are sent in the order 8, 7 through DataHandler, as the
     * backend form sends them after the editor moved a related post: the field `related_posts` of the translation holds
     * the uid list "8,7". DataHandler rewrites the `sorting` of the relation rows of the translation, `uid_local` 2,
     * and leaves the relation rows of the default record 1 untouched.
     *
     * @see reorderingRelatedPostsOfATranslationByExtbase()
     */
    #[Test]
    public function reorderingRelatedPostsOfATranslationByCore(): void
    {
        // Given: post 1 and its German translation 2 each have the related posts 7 and 8
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/Given/TranslationWithRelatedPosts.csv');

        // When: the related posts of the translation 2 are sent in the order 8, 7
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [2 => ['related_posts' => '8,7']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and only the relation rows of the translation change
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/ReorderedRelatedPostsOfTranslation.csv');
    }

    /**
     * Extbase reads post 1 with the language German, detaches both related posts, attaches them again in the order 8, 7
     * and persists the post with `update()` and `persistAll()`. Extbase swaps the `sorting` of the relation rows of the
     * default record 1, `uid_local` 1, and leaves the relation rows of the translation 2 unchanged, because it updates
     * the relation rows with the uid of the default record.
     *
     * @see reorderingRelatedPostsOfATranslationByCore()
     */
    #[Test]
    public function reorderingRelatedPostsOfATranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it rewrites the `sorting` of the relation rows of the default record instead of those of the translation.');

        // Given: post 1 and its German translation 2 each have the related posts 7 and 8
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/Given/TranslationWithRelatedPosts.csv');
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the related posts are put in the order 8, 7 and persisted
        $relatedPosts = $post->getRelatedPosts()->toArray();
        foreach ($relatedPosts as $relatedPost) {
            $post->getRelatedPosts()->detach($relatedPost);
        }
        foreach (array_reverse($relatedPosts) as $relatedPost) {
            $post->addRelatedPost($relatedPost);
        }
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: only the relation rows of the translation change
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/ReorderedRelatedPostsOfTranslation.csv');
    }

    /**
     * Only post 8 is sent as related post of the German translation 2 "Hallo Welt" through DataHandler, as the backend
     * form sends the remaining related post after the editor removed post 7. DataHandler deletes the relation row of
     * the translation for post 7, renumbers the remaining row of the translation to the `sorting` 1, writes the number
     * of related posts, 1, into the translation and leaves the relation rows of the default record 1 untouched.
     *
     * @see removingARelatedPostFromATranslationByExtbase()
     */
    #[Test]
    public function removingARelatedPostFromATranslationByCore(): void
    {
        // Given: post 1 and its German translation 2 each have the related posts 7 and 8
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/Given/TranslationWithRelatedPosts.csv');

        // When: only post 8 is sent for the translation 2
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [2 => ['related_posts' => '8']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and only the relation row of the translation is gone
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/RemovedRelatedPostFromTranslation.csv');
    }

    /**
     * Extbase reads post 1 with the language German, detaches post 7 and persists the post with `update()` and
     * `persistAll()`. Extbase deletes the relation row of the default record 1 for post 7 and leaves both relation rows
     * of the translation 2 in place; the field `related_posts` of the translation stays 2, because Extbase deletes the
     * relation rows with the uid of the default record.
     *
     * @see removingARelatedPostFromATranslationByCore()
     */
    #[Test]
    public function removingARelatedPostFromATranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it deletes the relation row of the default record instead of the row of the translation and leaves the translation with its two related posts.');

        // Given: post 1 and its German translation 2 each have the related posts 7 and 8
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/Given/TranslationWithRelatedPosts.csv');
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: post 7 is detached from the translation and persisted
        $post->getRelatedPosts()->detach($this->get(PostRepository::class)->findByUid(7));
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: only the relation row of the translation is gone
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/RemovedRelatedPostFromTranslation.csv');
    }

    /**
     * The German translation 2 "Hallo Welt" of post 1 has the related posts 4 and 6, the German translations of the
     * posts 3 and 5 that post 1 has itself. The same uid list "4,6" is sent for the translation 2 through DataHandler,
     * as the backend form sends the field of the translation. DataHandler leaves the relation rows of the translation
     * (`uid_local` 2) and of the default record 1 as they are; the only change is the `tstamp` of the translation 2,
     * which becomes the current time.
     *
     * @see savingATranslationWithTranslatedRelatedPostsUnchangedByExtbase()
     */
    #[Test]
    public function savingATranslationWithTranslatedRelatedPostsUnchangedByCore(): void
    {
        // Given: post 1 has the related posts 3 and 5, its German translation 2 has their translations 4 and 6
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/Given/TranslationWithTranslatedRelatedPosts.csv');

        // When: the related posts 4 and 6 are sent again for the translation 2
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [2 => ['related_posts' => '4,6']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the relation rows are unchanged
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/UnchangedTranslatedRelatedPosts.csv');
    }

    /**
     * Extbase reads the German translation 2 itself, without respecting the language, looks up the posts 4 and 6 by
     * their uids, sets them as the related posts and persists the post with `update()` and `persistAll()`. Extbase
     * works with the default uids of the related posts: it writes rows of the translation 2 to the default posts 3 and
     * 5 next to its rows to the posts 4 and 6. The translation 2 ends with four relation rows and the number 4 in its
     * field `related_posts`; the relation rows of the default record 1 stay as they are.
     *
     * @see savingATranslationWithTranslatedRelatedPostsUnchangedByCore()
     */
    #[Test]
    public function savingATranslationWithTranslatedRelatedPostsUnchangedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes the relation rows of the translation to the default posts next to its own rows to the translated posts.');

        // Given: post 1 has the related posts 3 and 5, its German translation 2 has their translations 4 and 6
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/Given/TranslationWithTranslatedRelatedPosts.csv');
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $post = $query->matching($query->equals('uid', 2))->execute()->getFirst();

        // When: the related posts of the translation are set to the posts 4 and 6 again and persisted
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setRespectSysLanguage(false);
        /** @var ObjectStorage<Post> $relatedPosts */
        $relatedPosts = new ObjectStorage();
        foreach ($query->matching($query->in('uid', [4, 6]))->execute() as $relatedPost) {
            $relatedPosts->attach($relatedPost);
        }
        $post->setRelatedPosts($relatedPosts);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the relation rows are unchanged
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/UnchangedTranslatedRelatedPosts.csv');
    }

    /**
     * The relation rows of the German translation 2 "Hallo Welt" point to the default posts 3 and 5, not to their
     * German translations 4 and 6, which exist. The uid list "3,5" is sent for the translation 2 through DataHandler.
     * DataHandler leaves the relation rows of the translation (`uid_local` 2) and of the default record 1 as they are;
     * the only change is the `tstamp` of the translation 2, which becomes the current time.
     *
     * @see savingATranslationWhoseRowsPointToTheDefaultPostsByExtbase()
     */
    #[Test]
    public function savingATranslationWhoseRowsPointToTheDefaultPostsByCore(): void
    {
        // Given: the relation rows of the German translation 2 point to the default posts 3 and 5
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/Given/TranslationWithDefaultRelatedPosts.csv');

        // When: the related posts 3 and 5 are sent again for the translation 2
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [2 => ['related_posts' => '3,5']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the relation rows are unchanged
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/UnchangedDefaultRelatedPosts.csv');
    }

    /**
     * Extbase reads the German translation 2 itself, without respecting the language, looks up the default posts 3 and
     * 5 by their uids, sets them as the related posts and persists the post with `update()` and `persistAll()`. Extbase
     * tries to write the rows of the translation 2 to the posts 3 and 5 again, although they already exist: the
     * database throws a UNIQUE constraint violation, which Extbase rethrows as `SqlErrorException`.
     *
     * @see savingATranslationWhoseRowsPointToTheDefaultPostsByCore()
     */
    #[Test]
    public function savingATranslationWhoseRowsPointToTheDefaultPostsByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it throws a UNIQUE constraint violation, because it writes relation rows of the translation that already exist.');

        // Given: the relation rows of the German translation 2 point to the default posts 3 and 5
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/Given/TranslationWithDefaultRelatedPosts.csv');
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $post = $query->matching($query->equals('uid', 2))->execute()->getFirst();

        // When: the related posts of the translation are set to the posts 3 and 5 again and persisted
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setRespectSysLanguage(false);
        /** @var ObjectStorage<Post> $relatedPosts */
        $relatedPosts = new ObjectStorage();
        foreach ($query->matching($query->in('uid', [3, 5]))->execute() as $relatedPost) {
            $relatedPosts->attach($relatedPost);
        }
        $post->setRelatedPosts($relatedPosts);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the relation rows are unchanged
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MmRelationTranslationWrite/UnchangedDefaultRelatedPosts.csv');
    }
}
