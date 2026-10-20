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
 * The columns `tstamp` and `crdate` of a record after creating and changing it.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle
 * (DataHandler, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair
 * assert the post table against the same result CSV in Fixtures/Timestamp/. Both run as the same
 * administrator at the fixed time 1700000000, the fixture records carry 1500000000 in both columns.
 * An Extbase test that differs from core is skipped.
 *
 * Fixture Posts.csv: post 1 "Hello world" and post 2 "Second post", both English.
 */
final class TimestampTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Timestamp/Given/Base.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Timestamp/Given/Posts.csv');
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
     * A new post "New post" is created through DataHandler. DataHandler sets `crdate` and `tstamp` of the new post to
     * the current time and leaves the other posts alone.
     *
     * @see timestampsOfACreatedRecordByExtbase()
     */
    #[Test]
    public function timestampsOfACreatedRecordByCore(): void
    {
        // When: a post is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => ['NEW1' => ['pid' => 2, 'title' => 'New post']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and `crdate` and `tstamp` of the new post are the current time
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Timestamp/CreatedPost.csv');
    }

    /**
     * Extbase sets `crdate` and `tstamp` of the new post "New post" to the current time, as DataHandler does.
     *
     * @see timestampsOfACreatedRecordByCore()
     */
    #[Test]
    public function timestampsOfACreatedRecordByExtbase(): void
    {
        // When: a post is created and persisted
        $post = new Post();
        $post->setTitle('New post');
        $this->get(PostRepository::class)->add($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: `crdate` and `tstamp` of the new post are the current time
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Timestamp/CreatedPost.csv');
    }

    /**
     * The title of the English post 1 "Hello world" is changed to "Changed post" through DataHandler. DataHandler sets
     * `tstamp` of the post to the current time and keeps its `crdate`; the other posts keep both columns.
     *
     * @see timestampOfAChangedRecordByExtbase()
     */
    #[Test]
    public function timestampOfAChangedRecordByCore(): void
    {
        // When: the title of post 1 is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['title' => 'Changed post']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and only `tstamp` of post 1 is the current time
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Timestamp/ChangedPost.csv');
    }

    /**
     * Extbase sets `tstamp` of the changed post 1 to the current time and keeps its `crdate`, as DataHandler does.
     *
     * @see timestampOfAChangedRecordByCore()
     */
    #[Test]
    public function timestampOfAChangedRecordByExtbase(): void
    {
        // Given: post 1 is read
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the title is changed and persisted
        $post->setTitle('Changed post');
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: only `tstamp` of post 1 is the current time
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Timestamp/ChangedPost.csv');
    }
}
