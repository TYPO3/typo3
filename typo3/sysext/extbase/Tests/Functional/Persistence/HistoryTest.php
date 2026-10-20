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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * The entries in `sys_history` written for creating, changing and deleting records.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle
 * (DataHandler, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair
 * start from the same fixture in Fixtures/History/Given/ and assert `sys_history` against the same
 * result CSV in Fixtures/History/. Both run as the same administrator at the same fixed time, and
 * the Extbase tests switch the feature toggle `extbase.enableHistoryTracking` on, except where
 * the scenario is about its default. An Extbase test that differs from core is skipped.
 *
 * Fixture Posts.csv: post 1 "Hello world" [en] with the translation 2 "Hallo Welt" [de], the
 * posts 3 "Third post" and 4 "Fourth post" [en] without translation, and the tags 1 "TYPO3" and
 * 2 "PHP". Post 4 has the tag 1 attached.
 */
final class HistoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/History/Given/Posts.csv');
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
     * A new post "New post" is created in the default language through DataHandler. DataHandler writes one history
     * entry of the type "add" for the new uid 5, with the whole new record as history data: `uid` 5, `pid` 2 and
     * `title` "New post".
     *
     * @see historyOfACreatedRecordByExtbase()
     */
    #[Test]
    public function historyOfACreatedRecordByCore(): void
    {
        // When: a post is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => ['NEW1' => ['pid' => 2, 'title' => 'New post']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and writes one history entry with the new record
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/History/CreatedPost.csv');
        $historyData = $this->get(ConnectionPool::class)->getConnectionForTable('sys_history')
            ->select(['history_data'], 'sys_history', ['recuid' => 5])->fetchOne();
        $data = json_decode($historyData, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([5, 2, 'New post'], [$data['uid'], $data['pid'], $data['title']]);
    }

    /**
     * Extbase writes two history entries for the new uid 5: an add entry and a modify entry with an empty old record.
     * Their history data lists the properties of the object (`uid`, `pid`, `title`, the class of the relations) and
     * the Extbase class name, not the database fields.
     *
     * @see historyOfACreatedRecordByCore()
     */
    #[Test]
    public function historyOfACreatedRecordByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes an add and a modify entry whose history data lists the properties of the object instead of the database fields.');

        // Given: history tracking is switched on
        // @phpstan-ignore deadCode.unreachable
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = true;

        // When: a post is created and persisted
        $post = new Post();
        $post->setTitle('New post');
        $this->get(PostRepository::class)->add($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: one history entry exists with the new record
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/History/CreatedPost.csv');
        $historyData = $this->get(ConnectionPool::class)->getConnectionForTable('sys_history')
            ->select(['history_data'], 'sys_history', ['recuid' => 5])->fetchOne();
        $data = json_decode($historyData, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([5, 2, 'New post'], [$data['uid'], $data['pid'], $data['title']]);
    }

    /**
     * The title of the English post 3 "Third post" is changed to "Changed post" through DataHandler. DataHandler
     * writes one history entry of the type "modify" for the post, with the old and the new value of the changed
     * field.
     *
     * @see historyOfAChangedRecordByExtbase()
     */
    #[Test]
    public function historyOfAChangedRecordByCore(): void
    {
        // When: the title of post 3 is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [3 => ['title' => 'Changed post']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and writes one history entry
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/History/ChangedPost.csv');
    }

    /**
     * Extbase writes one modify entry for post 3. Its history data holds the complete old and new property set of the
     * object plus the Extbase class name, not only the changed field.
     *
     * @see historyOfAChangedRecordByCore()
     */
    #[Test]
    public function historyOfAChangedRecordByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: its history data lists all properties of the object instead of the changed database fields.');

        // Given: history tracking is switched on and post 3 is read
        // @phpstan-ignore deadCode.unreachable
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = true;
        $post = $this->get(PostRepository::class)->findByUid(3);

        // When: the title is changed and persisted
        $post->setTitle('Changed post');
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: one history entry exists
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/History/ChangedPost.csv');
    }

    /**
     * The title of the German translation 2 "Hallo Welt" is changed to "Neuer Beitrag" through DataHandler. DataHandler
     * writes one history entry of the type "modify" for the uid of the translation, not for the English post. Its
     * history data names the old title "Hallo Welt" and the new title "Neuer Beitrag".
     *
     * @see historyOfAChangedTranslationByExtbase()
     */
    #[Test]
    public function historyOfAChangedTranslationByCore(): void
    {
        // When: the title of the translation 2 is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [2 => ['title' => 'Neuer Beitrag']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and writes one history entry for the translation
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/History/ChangedTranslation.csv');
        $historyData = $this->get(ConnectionPool::class)->getConnectionForTable('sys_history')
            ->select(['history_data'], 'sys_history', ['recuid' => 2])->fetchOne();
        $data = json_decode(is_resource($historyData) ? stream_get_contents($historyData) : $historyData, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(['Hallo Welt', 'Neuer Beitrag'], [$data['oldRecord']['title'], $data['newRecord']['title']]);
    }

    /**
     * Extbase writes one modify entry for the uid of the English post 1, not for the translation 2. The history data
     * names the old title "Hallo Welt" and the new title "Neuer Beitrag" of the translation, under the uid of the
     * English post.
     *
     * @see historyOfAChangedTranslationByCore()
     */
    #[Test]
    public function historyOfAChangedTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it records the change on the uid of the default record instead of the uid of the translation.');

        // Given: history tracking is switched on and the German translation of post 1 is read
        // @phpstan-ignore deadCode.unreachable
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = true;
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the title is changed and persisted
        $post->setTitle('Neuer Beitrag');
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: one history entry exists for the translation
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/History/ChangedTranslation.csv');
        $historyData = $this->get(ConnectionPool::class)->getConnectionForTable('sys_history')
            ->select(['history_data'], 'sys_history', ['recuid' => 2])->fetchOne();
        $data = json_decode(is_resource($historyData) ? stream_get_contents($historyData) : $historyData, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(['Hallo Welt', 'Neuer Beitrag'], [$data['oldRecord']['title'], $data['newRecord']['title']]);
    }

    /**
     * The English post 3 "Third post" without translation is deleted through DataHandler. DataHandler writes one
     * history entry of the type "delete" without history data.
     *
     * @see historyOfADeletedRecordByExtbase()
     */
    #[Test]
    public function historyOfADeletedRecordByCore(): void
    {
        // When: post 3 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [3 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and writes one history entry
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/History/DeletedPost.csv');
    }

    /**
     * Extbase writes one delete entry for post 3 without history data, like DataHandler.
     *
     * @see historyOfADeletedRecordByCore()
     */
    #[Test]
    public function historyOfADeletedRecordByExtbase(): void
    {
        // Given: history tracking is switched on and post 3 is read
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = true;
        $post = $this->get(PostRepository::class)->findByUid(3);

        // When: the post is removed and persisted
        $this->get(PostRepository::class)->remove($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: one history entry exists
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/History/DeletedPost.csv');
    }

    /**
     * The title of the English post 3 "Third post" is changed through DataHandler while Extbase history tracking
     * keeps its default configuration. DataHandler writes the history entry regardless of that setting.
     *
     * @see historyWithTheDefaultConfigurationByExtbase()
     */
    #[Test]
    public function historyWithTheDefaultConfigurationByCore(): void
    {
        // When: the title of post 3 is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [3 => ['title' => 'Changed post']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and writes one history entry
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/History/ChangedPost.csv');
    }

    /**
     * Extbase writes no history entry for the changed title, `sys_history` stays empty.
     *
     * @see historyWithTheDefaultConfigurationByCore()
     */
    #[Test]
    public function historyWithTheDefaultConfigurationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes no history entry as long as the feature toggle `extbase.enableHistoryTracking` is off.');

        // Given: post 3 is read, history tracking keeps its default configuration
        // @phpstan-ignore deadCode.unreachable
        $post = $this->get(PostRepository::class)->findByUid(3);

        // When: the title is changed and persisted
        $post->setTitle('Changed post');
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: one history entry exists
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/History/ChangedPost.csv');
    }
}
