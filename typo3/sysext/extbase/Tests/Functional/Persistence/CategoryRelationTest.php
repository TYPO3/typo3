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
use TYPO3Tests\BlogExample\Domain\Repository\CategoryRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Writing and removing categories: the relation rows of a category field.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle (DataHandler, suffix
 * "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair start from the same fixture in
 * Fixtures/CategoryRelation/Given/ and assert the database against the same result CSV in Fixtures/CategoryRelation/.
 * Both run as the same administrator at the same fixed time. An Extbase test that differs from core is skipped.
 *
 * The post table has a soft delete column and a field `categories` of the type `category`, a many-to-many relation to
 * the categories 1 (News), 2 (Tech) and 3 (Sports) of the table `sys_category`. The relation rows are stored in the
 * table `sys_category_record_mm`: `uid_local` is the uid of the category, `uid_foreign` the uid of the post, and the
 * columns `tablenames` and `fieldname` name the table and the field of the post, because the relation table is shared
 * by all category fields. In Extbase the field is a property of the type ObjectStorage of Category. DataHandler
 * receives the categories as a comma separated list of uids in the field of the record, as the backend form sends them.
 */
final class CategoryRelationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/Given/Base.csv');
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
     * The categories 1 and 2 are set on post 1 "Hello world" through DataHandler, as the backend form sends them: the
     * field `categories` holds the uid list "1,2". DataHandler writes one row per category into
     * `sys_category_record_mm` with `uid_foreign` 1, the table name and the field name, the `sorting_foreign` 1 and 2
     * in the order of the list and the `sorting` 0, and writes the number of categories, 2, into the post.
     *
     * @see addingTwoCategoriesWritesMmRowsInOrderByExtbase()
     */
    #[Test]
    public function addingTwoCategoriesWritesMmRowsInOrderByCore(): void
    {
        // Given: post 1 exists without categories
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/Given/Record.csv');

        // When: the categories 1 and 2 are set on post 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['categories' => '1,2']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the relation rows carry the order
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/AddedCategories.csv');
    }

    /**
     * Extbase adds the categories 1 and 2 to post 1 "Hello world" and persists the post with `update()` and
     * `persistAll()`. Extbase writes the same relation rows as DataHandler: `uid_foreign` 1, the table name and the
     * field name, the `sorting_foreign` 1 and 2 in the order of the additions and the `sorting` 0, and writes the
     * number of categories, 2, into the post.
     *
     * @see addingTwoCategoriesWritesMmRowsInOrderByCore()
     */
    #[Test]
    public function addingTwoCategoriesWritesMmRowsInOrderByExtbase(): void
    {
        // Given: post 1 exists without categories
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/Given/Record.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the categories 1 and 2 are added and persisted
        $post->addCategory($this->get(CategoryRepository::class)->findByUid(1));
        $post->addCategory($this->get(CategoryRepository::class)->findByUid(2));
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the relation rows carry the order
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/AddedCategories.csv');
    }

    /**
     * Only category 2 is sent for post 1 "Hello world" through DataHandler, as the backend form sends the remaining
     * category after the editor removed category 1. DataHandler deletes the relation row of category 1, renumbers the
     * `sorting_foreign` of the row of category 2 to 1 and writes the number of categories, 1, into the post.
     *
     * @see removingOneCategoryRemovesItsMmRowByExtbase()
     */
    #[Test]
    public function removingOneCategoryRemovesItsMmRowByCore(): void
    {
        // Given: post 1 exists with the categories 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/Given/Categorized.csv');

        // When: only category 2 is sent
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['categories' => '2']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the relation row of category 1 is gone
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/RemovedCategory.csv');
    }

    /**
     * Extbase removes category 1 from post 1 "Hello world" and persists the post with `update()` and `persistAll()`.
     * Extbase deletes the relation row of category 1 and writes the number of categories, 1, into the post, but leaves
     * the `sorting_foreign` of the remaining row of category 2 at 2 instead of renumbering it to 1.
     *
     * @see removingOneCategoryRemovesItsMmRowByCore()
     */
    #[Test]
    public function removingOneCategoryRemovesItsMmRowByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it deletes the relation row of the removed category but leaves the remaining row at the `sorting_foreign` 2 instead of renumbering it to 1.');

        // Given: post 1 exists with the categories 1 and 2
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/Given/Categorized.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: category 1 is removed and persisted
        $post->removeCategory($this->get(CategoryRepository::class)->findByUid(1));
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the relation row of category 1 is gone
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/RemovedCategory.csv');
    }

    /**
     * The categories of post 1 "Hello world" are sent in the order 2, 1 through DataHandler, as the backend form sends
     * them after the editor moved a category: the field `categories` holds the uid list "2,1". DataHandler keeps both
     * relation rows and rewrites their `sorting_foreign`: category 2 gets 1, category 1 gets 2.
     *
     * @see reorderingCategoriesRewritesTheSortingByExtbase()
     */
    #[Test]
    public function reorderingCategoriesRewritesTheSortingByCore(): void
    {
        // Given: post 1 exists with the categories 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/Given/Categorized.csv');

        // When: the categories are sent in the order 2, 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['categories' => '2,1']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the sorting of the relation rows is swapped
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/ReorderedCategories.csv');
    }

    /**
     * Extbase detaches both categories from post 1 "Hello world", attaches them again in the order 2, 1 and persists
     * the post with `update()` and `persistAll()`. Extbase keeps both relation rows and rewrites their
     * `sorting_foreign` like DataHandler: category 2 gets 1, category 1 gets 2.
     *
     * @see reorderingCategoriesRewritesTheSortingByCore()
     */
    #[Test]
    public function reorderingCategoriesRewritesTheSortingByExtbase(): void
    {
        // Given: post 1 exists with the categories 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/Given/Categorized.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the categories are put in the order 2, 1 and persisted
        $categories = $post->getCategories()->toArray();
        foreach ($categories as $category) {
            $post->removeCategory($category);
        }
        foreach (array_reverse($categories) as $category) {
            $post->addCategory($category);
        }
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the sorting of the relation rows is swapped
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/ReorderedCategories.csv');
    }

    /**
     * Post 1 "Hello world", which has the categories News (1) and Tech (2), is deleted through DataHandler. DataHandler
     * keeps the row, sets `deleted` to 1 and sets `tstamp` to the current time. It leaves both rows in
     * `sys_category_record_mm` and the number of categories in the post as they are, and the categories stay as they
     * are.
     *
     * @see deletingARecordWithCategoriesKeepsTheMmRowsByExtbase()
     */
    #[Test]
    public function deletingARecordWithCategoriesKeepsTheMmRowsByCore(): void
    {
        // Given: post 1 exists with the categories 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/Given/Categorized.csv');

        // When: post 1 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error, the post is marked deleted and the relation rows stay
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/DeletedCategorizedPost.csv');
    }

    /**
     * Extbase removes post 1 "Hello world" and persists with `persistAll()`. Extbase keeps the row, sets `deleted` to 1
     * and sets `tstamp` to the current time, and leaves both rows in `sys_category_record_mm`, the number of categories
     * in the post and the categories as they are, like DataHandler.
     *
     * @see deletingARecordWithCategoriesKeepsTheMmRowsByCore()
     */
    #[Test]
    public function deletingARecordWithCategoriesKeepsTheMmRowsByExtbase(): void
    {
        // Given: post 1 exists with the categories 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/Given/Categorized.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the post is removed and persisted
        $this->get(PostRepository::class)->remove($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the post is marked deleted and the relation rows stay
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CategoryRelation/DeletedCategorizedPost.csv');
    }
}
