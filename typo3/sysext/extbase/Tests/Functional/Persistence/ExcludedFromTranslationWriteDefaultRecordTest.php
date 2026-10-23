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
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Comment;
use TYPO3Tests\BlogExample\Domain\Model\Info;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\PersonRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Writes through the default record: what reaches the translations, in the database and in the
 * history, also inside a workspace.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle
 * (DataHandler, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair
 * assert the database against the same result CSV below
 * Fixtures/ExcludedFromTranslation/WriteDefaultRecord/; the history scenario compares the recorded
 * author changes instead. The oracle tests describe correct behaviour and must be green; an Extbase
 * test that differs from core is skipped with a reference to the issue.
 *
 * Fixture WriteDefaultRecord.csv: post 1 [en] with 2 [de] and 3 [fr] (tags and info), post 4 [en]
 * with 5 [de] and 6 [fr] (no children, for adding info), post 7 [en] with 8 [de] (comments).
 */
final class ExcludedFromTranslationWriteDefaultRecordTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
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
     * The author of the English post is changed from Anna to Ben through DataHandler. The German
     * and French translations take over Ben as well; DataHandler synchronizes excluded fields into
     * every translation.
     *
     * @see authorChangedOnTheDefaultRecordIsWrittenToEveryTranslationByExtbase()
     */
    #[Test]
    public function authorChangedOnTheDefaultRecordIsWrittenToEveryTranslationByCore(): void
    {
        // When: the author of the English post is set to Ben (uid 2)
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['author' => 2]]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the translations have Ben as well
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/AuthorChanged.csv');
    }

    /**
     * Extbase writes the new author to the English post only; the translations keep Anna.
     *
     * @see authorChangedOnTheDefaultRecordIsWrittenToEveryTranslationByCore()
     */
    #[Test]
    public function authorChangedOnTheDefaultRecordIsWrittenToEveryTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: the English post is read with overlays mixed, together with the person Ben
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);
        $ben = $this->get(PersonRepository::class)->findOneBy(['firstname' => 'Ben']);

        // When: the author of the English post is set to Ben and persisted
        $post->setAuthor($ben);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the translations have Ben as well
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/AuthorChanged.csv');
    }

    /**
     * The date of the English post is changed from 2026-01-15 to 2026-02-20 through DataHandler.
     * The German and French translations take over the new date.
     *
     * @see dateChangedOnTheDefaultRecordIsWrittenToEveryTranslationByExtbase()
     */
    #[Test]
    public function dateChangedOnTheDefaultRecordIsWrittenToEveryTranslationByCore(): void
    {
        // When: the date of the English post is set to 2026-02-20
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_post' => [1 => ['date' => new \DateTimeImmutable('2026-02-20 12:00:00', new \DateTimeZone('UTC'))->getTimestamp()]]],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the translations have the new date as well
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/DateChanged.csv');
    }

    /**
     * Extbase writes the new date to the English post only; the translations keep 2026-01-15.
     *
     * @see dateChangedOnTheDefaultRecordIsWrittenToEveryTranslationByCore()
     */
    #[Test]
    public function dateChangedOnTheDefaultRecordIsWrittenToEveryTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: the English post is read with overlays mixed
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the date of the English post is set to 2026-02-20 and persisted
        $post->setDate(new \DateTime('2026-02-20 12:00:00', new \DateTimeZone('UTC')));
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the translations have the new date as well
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/DateChanged.csv');
    }

    /**
     * The title of the English post is changed through DataHandler. The title is not excluded from
     * translation, so the German and French translations keep their own titles.
     *
     * @see translatableValueChangedOnTheDefaultRecordLeavesTheTranslationsUntouchedByExtbase()
     */
    #[Test]
    public function translatableValueChangedOnTheDefaultRecordLeavesTheTranslationsUntouchedByCore(): void
    {
        // When: the title of the English post is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['title' => 'Hello everyone']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the translations keep their titles
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/TitleChanged.csv');
    }

    /**
     * @see translatableValueChangedOnTheDefaultRecordLeavesTheTranslationsUntouchedByCore()
     */
    #[Test]
    public function translatableValueChangedOnTheDefaultRecordLeavesTheTranslationsUntouchedByExtbase(): void
    {
        // Given: the English post is read with overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the title of the English post is changed and persisted
        $post->setTitle('Hello everyone');
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the translations keep their titles
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/TitleChanged.csv');
    }

    /**
     * The English post "Third post" has the comments "Nice read" and "Well written", and its German
     * translation owns a copy of each. A third comment "Thanks" is added to the English post through
     * DataHandler, and DataHandler creates a copy of it for the German translation as well.
     *
     * @see commentAddedOnTheDefaultRecordIsCopiedToTheTranslationByExtbase()
     */
    #[Test]
    public function commentAddedOnTheDefaultRecordIsCopiedToTheTranslationByCore(): void
    {
        // When: the comment "Thanks" is added to the English post
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tx_blogexample_domain_model_post' => [7 => ['comments' => '1,2,NEW1']],
                'tx_blogexample_domain_model_comment' => [
                    'NEW1' => ['pid' => 2, 'author' => 'Max', 'email' => 'max@example.com', 'content' => 'Thanks'],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the German translation has a copy of the comment
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/CommentAdded.csv');
    }

    /**
     * Extbase adds the comment to the English post only; the German translation gets no copy.
     *
     * @see commentAddedOnTheDefaultRecordIsCopiedToTheTranslationByCore()
     */
    #[Test]
    public function commentAddedOnTheDefaultRecordIsCopiedToTheTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: the English post is read with overlays mixed
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(7);

        // When: the comment "Thanks" is added to the English post and persisted
        $comment = new Comment();
        $comment->setAuthor('Max');
        $comment->setEmail('max@example.com');
        $comment->setContent('Thanks');
        $post->addComment($comment);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the German translation has a copy of the comment
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/CommentAdded.csv');
    }

    /**
     * The English post "Second post" has no info, neither have its German and French translations.
     * The info "More info" is added to the English post through DataHandler, and DataHandler creates
     * a copy of it for each translation.
     *
     * @see infoAddedOnTheDefaultRecordIsCopiedToEveryTranslationByExtbase()
     */
    #[Test]
    public function infoAddedOnTheDefaultRecordIsCopiedToEveryTranslationByCore(): void
    {
        // When: the info "More info" is added to the English post
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tx_blogexample_domain_model_post' => [4 => ['additional_info' => 'NEW1']],
                'tx_blogexample_domain_model_info' => ['NEW1' => ['pid' => 2, 'name' => 'More info']],
            ],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and both translations have a copy of the info
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/InfoAdded.csv');
    }

    /**
     * Extbase creates the info for the English post only; the translations get no copy.
     *
     * @see infoAddedOnTheDefaultRecordIsCopiedToEveryTranslationByCore()
     */
    #[Test]
    public function infoAddedOnTheDefaultRecordIsCopiedToEveryTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: the English post is read with overlays mixed
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(4);

        // When: the info "More info" is added to the English post and persisted
        $info = new Info();
        $info->setName('More info');
        $post->setAdditionalInfo($info);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: both translations have a copy of the info
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/InfoAdded.csv');
    }

    /**
     * The comment "Nice read" is deleted from the English post "Third post" through DataHandler.
     * The comment table is not language aware, but DataHandler finds the copy of the German
     * translation through its origUid and deletes it together with the default comment.
     *
     * @see commentRemovedFromTheDefaultRecordStaysInTheTranslationByExtbase()
     */
    #[Test]
    public function commentRemovedFromTheDefaultRecordStaysInTheTranslationByCore(): void
    {
        // When: the comment "Nice read" is deleted from the English post
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_post' => [7 => ['comments' => '2']]],
            ['tx_blogexample_domain_model_comment' => [1 => ['delete' => 1]]]
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the copy of the German translation is deleted as well
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/CommentRemovedByCore.csv');
    }

    /**
     * @see commentRemovedFromTheDefaultRecordStaysInTheTranslationByCore()
     */
    #[Test]
    public function commentRemovedFromTheDefaultRecordStaysInTheTranslationByExtbase(): void
    {
        // Given: the English post is read with overlays mixed, together with the comment "Nice read"
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(7);
        $niceRead = array_find($post->getComments()->toArray(), static fn(Comment $comment): bool => $comment->getContent() === 'Nice read');

        // When: the comment "Nice read" is removed from the English post and persisted
        $post->removeComment($niceRead);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the German translation keeps both comments
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/CommentRemoved.csv');
    }

    /**
     * The English post, the German and the French translation each have an info. The info of the
     * English post is deleted through DataHandler, which deletes the infos of both translations
     * with it.
     *
     * @see infoRemovedFromTheDefaultRecordIsRemovedFromEveryTranslationByExtbase()
     */
    #[Test]
    public function infoRemovedFromTheDefaultRecordIsRemovedFromEveryTranslationByCore(): void
    {
        // When: the info of the English post is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_post' => [1 => ['additional_info' => '']]],
            ['tx_blogexample_domain_model_info' => [1 => ['delete' => 1]]]
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the infos of both translations are deleted as well
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/InfoRemoved.csv');
    }

    /**
     * Extbase deletes the info of the English post only; the translations keep their infos.
     *
     * @see infoRemovedFromTheDefaultRecordIsRemovedFromEveryTranslationByCore()
     */
    #[Test]
    public function infoRemovedFromTheDefaultRecordIsRemovedFromEveryTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: the English post is read with overlays mixed
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the info of the English post is removed and persisted
        $this->get(PersistenceManager::class)->remove($post->getAdditionalInfo());
        $post->_setProperty('additionalInfo', null);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the infos of both translations are deleted as well
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/InfoRemoved.csv');
    }

    /**
     * The English post has the tags PHP and TYPO3, and so have both translations. The tag Fluid is
     * added to the English post through DataHandler, and the German and French translations get it
     * as well.
     *
     * @see tagAddedOnTheDefaultRecordIsWrittenToEveryTranslationByExtbase()
     */
    #[Test]
    public function tagAddedOnTheDefaultRecordIsWrittenToEveryTranslationByCore(): void
    {
        // When: the tag Fluid (uid 3) is added to the English post
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['tags' => '1,2,3']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and both translations have the tag as well
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/TagAdded.csv');
    }

    /**
     * Extbase adds the tag to the English post only; the translations keep PHP and TYPO3.
     *
     * @see tagAddedOnTheDefaultRecordIsWrittenToEveryTranslationByCore()
     */
    #[Test]
    public function tagAddedOnTheDefaultRecordIsWrittenToEveryTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: the English post is read with overlays mixed, together with the tag Fluid
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);
        $query = $this->get(PersistenceManager::class)->createQueryForType(Tag::class);
        $fluid = $query->matching($query->equals('name', 'Fluid'))->execute()->getFirst();

        // When: the tag Fluid is added to the English post and persisted
        $post->addTag($fluid);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: both translations have the tag as well
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/TagAdded.csv');
    }

    /**
     * The English post has the tags PHP and TYPO3, and so have both translations. The tag TYPO3 is
     * removed from the English post through DataHandler, and the German and French translations
     * lose it as well.
     *
     * @see tagRemovedFromTheDefaultRecordIsRemovedFromEveryTranslationByExtbase()
     */
    #[Test]
    public function tagRemovedFromTheDefaultRecordIsRemovedFromEveryTranslationByCore(): void
    {
        // When: the tag TYPO3 (uid 1) is removed from the English post
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['tags' => '2']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and both translations lose the tag as well
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/TagRemoved.csv');
    }

    /**
     * Extbase removes the tag from the English post only; the translations keep TYPO3.
     *
     * @see tagRemovedFromTheDefaultRecordIsRemovedFromEveryTranslationByCore()
     */
    #[Test]
    public function tagRemovedFromTheDefaultRecordIsRemovedFromEveryTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: the English post is read with overlays mixed, together with the tag TYPO3
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);
        $typo3 = array_find($post->getTags()->toArray(), static fn(Tag $tag): bool => $tag->getName() === 'TYPO3');

        // When: the tag TYPO3 is removed from the English post and persisted
        $post->removeTag($typo3);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: both translations lose the tag as well
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/TagRemoved.csv');
    }

    /**
     * The author of the English post is changed from Anna (1) to Ben (2) through DataHandler. The
     * history records the change once for every changed record: the English post and both
     * translations, each from Anna to Ben.
     *
     * @see authorChangedOnTheDefaultRecordIsRecordedInTheHistoryOfEveryChangedRecordByExtbase()
     */
    #[Test]
    public function authorChangedOnTheDefaultRecordIsRecordedInTheHistoryOfEveryChangedRecordByCore(): void
    {
        // When: the author of the English post is set to Ben
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['author' => 2]]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: the history has one change from Anna to Ben for the English post and for each translation
        self::assertSame([], $dataHandler->errorLog);
        $entries = $this->get(ConnectionPool::class)->getConnectionForTable('sys_history')->select(
            ['recuid', 'history_data'],
            'sys_history',
            ['tablename' => 'tx_blogexample_domain_model_post', 'actiontype' => 2],
            [],
            ['recuid' => 'ASC', 'uid' => 'ASC']
        )->fetchAllAssociative();
        $changes = [];
        foreach ($entries as $entry) {
            $data = json_decode($entry['history_data'], true, 512, JSON_THROW_ON_ERROR);
            $changes[] = [(int)$entry['recuid'], (int)($data['oldRecord']['author'] ?? 0), (int)($data['newRecord']['author'] ?? 0)];
        }
        self::assertSame([[1, 1, 2], [2, 1, 2], [3, 1, 2]], $changes);
    }

    /**
     * Extbase records the change of the English post only.
     *
     * @see authorChangedOnTheDefaultRecordIsRecordedInTheHistoryOfEveryChangedRecordByCore()
     */
    #[Test]
    public function authorChangedOnTheDefaultRecordIsRecordedInTheHistoryOfEveryChangedRecordByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: history tracking is enabled and the English post is read with overlays mixed, together with the person Ben
        // @phpstan-ignore deadCode.unreachable
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = true;
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);
        $ben = $this->get(PersonRepository::class)->findOneBy(['firstname' => 'Ben']);

        // When: the author of the English post is set to Ben and persisted
        $post->setAuthor($ben);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the history has one change from Anna to Ben for the English post and for each translation
        $entries = $this->get(ConnectionPool::class)->getConnectionForTable('sys_history')->select(
            ['recuid', 'history_data'],
            'sys_history',
            ['tablename' => 'tx_blogexample_domain_model_post', 'actiontype' => 2],
            [],
            ['recuid' => 'ASC', 'uid' => 'ASC']
        )->fetchAllAssociative();
        $changes = [];
        foreach ($entries as $entry) {
            $data = json_decode($entry['history_data'], true, 512, JSON_THROW_ON_ERROR);
            $changes[] = [(int)$entry['recuid'], (int)($data['oldRecord']['author'] ?? 0), (int)($data['newRecord']['author'] ?? 0)];
        }
        self::assertSame([[1, 1, 2], [2, 1, 2], [3, 1, 2]], $changes);
    }

    /**
     * In workspace 1 the author of the English post is changed from Anna to Ben through DataHandler.
     * The live posts stay untouched; DataHandler creates a workspace version of the English post and
     * of both translations, each with author Ben.
     *
     * @see authorChangedOnTheDefaultRecordInAWorkspaceIsWrittenToVersionsOfEveryTranslationByExtbase()
     */
    #[Test]
    public function authorChangedOnTheDefaultRecordInAWorkspaceIsWrittenToVersionsOfEveryTranslationByCore(): void
    {
        // Given: the backend user works in workspace 1
        $GLOBALS['BE_USER']->workspace = 1;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));

        // When: the author of the English post is set to Ben
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['author' => 2]]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the workspace has versions of all three posts with Ben
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/AuthorChangedInWorkspace.csv');
    }

    /**
     * Extbase ignores the workspace: it writes Ben into the live English post, creates no versions
     * and leaves the translations at Anna.
     *
     * @see authorChangedOnTheDefaultRecordInAWorkspaceIsWrittenToVersionsOfEveryTranslationByCore()
     */
    #[Test]
    public function authorChangedOnTheDefaultRecordInAWorkspaceIsWrittenToVersionsOfEveryTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: the backend user works in workspace 1 and the English post is read with overlays mixed, together with the person Ben
        // @phpstan-ignore deadCode.unreachable
        $GLOBALS['BE_USER']->workspace = 1;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);
        $ben = $this->get(PersonRepository::class)->findOneBy(['firstname' => 'Ben']);

        // When: the author of the English post is set to Ben and persisted
        $post->setAuthor($ben);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the workspace has versions of all three posts with Ben
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultRecord/AuthorChangedInWorkspace.csv');
    }
}
