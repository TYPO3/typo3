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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * The entries in `sys_log` written for creating, changing and deleting records.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle
 * (DataHandler, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair
 * start from the same fixture in Fixtures/Log/Given/ and assert `sys_log` against the same result
 * CSV in Fixtures/Log/. Both run as the same administrator at the same fixed time, and the Extbase
 * tests switch the feature toggle `extbase.enableHistoryTracking` on. An Extbase test that differs
 * from core is skipped.
 *
 * Fixture Posts.csv: post 1 "Hello world" [en] with the translation 2 "Hallo Welt" [de], the
 * posts 3 "Third post" and 4 "Fourth post" [en] without translation, and the tags 1 "TYPO3" and
 * 2 "PHP". Post 4 has the tag 1 attached.
 */
final class LogTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Log/Given/Posts.csv');
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
     * A new post "New post" is created in the default language through DataHandler. DataHandler writes one `sys_log`
     * entry for the new uid 5, with the action "insert" (1), the message "Record {table}:{uid} was inserted on page
     * {pid}" and the page of the post in the log data.
     *
     * @see logOfACreatedRecordByExtbase()
     */
    #[Test]
    public function logOfACreatedRecordByCore(): void
    {
        // When: a post is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => ['NEW1' => ['pid' => 2, 'title' => 'New post']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and writes one log entry
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Log/CreatedPost.csv');
    }

    /**
     * Extbase creates the post but writes no `sys_log` entry.
     *
     * @see logOfACreatedRecordByCore()
     */
    #[Test]
    public function logOfACreatedRecordByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes no `sys_log` entry.');

        // Given: history tracking is switched on
        // @phpstan-ignore deadCode.unreachable
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = true;

        // When: a post is created and persisted
        $post = new Post();
        $post->setTitle('New post');
        $this->get(PostRepository::class)->add($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: one log entry exists
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Log/CreatedPost.csv');
    }

    /**
     * The title of the English post 3 "Third post" is changed to "Changed post" through DataHandler. DataHandler
     * writes one `sys_log` entry with the action "update" (2) and the message "Record {table}:{uid} was updated".
     *
     * @see logOfAChangedRecordByExtbase()
     */
    #[Test]
    public function logOfAChangedRecordByCore(): void
    {
        // When: the title of post 3 is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [3 => ['title' => 'Changed post']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and writes one log entry
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Log/ChangedPost.csv');
    }

    /**
     * Extbase changes the post but writes no `sys_log` entry.
     *
     * @see logOfAChangedRecordByCore()
     */
    #[Test]
    public function logOfAChangedRecordByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes no `sys_log` entry.');

        // Given: history tracking is switched on and post 3 is read
        // @phpstan-ignore deadCode.unreachable
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = true;
        $post = $this->get(PostRepository::class)->findByUid(3);

        // When: the title is changed and persisted
        $post->setTitle('Changed post');
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: one log entry exists
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Log/ChangedPost.csv');
    }

    /**
     * The English post 3 "Third post" without translation is deleted through DataHandler. DataHandler writes one
     * `sys_log` entry with the action "delete" (3) and the message "Record {table}:{uid} was deleted from pages:{pid}".
     *
     * @see logOfADeletedRecordByExtbase()
     */
    #[Test]
    public function logOfADeletedRecordByCore(): void
    {
        // When: post 3 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [3 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and writes one log entry
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Log/DeletedPost.csv');
    }

    /**
     * Extbase removes the post but writes no `sys_log` entry.
     *
     * @see logOfADeletedRecordByCore()
     */
    #[Test]
    public function logOfADeletedRecordByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes no `sys_log` entry.');

        // Given: history tracking is switched on and post 3 is read
        // @phpstan-ignore deadCode.unreachable
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = true;
        $post = $this->get(PostRepository::class)->findByUid(3);

        // When: the post is removed and persisted
        $this->get(PostRepository::class)->remove($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: one log entry exists
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Log/DeletedPost.csv');
    }
}
