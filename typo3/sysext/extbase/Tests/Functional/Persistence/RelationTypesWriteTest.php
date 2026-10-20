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
use TYPO3Tests\BlogExample\Domain\Model\Attachment;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Comment;
use TYPO3Tests\BlogExample\Domain\Model\FrontendUserGroup;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\AdministratorRepository;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\CollectionRepository;
use TYPO3Tests\BlogExample\Domain\Repository\LabelRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Writing further relation types: a many-to-many relation written from the foreign side, lists of uids without a
 * relation table, inline children without a foreign field, one child table for two inline fields, file references and
 * nested inline children.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle (DataHandler, suffix
 * "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair start from the same fixture in
 * Fixtures/RelationTypesWrite/Given/ and assert the database against the same result CSV in Fixtures/RelationTypesWrite/. Both
 * run as the same administrator at the same fixed time. An Extbase test that differs from core is skipped.
 *
 * The collection has a many-to-many relation `labels` to the labels (relation table with `sorting` and
 * `sorting_foreign`); the label has the field `collections` as the foreign side of the same relation
 * (`MM_opposite_field`). The field `refs` of the collection is a group field of the label table with up to five items
 * and without a relation table. The field `usergroup` of the administrator is a select field with a foreign table and
 * without a relation table. The field `additional_comments` of the post is an inline field without a foreign field.
 * The fields `gallery_attachments` and `document_attachments` of the collection are inline fields to the same
 * attachment table, told apart by the foreign table field `parent_table` and the match field `parent_field`. The
 * field `logo` of the blog holds file references. The field `posts` of the blog and the field `comments` of the post
 * are inline fields with a foreign field.
 */
final class RelationTypesWriteTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Base.csv');
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
     * The collections 1 "First collection" and 2 "Second collection" are set on the label 1 "Alpha" through DataHandler
     * from the foreign side of the bidirectional many-to-many relation, as the backend form sends them: the field
     * `collections` of the label holds the uid list "1,2". DataHandler writes one row per collection into
     * `tx_blogexample_collection_label_mm` with `uid_local` as the uid of the collection, `uid_foreign` 1 and the
     * `sorting_foreign` 1 and 2 in the order of the list, leaves `sorting` at 0 and writes the number of collections,
     * 2, into the label. The field `labels` of the two collections stays 0.
     *
     * @see addingCollectionsToALabelOnTheForeignSideByExtbase()
     */
    #[Test]
    public function addingCollectionsToALabelOnTheForeignSideByCore(): void
    {
        // Given: the collections 1 and 2 and the labels 1 "Alpha" and 2 "Beta" exist, without relations
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Collections.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Labels.csv');

        // When: the collections 1 and 2 are set on label 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_label' => [1 => ['collections' => '1,2']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the relation rows carry the order in sorting_foreign
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/AddedCollectionsToLabel.csv');
    }

    /**
     * Extbase attaches the collections 1 and 2 to the property `collections` of label 1 "Alpha" and persists the label
     * with `update()` and `persistAll()`. Extbase writes the same relation rows as DataHandler: `uid_local` as the uid
     * of the collection, `uid_foreign` 1, the `sorting_foreign` 1 and 2 in the order of the additions and the `sorting`
     * 0, and writes the number of collections, 2, into the label.
     *
     * @see addingCollectionsToALabelOnTheForeignSideByCore()
     */
    #[Test]
    public function addingCollectionsToALabelOnTheForeignSideByExtbase(): void
    {
        // Given: the collections 1 and 2 and the labels 1 "Alpha" and 2 "Beta" exist, without relations
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Collections.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Labels.csv');
        $label = $this->get(LabelRepository::class)->findByUid(1);

        // When: the collections 1 and 2 are set on label 1 and persisted
        $label->getCollections()->attach($this->get(CollectionRepository::class)->findByUid(1));
        $label->getCollections()->attach($this->get(CollectionRepository::class)->findByUid(2));
        $this->get(LabelRepository::class)->update($label);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the relation rows carry the order in sorting_foreign
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/AddedCollectionsToLabel.csv');
    }

    /**
     * The label 1 "Alpha" has the collections 1 and 2 through the relation table. The collections are sent in the order
     * 2, 1 through DataHandler from the foreign side, as the backend form sends them after the editor moved a
     * collection: the field `collections` of the label holds the uid list "2,1". DataHandler keeps both relation rows
     * and swaps their `sorting_foreign`: collection 2 gets 1, collection 1 gets 2. It sets `tstamp` of the label to the
     * current time.
     *
     * @see reorderingTheCollectionsOfALabelOnTheForeignSideByExtbase()
     */
    #[Test]
    public function reorderingTheCollectionsOfALabelOnTheForeignSideByCore(): void
    {
        // Given: label 1 "Alpha" has the collections 1 and 2 through the relation table
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/LabelWithCollections.csv');

        // When: the collections are sent in the order 2, 1 for label 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_label' => [1 => ['collections' => '2,1']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the sorting_foreign of the relation rows is swapped
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/ReorderedCollectionsOfLabel.csv');
    }

    /**
     * Extbase detaches both collections from label 1 "Alpha", attaches them again in the order 2, 1 and persists the
     * label with `update()` and `persistAll()`. Extbase keeps both relation rows and swaps their `sorting_foreign` like
     * DataHandler: collection 2 gets 1, collection 1 gets 2. It sets `tstamp` of the label to the current time.
     *
     * @see reorderingTheCollectionsOfALabelOnTheForeignSideByCore()
     */
    #[Test]
    public function reorderingTheCollectionsOfALabelOnTheForeignSideByExtbase(): void
    {
        // Given: label 1 "Alpha" has the collections 1 and 2 through the relation table
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/LabelWithCollections.csv');
        $label = $this->get(LabelRepository::class)->findByUid(1);

        // When: the collections are put in the order 2, 1 and persisted
        $collections = $label->getCollections()->toArray();
        foreach ($collections as $collection) {
            $label->getCollections()->detach($collection);
        }
        foreach (array_reverse($collections) as $collection) {
            $label->getCollections()->attach($collection);
        }
        $this->get(LabelRepository::class)->update($label);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the sorting_foreign of the relation rows is swapped
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/ReorderedCollectionsOfLabel.csv');
    }

    /**
     * The user groups 1 "Editors" and 2 "Reviewers" are set as `usergroup` of the administrator 1 "blogadmin" through
     * DataHandler. The field `usergroup` is a select field with a foreign table and without a relation table: it holds
     * the uids as a comma separated list. The backend form sends the uid list "1,2", DataHandler writes "1,2" into the
     * field and sets `tstamp` of the administrator to the current time.
     *
     * @see addingTwoUserGroupsToASelectWithoutRelationTableByExtbase()
     */
    #[Test]
    public function addingTwoUserGroupsToASelectWithoutRelationTableByCore(): void
    {
        // Given: administrator 1 exists without user groups
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Administrator.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Groups.csv');

        // When: the user groups 1 and 2 are set on administrator 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['fe_users' => [1 => ['usergroup' => '1,2']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the field holds the uids in the order of the list
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/AddedUserGroups.csv');
    }

    /**
     * Extbase attaches the user groups 1 and 2 to the property `usergroup` of administrator 1 "blogadmin" and persists
     * the administrator with `update()` and `persistAll()`. Extbase writes the uid list "1,2" into the field
     * `usergroup` and sets `tstamp` of the administrator to the current time, like DataHandler.
     *
     * @see addingTwoUserGroupsToASelectWithoutRelationTableByCore()
     */
    #[Test]
    public function addingTwoUserGroupsToASelectWithoutRelationTableByExtbase(): void
    {
        // Given: administrator 1 exists without user groups
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Administrator.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Groups.csv');
        $administrator = $this->get(AdministratorRepository::class)->findByUid(1);

        // When: the user groups 1 and 2 are set on administrator 1 and persisted
        $administrator->getUsergroup()->attach($this->get(PersistenceManager::class)->getObjectByIdentifier(1, FrontendUserGroup::class));
        $administrator->getUsergroup()->attach($this->get(PersistenceManager::class)->getObjectByIdentifier(2, FrontendUserGroup::class));
        $this->get(AdministratorRepository::class)->update($administrator);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the field holds the uids in the order of the list
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/AddedUserGroups.csv');
    }

    /**
     * The user groups of administrator 1 "blogadmin", the groups 1 "Editors" and 2 "Reviewers", are sent in the order
     * 2, 1 through DataHandler, as the backend form sends them after the editor moved a group. DataHandler writes the
     * uid list "2,1" into the field `usergroup` and sets `tstamp` of the administrator to the current time.
     *
     * @see reorderingTheUserGroupsOfASelectWithoutRelationTableByExtbase()
     */
    #[Test]
    public function reorderingTheUserGroupsOfASelectWithoutRelationTableByCore(): void
    {
        // Given: administrator 1 has the user groups 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/AdministratorWithGroups.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Groups.csv');

        // When: the user groups are sent in the order 2, 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['fe_users' => [1 => ['usergroup' => '2,1']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the field holds the uids in the new order
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/ReorderedUserGroups.csv');
    }

    /**
     * Extbase detaches both user groups from the property `usergroup` of administrator 1 "blogadmin", attaches them
     * again in the order 2, 1 and persists the administrator with `update()` and `persistAll()`. Extbase writes the uid
     * list "2,1" into the field `usergroup` and sets `tstamp` of the administrator to the current time, like
     * DataHandler.
     *
     * @see reorderingTheUserGroupsOfASelectWithoutRelationTableByCore()
     */
    #[Test]
    public function reorderingTheUserGroupsOfASelectWithoutRelationTableByExtbase(): void
    {
        // Given: administrator 1 has the user groups 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/AdministratorWithGroups.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Groups.csv');
        $administrator = $this->get(AdministratorRepository::class)->findByUid(1);

        // When: the user groups are put in the order 2, 1 and persisted
        $groups = $administrator->getUsergroup()->toArray();
        foreach ($groups as $group) {
            $administrator->getUsergroup()->detach($group);
        }
        foreach (array_reverse($groups) as $group) {
            $administrator->getUsergroup()->attach($group);
        }
        $this->get(AdministratorRepository::class)->update($administrator);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the field holds the uids in the new order
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/ReorderedUserGroups.csv');
    }

    /**
     * The labels 1 "Alpha" and 2 "Beta" are set as `refs` of the collection 1 "First collection" through DataHandler.
     * The field `refs` is a group field that allows only the label table, up to five items and has no relation table:
     * it holds the uids as a comma separated list. The backend form sends the uid list "1,2", DataHandler writes "1,2"
     * into the field and sets `tstamp` of the collection to the current time.
     *
     * @see addingTwoLabelsToAGroupFieldWithoutRelationTableByExtbase()
     */
    #[Test]
    public function addingTwoLabelsToAGroupFieldWithoutRelationTableByCore(): void
    {
        // Given: collection 1 exists without refs
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Collection.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Labels.csv');

        // When: the labels 1 and 2 are set as refs of collection 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_collection' => [1 => ['refs' => '1,2']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the field holds the uids in the order of the list
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/AddedRefs.csv');
    }

    /**
     * Extbase attaches the labels 1 and 2 to the property `refs` of collection 1 "First collection" and persists the
     * collection with `update()` and `persistAll()`. Extbase writes the uid list "1,2" into the field `refs` and sets
     * `tstamp` of the collection to the current time, like DataHandler.
     *
     * @see addingTwoLabelsToAGroupFieldWithoutRelationTableByCore()
     */
    #[Test]
    public function addingTwoLabelsToAGroupFieldWithoutRelationTableByExtbase(): void
    {
        // Given: collection 1 exists without refs
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Collection.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Labels.csv');
        $collection = $this->get(CollectionRepository::class)->findByUid(1);

        // When: the labels 1 and 2 are set as refs of collection 1 and persisted
        $collection->getRefs()->attach($this->get(LabelRepository::class)->findByUid(1));
        $collection->getRefs()->attach($this->get(LabelRepository::class)->findByUid(2));
        $this->get(CollectionRepository::class)->update($collection);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the field holds the uids in the order of the list
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/AddedRefs.csv');
    }

    /**
     * Post 1 "Hello world" has the comment 1 in its field `additional_comments`, an inline field without foreign field:
     * the table of the comments has no column that points back to this field, the post holds the uids of its comments
     * as a comma separated list. A new comment is sent as `NEW1` in the list "1,NEW1", as the backend form sends it
     * after the editor added a comment. DataHandler creates comment 2 with `pid` 2 and the current time as `crdate` and
     * `tstamp` and writes "1,2" into the field `additional_comments` of the post.
     *
     * @see addingAChildToInlineChildrenWithoutForeignFieldByExtbase()
     */
    #[Test]
    public function addingAChildToInlineChildrenWithoutForeignFieldByCore(): void
    {
        // Given: post 1 has the comment 1 in its field additional_comments
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/PostWithComment.csv');

        // When: a new comment is added to post 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_blogexample_domain_model_post' => [1 => ['additional_comments' => '1,NEW1']],
            'tx_blogexample_domain_model_comment' => ['NEW1' => ['pid' => 2, 'author' => 'Ben', 'email' => 'ben@example.com', 'content' => 'Well written']],
        ], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the comment is created and listed in the field
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/AddedComment.csv');
    }

    /**
     * Extbase attaches a new comment to the property `additionalComments` of post 1 "Hello world" and persists the post
     * with `update()` and `persistAll()`. Extbase creates comment 2 with `pid` 2 and the current time as `crdate` and
     * `tstamp` and writes "1,2" into the field `additional_comments` of the post, like DataHandler.
     *
     * @see addingAChildToInlineChildrenWithoutForeignFieldByCore()
     */
    #[Test]
    public function addingAChildToInlineChildrenWithoutForeignFieldByExtbase(): void
    {
        // Given: post 1 has the comment 1 in its field additional_comments
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/PostWithComment.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: a new comment is added to post 1 and persisted
        $comment = new Comment();
        $comment->setAuthor('Ben');
        $comment->setEmail('ben@example.com');
        $comment->setContent('Well written');
        $post->addAdditionalComment($comment);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the comment is created and listed in the field
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/AddedComment.csv');
    }

    /**
     * The inline fields `gallery_attachments` and `document_attachments` of collection 1 "First collection" use the
     * same child table, the attachments. Each field has the foreign field `parent`, the table field `parent_table` and
     * a match field `parent_field` with its own value. A new attachment is sent for each field, as the backend form
     * sends them: the fields hold `NEW1` and `NEW2` and the attachments are NEW records with their `pid` and the uid of
     * the collection as parent. DataHandler creates attachment 1 with the table name of the collections as
     * `parent_table`, `parent_field` `gallery_attachments` and the `sorting` 1, and attachment 2 with `parent_field`
     * `document_attachments` and the `sorting` 1, both with the current time as `crdate` and `tstamp`, and writes the
     * number of attachments, 1, into both fields of the collection.
     *
     * @see addingChildrenToTwoInlineFieldsOfOneChildTableByExtbase()
     */
    #[Test]
    public function addingChildrenToTwoInlineFieldsOfOneChildTableByCore(): void
    {
        // Given: collection 1 exists without attachments
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Collection.csv');

        // When: a new attachment is added to each of the fields gallery_attachments and document_attachments of
        // collection 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_blogexample_domain_model_collection' => [1 => ['gallery_attachments' => 'NEW1', 'document_attachments' => 'NEW2']],
            'tx_blogexample_domain_model_attachment' => [
                'NEW1' => ['pid' => 2, 'title' => 'Gallery attachment', 'parent' => 1],
                'NEW2' => ['pid' => 2, 'title' => 'Document attachment', 'parent' => 1],
            ],
        ], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and each attachment carries the table, the field and the uid of the
        // collection
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/AddedAttachments.csv');
    }

    /**
     * Extbase attaches a new attachment to the property `galleryAttachments` and another one to the property
     * `documentAttachments` of collection 1 "First collection" and persists the collection with `update()` and
     * `persistAll()`. Extbase creates both attachments with parent 1, the table name of the collections as
     * `parent_table`, the `parent_field` of its property and the `sorting` 1, and writes the number 1 into both fields
     * of the collection, like DataHandler.
     *
     * @see addingChildrenToTwoInlineFieldsOfOneChildTableByCore()
     */
    #[Test]
    public function addingChildrenToTwoInlineFieldsOfOneChildTableByExtbase(): void
    {
        // Given: collection 1 exists without attachments
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/Collection.csv');
        $collection = $this->get(CollectionRepository::class)->findByUid(1);

        // When: a new attachment is added to each of the fields gallery_attachments and document_attachments of
        // collection 1 and persisted
        $gallery = new Attachment();
        $gallery->setTitle('Gallery attachment');
        $collection->getGalleryAttachments()->attach($gallery);
        $document = new Attachment();
        $document->setTitle('Document attachment');
        $collection->getDocumentAttachments()->attach($document);
        $this->get(CollectionRepository::class)->update($collection);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: each attachment carries the table, the field and the uid of the collection
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/AddedAttachments.csv');
    }

    /**
     * Blog 1 "Tech blog" has the file references 1 (to the file 1) and 2 (to the file 2) in its field `logo`. The form
     * sends only the reference 2 in the field and deletes reference 1 by a command, as the backend form does after the
     * editor removed a file. DataHandler marks reference 1 deleted, sets `tstamp` of both references and of the blog to
     * the current time, renumbers reference 2 to the `sorting_foreign` 1 and writes the number of references, 1, into
     * the field `logo`.
     *
     * @see removingOneOfTwoFileReferencesByExtbase()
     */
    #[Test]
    public function removingOneOfTwoFileReferencesByCore(): void
    {
        // Given: blog 1 has the file references 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/BlogWithTwoLogos.csv');

        // When: the form sends only reference 2 and deletes reference 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_blogexample_domain_model_blog' => [1 => ['logo' => '2']]],
            ['sys_file_reference' => [1 => ['delete' => 1]]]
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and reference 1 is deleted
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/RemovedLogo.csv');
    }

    /**
     * Extbase removes the first reference from the property `logo` of blog 1 "Tech blog" and persists the blog with
     * `update()` and `persistAll()`. Extbase writes the number 1 into the field `logo` and sets `tstamp` of the blog,
     * but it does not delete reference 1: the reference keeps `deleted` 0, its `uid_foreign` becomes 0, its
     * `tablenames` and `fieldname` become empty and its `sorting_foreign` becomes 0. Reference 2 keeps its
     * `sorting_foreign` 2 and its old `tstamp`.
     *
     * @see removingOneOfTwoFileReferencesByCore()
     */
    #[Test]
    public function removingOneOfTwoFileReferencesByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it detaches the removed file reference (`uid_foreign`, `tablenames`, `fieldname` and `sorting_foreign` are cleared) but does not delete it, and leaves the remaining reference at the `sorting_foreign` 2.');

        // Given: blog 1 has the file references 1 and 2
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/BlogWithTwoLogos.csv');
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: the first reference is removed and persisted
        $blog->getLogo()->detach($blog->getLogo()->toArray()[0]);
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: reference 1 is deleted
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/RemovedLogo.csv');
    }

    /**
     * Blog 1 "Tech blog" has the file references 1 (to the file 1) and 2 (to the file 2) in its field `logo`. The
     * references are sent in the order 2, 1 through DataHandler, as the backend form sends them after the editor moved
     * a file: the field `logo` holds the uid list "2,1". DataHandler keeps both references and swaps their
     * `sorting_foreign`: reference 2 gets 1, reference 1 gets 2. It sets `tstamp` of both references and of the blog to
     * the current time.
     *
     * @see reorderingTwoFileReferencesByExtbase()
     */
    #[Test]
    public function reorderingTwoFileReferencesByCore(): void
    {
        // Given: blog 1 has the file references 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/BlogWithTwoLogos.csv');

        // When: the references are sent in the order 2, 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_blog' => [1 => ['logo' => '2,1']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the sorting_foreign of the references is swapped
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/ReorderedLogos.csv');
    }

    /**
     * Extbase detaches both references from the property `logo` of blog 1 "Tech blog", attaches them again in the order
     * 2, 1 and persists the blog with `update()` and `persistAll()`. Extbase keeps both references and swaps their
     * `sorting_foreign` like DataHandler: reference 2 gets 1, reference 1 gets 2. It sets `tstamp` of both references
     * and of the blog to the current time.
     *
     * @see reorderingTwoFileReferencesByCore()
     */
    #[Test]
    public function reorderingTwoFileReferencesByExtbase(): void
    {
        // Given: blog 1 has the file references 1 and 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/Given/BlogWithTwoLogos.csv');
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: the references are put in the order 2, 1 and persisted
        $references = $blog->getLogo()->toArray();
        foreach ($references as $reference) {
            $blog->getLogo()->detach($reference);
        }
        foreach (array_reverse($references) as $reference) {
            $blog->getLogo()->attach($reference);
        }
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the sorting_foreign of the references is swapped
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/ReorderedLogos.csv');
    }

    /**
     * A new blog, a new post of the blog and a new comment of the post are sent in one datamap through DataHandler, as
     * the backend form sends nested inline children: the field `posts` of the blog holds `NEW2`, the field `comments`
     * of the post holds `NEW3`, the children do not carry their parent, as the backend form does not send it.
     * DataHandler resolves the placeholders in one run, sets the foreign fields and creates blog 1, post 1 with the
     * blog 1 and comment 1 with the post 1, all three with the current time as `crdate` and `tstamp`. It writes the
     * number of children, 1, into the field `posts` of the blog and into the field `comments` of the post.
     *
     * @see creatingABlogWithAPostAndACommentByExtbase()
     */
    #[Test]
    public function creatingABlogWithAPostAndACommentByCore(): void
    {
        // When: a new blog with a new post with a new comment is sent in one datamap
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_blogexample_domain_model_blog' => ['NEW1' => ['pid' => 2, 'title' => 'New blog', 'description' => 'New blog description', 'posts' => 'NEW2']],
            'tx_blogexample_domain_model_post' => ['NEW2' => ['pid' => 2, 'title' => 'New post', 'comments' => 'NEW3']],
            'tx_blogexample_domain_model_comment' => ['NEW3' => ['pid' => 2, 'author' => 'Max', 'email' => 'max@example.com', 'content' => 'First comment']],
        ], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and all three records exist and point to each other
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/CreatedBlogWithPostAndComment.csv');
    }

    /**
     * Extbase creates a new blog with a new post that has a new comment, adds the blog to its repository and calls
     * `persistAll()` once. Extbase creates the blog, the post with the uid of the blog and the comment with the uid of
     * the post and writes the number of children, 1, into both fields, like DataHandler.
     *
     * @see creatingABlogWithAPostAndACommentByCore()
     */
    #[Test]
    public function creatingABlogWithAPostAndACommentByExtbase(): void
    {
        // When: a new blog with a new post with a new comment is added and persisted once
        $comment = new Comment();
        $comment->setAuthor('Max');
        $comment->setEmail('max@example.com');
        $comment->setContent('First comment');
        $post = new Post();
        $post->setTitle('New post');
        $post->addComment($comment);
        $blog = new Blog();
        $blog->setTitle('New blog');
        $blog->setDescription('New blog description');
        $blog->addPost($post);
        $this->get(BlogRepository::class)->add($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: all three records exist and point to each other
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTypesWrite/CreatedBlogWithPostAndComment.csv');
    }
}
