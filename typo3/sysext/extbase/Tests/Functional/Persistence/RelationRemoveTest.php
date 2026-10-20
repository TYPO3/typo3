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
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\CollectionRepository;
use TYPO3Tests\BlogExample\Domain\Repository\LabelRepository;

/**
 * Removing records with further relation types: inline children that have children of their own and the relation rows
 * of a many-to-many relation that is written from both sides.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle (DataHandler, suffix
 * "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair start from the same fixture in
 * Fixtures/RelationRemove/Given/ and assert the database against the same result CSV in Fixtures/RelationRemove/. Both
 * run as the same administrator at the same fixed time. An Extbase test that differs from core is skipped.
 *
 * The blog table, the post table, the comment table, the collection table and the label table have a soft delete
 * column. A blog has posts and a post has comments as inline children with a foreign field, mapped with cascade remove
 * in Extbase on both levels. The collections have the many-to-many relation `labels` to the labels, and the label table
 * has the field `collections` as the foreign side of the same relation.
 */
final class RelationRemoveTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationRemove/Given/Base.csv');
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
     * Blog 1 "Tech blog" has the post 1 "Hello world", which has the comments 1 and 2; both levels are inline children
     * with a foreign field. Blog 1 is deleted through DataHandler. DataHandler marks the blog, the post and both
     * comments deleted and sets `tstamp` of all four rows to the current time.
     *
     * @see deletingABlogDeletesItsPostsAndCommentsByExtbase()
     */
    #[Test]
    public function deletingABlogDeletesItsPostsAndCommentsByCore(): void
    {
        // Given: blog 1 exists with the post 1 and the comments 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationRemove/Given/BlogWithPostAndComments.csv');

        // When: blog 1 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_blog' => [1 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the blog, its post and both comments are marked deleted
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationRemove/DeletedBlogWithPostAndComments.csv');
    }

    /**
     * Extbase removes blog 1 "Tech blog", whose properties `posts` and `comments` are mapped with cascade remove, and
     * persists with `persistAll()`. Extbase marks the blog, the post and both comments deleted and sets `tstamp` of all
     * four rows to the current time, like DataHandler.
     *
     * @see deletingABlogDeletesItsPostsAndCommentsByCore()
     */
    #[Test]
    public function deletingABlogDeletesItsPostsAndCommentsByExtbase(): void
    {
        // Given: blog 1 exists with the post 1 and the comments 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationRemove/Given/BlogWithPostAndComments.csv');
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: blog 1 is removed and persisted
        $this->get(BlogRepository::class)->remove($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the blog, its post and both comments are marked deleted
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationRemove/DeletedBlogWithPostAndComments.csv');
    }

    /**
     * Label 1 "Alpha", the foreign side of the many-to-many relation to the collections, has the collections 1 and 2
     * through the relation table. Label 1 is deleted through DataHandler. DataHandler keeps the row, sets `deleted` to
     * 1 and sets `tstamp` to the current time. It leaves both rows of the relation table and the numbers of relations
     * in the label and in the collections as they are.
     *
     * @see deletingALabelOfABidirectionalMmRelationKeepsTheMmRowsByExtbase()
     */
    #[Test]
    public function deletingALabelOfABidirectionalMmRelationKeepsTheMmRowsByCore(): void
    {
        // Given: label 1 has the collections 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationRemove/Given/LabelWithCollections.csv');

        // When: label 1 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_label' => [1 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error, the label is marked deleted and the relation rows stay
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationRemove/DeletedLabelWithCollections.csv');
    }

    /**
     * Extbase removes label 1 "Alpha" and persists with `persistAll()`. Extbase keeps the row, sets `deleted` to 1 and
     * sets `tstamp` to the current time and leaves both rows of the relation table and the numbers of relations as they
     * are, like DataHandler.
     *
     * @see deletingALabelOfABidirectionalMmRelationKeepsTheMmRowsByCore()
     */
    #[Test]
    public function deletingALabelOfABidirectionalMmRelationKeepsTheMmRowsByExtbase(): void
    {
        // Given: label 1 has the collections 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationRemove/Given/LabelWithCollections.csv');
        $label = $this->get(LabelRepository::class)->findByUid(1);

        // When: label 1 is removed and persisted
        $this->get(LabelRepository::class)->remove($label);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the label is marked deleted and the relation rows stay
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationRemove/DeletedLabelWithCollections.csv');
    }

    /**
     * Collection 1 "First collection" has the label 1 "Alpha" through the relation table, which label 1 also lists as
     * its collection. Collection 1 is deleted through DataHandler. DataHandler keeps the row, sets `deleted` to 1 and
     * sets `tstamp` to the current time. It leaves both rows of the relation table and the numbers of relations in the
     * label and in the collections as they are, so label 1 still counts two collections.
     *
     * @see deletingACollectionOfABidirectionalMmRelationKeepsTheMmRowsByExtbase()
     */
    #[Test]
    public function deletingACollectionOfABidirectionalMmRelationKeepsTheMmRowsByCore(): void
    {
        // Given: collection 1 and collection 2 have the label 1
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationRemove/Given/LabelWithCollections.csv');

        // When: collection 1 is deleted
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_collection' => [1 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error, the collection is marked deleted and the relation rows stay
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationRemove/DeletedCollectionOfLabel.csv');
    }

    /**
     * Extbase removes collection 1 "First collection" and persists with `persistAll()`. Extbase keeps the row, sets
     * `deleted` to 1 and sets `tstamp` to the current time and leaves both rows of the relation table and the numbers
     * of relations as they are, like DataHandler.
     *
     * @see deletingACollectionOfABidirectionalMmRelationKeepsTheMmRowsByCore()
     */
    #[Test]
    public function deletingACollectionOfABidirectionalMmRelationKeepsTheMmRowsByExtbase(): void
    {
        // Given: collection 1 and collection 2 have the label 1
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationRemove/Given/LabelWithCollections.csv');
        $collection = $this->get(CollectionRepository::class)->findByUid(1);

        // When: collection 1 is removed and persisted
        $this->get(CollectionRepository::class)->remove($collection);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the collection is marked deleted and the relation rows stay
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationRemove/DeletedCollectionOfLabel.csv');
    }
}
