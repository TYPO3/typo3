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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\DataHandling\RecordFieldTransformer;
use TYPO3\CMS\Core\Domain\Exception\RecordPropertyException;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordPropertyClosure;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\CMS\Core\Resource\Collection\LazyFolderCollection;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordFieldTransformerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'workspaces',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_relation_resolver',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/core/Tests/Functional/DataHandling/Fixtures/TestFolder/' => 'fileadmin/',
    ];

    #[Test]
    public function canResolveFileReference(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/file_reference.csv');
        $dummyRecord = $this->createTestRecordObject(['image' => 1]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getSubSchema('typo3tests_contentelementb')->getField('image');
        $subject = $this->get(RecordFieldTransformer::class);
        $propertyClosure = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertInstanceOf(RecordPropertyClosure::class, $propertyClosure);
        $result = $propertyClosure->instantiate();

        self::assertInstanceOf(FileReference::class, $result);
        self::assertEquals('/kasper-skarhoj1.jpg', $result->getIdentifier());
        self::assertIsArray($result->getProperties());

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertInstanceOf(FileReference::class, $resolvedRecord->get('image'));
        self::assertEquals('/kasper-skarhoj1.jpg', $resolvedRecord->get('image')->getIdentifier());
    }

    #[Test]
    public function canHandleInvalidFileReference(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/file_reference.csv');
        $dummyRecord = $this->createTestRecordObject(['uid' => 261, 'image' => 1]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getSubSchema('typo3tests_contentelementb')->getField('image');
        $subject = $this->get(RecordFieldTransformer::class);
        $propertyClosure = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertInstanceOf(RecordPropertyClosure::class, $propertyClosure);
        $result = $propertyClosure->instantiate();
        self::assertNull($result);

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertNull($resolvedRecord->get('image'));
    }

    #[Test]
    public function canResolveFileReferences(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/file_references.csv');
        $dummyRecord = $this->createTestRecordObject(['media' => 2]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('media');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        foreach ($result as $fileReference) {
            self::assertInstanceOf(FileReference::class, $fileReference);
            self::assertEquals('/kasper-skarhoj1.jpg', $fileReference->getIdentifier());
        }

        self::assertCount(2, $result);
        self::assertInstanceOf(LazyFileReferenceCollection::class, $result);

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertInstanceOf(LazyFileReferenceCollection::class, $resolvedRecord->get('media'));
        self::assertInstanceOf(FileReference::class, $resolvedRecord->get('media')[0]);
        self::assertEquals('/kasper-skarhoj1.jpg', $resolvedRecord->get('media')[0]->getIdentifier());
    }

    #[Test]
    public function resolvesSingleFileReferenceWithoutMaxItems(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/file_references.csv');
        $dummyRecord = $this->createTestRecordObject(['assets' => 1]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('assets');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        foreach ($result as $fileReference) {
            self::assertInstanceOf(FileReference::class, $fileReference);
            self::assertEquals('/kasper-skarhoj1.jpg', $fileReference->getIdentifier());
        }

        self::assertCount(1, $result);
        self::assertInstanceOf(LazyFileReferenceCollection::class, $result);

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertCount(1, $resolvedRecord->get('assets'));
        self::assertInstanceOf(LazyFileReferenceCollection::class, $resolvedRecord->get('assets'));
        self::assertInstanceOf(FileReference::class, $resolvedRecord->get('assets')[0]);
        self::assertEquals('/kasper-skarhoj1.jpg', $resolvedRecord->get('assets')[0]->getIdentifier());
    }

    #[Test]
    public function canResolveFilesFromFolder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/folder_files.csv');
        $dummyRecord = $this->createTestRecordObject(['typo3tests_contentelementb_folder' => '1:/']);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_folder');
        $subject = $this->get(RecordFieldTransformer::class);
        $propertyClosure = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertInstanceOf(RecordPropertyClosure::class, $propertyClosure);
        $result = $propertyClosure->instantiate();
        self::assertInstanceOf(Folder::class, $result);
        self::assertEquals('/', $result->getIdentifier());
        self::assertEquals('1:/', $result->getCombinedIdentifier());
        self::assertCount(1, $result->getFiles());

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertInstanceOf(Folder::class, $resolvedRecord->get('typo3tests_contentelementb_folder'));
        self::assertEquals('/', $resolvedRecord->get('typo3tests_contentelementb_folder')->getIdentifier());
        self::assertEquals('1:/', $resolvedRecord->get('typo3tests_contentelementb_folder')->getCombinedIdentifier());
        self::assertCount(1, $resolvedRecord->get('typo3tests_contentelementb_folder')->getFiles());
    }

    #[Test]
    public function canHandleInvalidFolder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/folder_files.csv');
        $dummyRecord = $this->createTestRecordObject(['typo3tests_contentelementb_folder' => '']);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_folder');
        $subject = $this->get(RecordFieldTransformer::class);
        $propertyClosure = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertInstanceOf(RecordPropertyClosure::class, $propertyClosure);
        $result = $propertyClosure->instantiate();
        self::assertNull($result);

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertNull($resolvedRecord->get('typo3tests_contentelementb_folder'));
    }

    #[Test]
    public function canResolveFilesFromFolders(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/folder_files.csv');
        $dummyRecord = $this->createTestRecordObject(['typo3tests_contentelementb_folder_recursive' => '1:/']);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_folder_recursive');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class),
        );

        foreach ($result as $folder) {
            self::assertInstanceOf(Folder::class, $folder);
            self::assertEquals(1, $folder->getStorage()->getUid());
            self::assertCount(1, $folder->getFiles());
        }

        self::assertInstanceOf(LazyFolderCollection::class, $result);
        self::assertCount(2, $result);
        self::assertInstanceOf(Folder::class, $result[0]);
        self::assertEquals('/sub/', $result[1]->getIdentifier());
        self::assertEquals('1:/sub/', $result[1]->getCombinedIdentifier());
        self::assertCount(1, $result[1]->getFiles());

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertInstanceOf(LazyFolderCollection::class, $resolvedRecord->get('typo3tests_contentelementb_folder_recursive'));
        self::assertCount(2, $resolvedRecord->get('typo3tests_contentelementb_folder_recursive'));
        self::assertInstanceOf(Folder::class, $resolvedRecord->get('typo3tests_contentelementb_folder_recursive')[0]);
        self::assertEquals('/sub/', $resolvedRecord->get('typo3tests_contentelementb_folder_recursive')[1]->getIdentifier());
        self::assertEquals('1:/sub/', $resolvedRecord->get('typo3tests_contentelementb_folder_recursive')[1]->getCombinedIdentifier());
        self::assertCount(1, $resolvedRecord->get('typo3tests_contentelementb_folder_recursive')[1]->getFiles());
    }

    #[Test]
    public function canResolveCollections(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/collections.csv');
        $dummyRecord = $this->createTestRecordObject(['typo3tests_contentelementb_collection' => 2]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_collection');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertCount(2, $result);
        self::assertInstanceOf(LazyRecordCollection::class, $result);
        self::assertSame('lorem foo bar', $result[0]->get('fieldA'));
        self::assertSame('lorem foo bar 2', $result[1]->get('fieldA'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRecord->get('typo3tests_contentelementb_collection'));
        self::assertCount(2, $resolvedRecord->get('typo3tests_contentelementb_collection'));
        self::assertSame('lorem foo bar', $resolvedRecord->get('typo3tests_contentelementb_collection')[0]->get('fieldA'));
        self::assertSame('lorem foo bar 2', $resolvedRecord->get('typo3tests_contentelementb_collection')[1]->get('fieldA'));
    }

    #[Test]
    public function canResolveCollectionsRecursively(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/collections_recursive.csv');
        $dummyRecord = $this->createTestRecordObject(['typo3tests_contentelementb_collection_recursive' => 2]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_collection_recursive');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertCount(2, $result);
        self::assertInstanceOf(LazyRecordCollection::class, $result);
        self::assertSame('lorem foo bar A', $result[0]->get('fieldA'));
        self::assertSame('lorem foo bar A2', $result[1]->get('fieldA'));
        self::assertCount(2, $result[0]->get('collection_inner'));
        self::assertSame('lorem foo bar B', $result[0]->get('collection_inner')[0]->get('fieldB'));
        self::assertSame('lorem foo bar B2', $result[0]->get('collection_inner')[1]->get('fieldB'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRecord->get('typo3tests_contentelementb_collection_recursive'));
        self::assertCount(2, $resolvedRecord->get('typo3tests_contentelementb_collection_recursive'));
        self::assertSame('lorem foo bar A', $resolvedRecord->get('typo3tests_contentelementb_collection_recursive')[0]->get('fieldA'));
        self::assertSame('lorem foo bar A2', $resolvedRecord->get('typo3tests_contentelementb_collection_recursive')[1]->get('fieldA'));
        self::assertCount(2, $resolvedRecord->get('typo3tests_contentelementb_collection_recursive')[0]->get('collection_inner'));
        self::assertSame('lorem foo bar B', $resolvedRecord->get('typo3tests_contentelementb_collection_recursive')[0]->get('collection_inner')[0]->get('fieldB'));
        self::assertSame('lorem foo bar B2', $resolvedRecord->get('typo3tests_contentelementb_collection_recursive')[0]->get('collection_inner')[1]->get('fieldB'));
    }

    #[Test]
    public function canResolveCollectionsInWorkspaces(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/collections.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $this->setWorkspaceId(1);
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_collection' => 2,
            't3ver_oid' => 260,
            't3ver_wsid' => 1,
            '_ORIG_uid' => 261,
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_collection');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertCount(2, $result);
        self::assertInstanceOf(LazyRecordCollection::class, $result);
        self::assertSame('lorem foo bar WS', $result[0]->get('fieldA'));
        self::assertSame('lorem foo bar 2 WS', $result[1]->get('fieldA'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRecord->get('typo3tests_contentelementb_collection'));
        self::assertCount(2, $resolvedRecord->get('typo3tests_contentelementb_collection'));
        self::assertSame('lorem foo bar WS', $resolvedRecord->get('typo3tests_contentelementb_collection')[0]->get('fieldA'));
        self::assertSame('lorem foo bar 2 WS', $resolvedRecord->get('typo3tests_contentelementb_collection')[1]->get('fieldA'));
    }

    #[Test]
    public function canResolveCategoriesManyToMany(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/category_many_to_many.csv');
        $dummyRecord = $this->createTestRecordObject();
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_categories_mm');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertCount(2, $result);
        self::assertSame('Category 1', $result[0]->get('title'));
        self::assertSame('Category 2', $result[1]->get('title'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRecord->get('typo3tests_contentelementb_categories_mm'));
        self::assertCount(2, $resolvedRecord->get('typo3tests_contentelementb_categories_mm'));
        self::assertSame('Category 1', $resolvedRecord->get('typo3tests_contentelementb_categories_mm')[0]->get('title'));
        self::assertSame('Category 2', $resolvedRecord->get('typo3tests_contentelementb_categories_mm')[1]->get('title'));
    }

    #[Test]
    public function canResolveCategoriesManyToManyInWorkspaces(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/category_many_to_many.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $this->setWorkspaceId(1);

        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_categories_mm' => 2,
            'sys_language_uid' => 1,
            't3ver_oid' => 260,
            't3ver_wsid' => 1,
            '_ORIG_uid' => 261,
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_categories_mm');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertCount(2, $result);
        // @todo: this should be the other way around, but currently RelationResolver cannot handle different sorting in WS
        self::assertSame('Category 2 ws', $result[1]->get('title'));
        self::assertSame('Category 1 ws', $result[0]->get('title'));
    }

    #[Test]
    public function canResolveCategoriesManyToManyLocalizedOverlaysOff(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/category_many_to_many_localized.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF));
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_categories_mm' => 2,
            'sys_language_uid' => 1,
            'l18n_parent' => 260,
            '_LOCALIZED_UID' => 381,
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_categories_mm');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $context
        );

        self::assertCount(1, $result);
        self::assertSame('Category 1 translated', $result[0]->get('title'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', array_replace($dummyRecord->toArray(), ['uid' => 381]), $context);
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRecord->get('typo3tests_contentelementb_categories_mm'));
        self::assertCount(1, $resolvedRecord->get('typo3tests_contentelementb_categories_mm'));
        self::assertSame('Category 1 translated', $resolvedRecord->get('typo3tests_contentelementb_categories_mm')[0]->get('title'));
    }

    #[Test]
    public function canResolveCategoriesManyToManyLocalizedOverlaysOn(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/category_many_to_many_localized.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_categories_mm' => 2,
            'sys_language_uid' => 1,
            'l18n_parent' => 260,
            '_LOCALIZED_UID' => 381,
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_categories_mm');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $context
        );

        self::assertCount(1, $result);
        self::assertSame('Category 1 translated', $result[0]->get('title'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', array_replace($dummyRecord->toArray(), ['uid' => 381]), $context);
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRecord->get('typo3tests_contentelementb_categories_mm'));
        self::assertCount(1, $resolvedRecord->get('typo3tests_contentelementb_categories_mm'));
        self::assertSame('Category 1 translated', $resolvedRecord->get('typo3tests_contentelementb_categories_mm')[0]->get('title'));
    }

    #[Test]
    public function canResolveCategoriesOneToOne(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/category_one_to_one.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_categories_11' => 2,
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_categories_11');
        $subject = $this->get(RecordFieldTransformer::class);
        $propertyClosure = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertInstanceOf(RecordPropertyClosure::class, $propertyClosure);
        $result = $propertyClosure->instantiate();
        self::assertInstanceOf(Record::class, $result);
        self::assertSame(2, $result->getUid());
        self::assertSame('Category 1', $result->get('title'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertInstanceOf(Record::class, $resolvedRecord->get('typo3tests_contentelementb_categories_11'));
        self::assertSame(2, $resolvedRecord->get('typo3tests_contentelementb_categories_11')->getUid());
        self::assertSame('Category 1', $resolvedRecord->get('typo3tests_contentelementb_categories_11')->get('title'));
    }

    #[Test]
    public function canResolveCategoriesOneToMany(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/category_one_to_many.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_categories_1m' => '2,11',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_categories_1m');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertCount(2, $result);
        self::assertInstanceOf(LazyRecordCollection::class, $result);
        self::assertSame('Category 1', $result[0]->get('title'));
        self::assertSame('Category 2', $result[1]->get('title'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertCount(2, $resolvedRecord->get('typo3tests_contentelementb_categories_1m'));
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRecord->get('typo3tests_contentelementb_categories_1m'));
        self::assertSame('Category 1', $resolvedRecord->get('typo3tests_contentelementb_categories_1m')[0]->get('title'));
        self::assertSame('Category 2', $resolvedRecord->get('typo3tests_contentelementb_categories_1m')[1]->get('title'));
    }

    #[Test]
    public function canResolveDbRelation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/db_relation.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_pages_relation' => '1906',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getSubSchema('typo3tests_contentelementb')->getField('typo3tests_contentelementb_pages_relation');
        $subject = $this->get(RecordFieldTransformer::class);
        $propertyClosure = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertInstanceOf(RecordPropertyClosure::class, $propertyClosure);
        $result = $propertyClosure->instantiate();
        self::assertInstanceOf(Record::class, $result);
        self::assertSame(1906, $result->getUid());
        self::assertSame(1906, $result->get('uid'));
        self::assertSame('Page 1', $result->get('title'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_pages_relation');
        self::assertInstanceOf(Record::class, $resolvedRelation);
        self::assertSame(1906, $resolvedRelation->getUid());
        self::assertSame(1906, $resolvedRelation->get('uid'));
        self::assertSame('Page 1', $resolvedRelation->get('title'));
    }

    #[Test]
    public function canResolveDbRelations(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/db_relations.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_pages_relations' => '1906,3389',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_pages_relations');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertCount(2, $result);
        self::assertInstanceOf(LazyRecordCollection::class, $result);
        self::assertSame('Page 1', $result[0]->get('title'));
        self::assertSame('Page 2', $result[1]->get('title'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_pages_relations');
        self::assertCount(2, $resolvedRelation);
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRelation);
        self::assertSame(1906, $resolvedRelation[0]->getUid());
        self::assertSame(1906, $resolvedRelation[0]->get('uid'));
        self::assertSame('Page 1', $resolvedRelation[0]->get('title'));
        self::assertSame('Page 2', $resolvedRelation[1]->get('title'));
    }

    #[Test]
    public function canResolveCircularRelation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/circular_relation.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_circular_relation' => '260',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_circular_relation');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertCount(1, $result);
        self::assertInstanceOf(LazyRecordCollection::class, $result);
        self::assertSame(260, $result[0]->getUid());

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_circular_relation');
        self::assertCount(1, $resolvedRelation);
        self::assertInstanceOf(LazyRecordCollection::class, $result);
        self::assertSame(260, $resolvedRelation[0]->getUid());
        self::assertSame(260, $resolvedRelation[0]->get('uid'));
    }

    #[Test]
    public function canResolveDbRelationRecursive(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/db_relation_recursive.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_record_relation_recursive' => '1,2',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_record_relation_recursive');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertInstanceOf(LazyRecordCollection::class, $result);
        self::assertCount(2, $result);
        self::assertSame('Record 1', $result[0]->get('title'));
        self::assertSame('Record 2', $result[1]->get('title'));
        self::assertCount(1, $result[0]->get('record_collection'));
        self::assertCount(1, $result[1]->get('record_collection'));
        self::assertSame('Collection 1', $result[0]->get('record_collection')[0]->get('text'));
        self::assertSame('Collection 2', $result[1]->get('record_collection')[0]->get('text'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_record_relation_recursive');
        self::assertCount(2, $resolvedRelation);
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRelation);
        self::assertSame('Record 1', $resolvedRelation[0]->get('title'));
        self::assertSame('Record 2', $resolvedRelation[1]->get('title'));
        self::assertCount(1, $resolvedRelation[0]->get('record_collection'));
        self::assertCount(1, $resolvedRelation[1]->get('record_collection'));
        self::assertSame('Collection 1', $resolvedRelation[0]->get('record_collection')[0]->get('text'));
        self::assertSame('Collection 2', $resolvedRelation[1]->get('record_collection')[0]->get('text'));
    }

    #[Test]
    public function canResolveDbRelationsInWorkspaces(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/db_relations.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $this->setWorkspaceId(1);
        $dummyRecord = $this->createTestRecordObject([
            'uid' => 260,
            't3ver_oid' => 260,
            't3ver_wsid' => 1,
            '_ORIG_uid' => 261,
            'typo3tests_contentelementb_pages_relations' => '1906,3389',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_pages_relations');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertCount(2, $result);
        self::assertInstanceOf(LazyRecordCollection::class, $result);
        self::assertSame('Page 1 ws', $result[0]->get('title'));
        self::assertSame('Page 2 ws', $result[1]->get('title'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_pages_relations');
        self::assertCount(2, $resolvedRelation);
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRelation);
        self::assertSame('Page 1 ws', $resolvedRelation[0]->get('title'));
        self::assertSame('Page 2 ws', $resolvedRelation[1]->get('title'));
    }

    #[Test]
    public function canResolveMultipleDbRelations(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/db_relation_multiple.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_pages_content_relation' => 'pages_1,pages_2,tt_content_1,tt_content_2',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_pages_content_relation');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );
        self::assertCount(4, $result);
        self::assertInstanceOf(LazyRecordCollection::class, $result);
        self::assertSame('Page 1', $result[0]->get('title'));
        self::assertSame('Page 2', $result[1]->get('title'));
        self::assertSame('Content 1', $result[2]->get('header'));
        self::assertSame('Content 2', $result[3]->get('header'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_pages_content_relation');
        self::assertCount(4, $resolvedRelation);
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRelation);
        self::assertSame('Page 1', $resolvedRelation[0]->get('title'));
        self::assertSame('Page 2', $resolvedRelation[1]->get('title'));
        self::assertSame('Content 1', $resolvedRelation[2]->get('header'));
        self::assertSame('Content 2', $resolvedRelation[3]->get('header'));
    }

    #[Test]
    public function canResolveDbRelationsMM(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/db_relation_mm.csv');
        $dummyRecord = $this->createTestRecordObject([
            'uid' => 263,
            'typo3tests_contentelementb_pages_mm' => 2,
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_pages_mm');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertCount(2, $result);
        self::assertInstanceOf(LazyRecordCollection::class, $result);
        self::assertSame('Page 1', $result[0]->get('title'));
        self::assertSame('Page 2', $result[1]->get('title'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_pages_mm');
        self::assertCount(2, $resolvedRelation);
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRelation);
        self::assertSame('Page 1', $resolvedRelation[0]->get('title'));
        self::assertSame('Page 2', $resolvedRelation[1]->get('title'));
    }

    public static function multipleItemsAsArrayConversionDataProvider(): \Generator
    {
        yield 'selectCheckboxFormat' => [
            'fieldName' => 'typo3tests_contentelementb_select_checkbox',
            'input' => '1,2,3',
            'expected' => ['1', '2', '3'],
        ];
        yield 'selectSingleBoxCommaList' => [
            'fieldName' => 'typo3tests_contentelementb_select_single_box',
            'input' => '1,2,3',
            'expected' => ['1', '2', '3'],
        ];
        yield 'selectMultipleSideBySideCommaList' => [
            'fieldName' => 'typo3tests_contentelementb_select_multiple',
            'input' => '1,2,3',
            'expected' => ['1', '2', '3'],
        ];
        yield 'selectMultipleSideBySideWithOneValue' => [
            'fieldName' => 'typo3tests_contentelementb_select_multiple',
            'input' => '1',
            'expected' => ['1'],
        ];
        yield 'selectMultipleSideBySideWithEmptyOneValue' => [
            'fieldName' => 'typo3tests_contentelementb_select_multiple',
            'input' => '',
            'expected' => [],
        ];
    }

    #[Test]
    #[DataProvider('multipleItemsAsArrayConversionDataProvider')]
    public function multipleItemsAsArrayConversionConvertedToArray(string $fieldName, string|int $input, array $expected): void
    {
        $dummyRecord = $this->createTestRecordObject([
            $fieldName => $input,
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField($fieldName);
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        $result = $result instanceof RecordPropertyClosure ? $result->instantiate() : $result;
        self::assertSame($expected, $result);

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $fieldValue = $resolvedRecord->get($fieldName) instanceof RecordPropertyClosure ? $resolvedRecord->get($fieldName)->instantiate() : $resolvedRecord->get($fieldName);
        self::assertSame($expected, $fieldValue);
    }

    public static function jsonTypeConversionDataProvider(): \Generator
    {
        yield 'canResolveJsonObject' => [
            'fieldName' => 'typo3tests_contentelementb_json',
            'input' => '{"foo": "bar"}',
            'expected' => ['foo' => 'bar'],
        ];
        yield 'canResolveJsonArray' => [
            'fieldName' => 'typo3tests_contentelementb_json',
            'input' => '["foo", "bar"]',
            'expected' => ['foo', 'bar'],
        ];
        yield 'canResolveJsonString' => [
            'fieldName' => 'typo3tests_contentelementb_json',
            'input' => '"foo"',
            'expected' => 'foo',
        ];
        yield 'canResolveJsonInt' => [
            'fieldName' => 'typo3tests_contentelementb_json',
            'input' => '5',
            'expected' => 5,
        ];
        yield 'canResolveJsonFloat' => [
            'fieldName' => 'typo3tests_contentelementb_json',
            'input' => '5.5',
            'expected' => 5.5,
        ];
        yield 'canResolveJsonBool' => [
            'fieldName' => 'typo3tests_contentelementb_json',
            'input' => 'true',
            'expected' => true,
        ];
        yield 'canResolveJsonNull' => [
            'fieldName' => 'typo3tests_contentelementb_json',
            'input' => 'null',
            'expected' => null,
        ];
        yield 'canResolveJsonEmpty' => [
            'fieldName' => 'typo3tests_contentelementb_json',
            'input' => '',
            'expected' => null,
        ];
    }

    #[Test]
    #[DataProvider('jsonTypeConversionDataProvider')]
    public function jsonTypeConversionConvertedToArray(string $fieldName, string $input, array|string|int|float|bool|null $expected): void
    {
        $dummyRecord = $this->createTestRecordObject([
            $fieldName => $input,
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField($fieldName);
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        )->instantiate();

        self::assertSame($expected, $result);

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertSame($expected, $resolvedRecord->get($fieldName));
    }

    #[Test]
    public function jsonTypeConversionThrowsExceptionOnInvalidJson(): void
    {
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_json' => '@@@',
        ]);

        $this->expectException(RecordPropertyException::class);
        $this->expectExceptionCode(1725892139);

        $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray())->get('typo3tests_contentelementb_json');
    }

    public static function canConvertDateTimeDataProvider(): \Generator
    {
        yield 'canResolveDatetime' => [
            'fieldName' => 'typo3tests_contentelementb_datetime',
            'input' => 30,
            'expected' => '1970-01-01T00:00:30+00:00',
        ];
        yield 'canResolveDatetimeZero' => [
            'fieldName' => 'typo3tests_contentelementb_datetime',
            'input' => 0,
            'expected' => null,
        ];
        yield 'canResolveDatetimeNull' => [
            'fieldName' => 'typo3tests_contentelementb_datetime_nullable',
            'input' => 30,
            'expected' => '1970-01-01T00:00:30+00:00',
        ];
        yield 'canResolveDatetimeNullZero' => [
            'fieldName' => 'typo3tests_contentelementb_datetime_nullable',
            'input' => 0,
            'expected' => '1970-01-01T00:00:00+00:00',
        ];
        yield 'canResolveDatetimeNullNull' => [
            'fieldName' => 'typo3tests_contentelementb_datetime_nullable',
            'input' => null,
            'expected' => null,
        ];
    }

    #[Test]
    #[DataProvider('canConvertDateTimeDataProvider')]
    public function canConvertDateTime(string $fieldName, ?int $input, ?string $expected): void
    {
        $dummyRecord = $this->createTestRecordObject([
            $fieldName => $input,
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField($fieldName);
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertSame($expected, $result?->format('c'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertSame($expected, $resolvedRecord->get($fieldName)?->format('c'));
    }

    #[Test]
    public function canResolveSelectSingle(): void
    {
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_select_single' => '1',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getSubSchema('typo3tests_contentelementb')->getField('typo3tests_contentelementb_select_single');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertSame('1', $result);

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertSame('1', $resolvedRecord->get('typo3tests_contentelementb_select_single'));
    }

    #[Test]
    public function canResolveSelectRelationOneToOne(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/select_one_to_one.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_select_one_to_one' => '1',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_select_one_to_one');
        $subject = $this->get(RecordFieldTransformer::class);
        $propertyClosure = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertInstanceOf(RecordPropertyClosure::class, $propertyClosure);
        $result = $propertyClosure->instantiate();
        self::assertInstanceOf(Record::class, $result);
        self::assertSame('Record 1', $result->get('title'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_select_one_to_one');
        self::assertInstanceOf(Record::class, $resolvedRelation);
        self::assertSame('Record 1', $resolvedRelation->get('title'));
    }

    /**
     * Special case where NO Collection is returned, since the field has relationship="oneToOne"
     */
    #[Test]
    public function canResolveSelectForeignTableSingle(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/select_foreign.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_select_foreign_native' => '1',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getSubSchema('typo3tests_contentelementb')->getField('typo3tests_contentelementb_select_foreign_native');
        $subject = $this->get(RecordFieldTransformer::class);
        $propertyClosure = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertInstanceOf(RecordPropertyClosure::class, $propertyClosure);
        $result = $propertyClosure->instantiate();
        self::assertInstanceOf(Record::class, $result);
        self::assertSame('Record 1', $result->get('title'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedField = $resolvedRecord->get('typo3tests_contentelementb_select_foreign_native');
        self::assertInstanceOf(Record::class, $resolvedField);
        self::assertSame('Record 1', $resolvedField->get('title'));
    }

    /**
     * Special case where null is returned since the relation is invalid and the field has relationship="oneToOne"
     */
    #[Test]
    public function resolveSelectForeignTableSingleToNullRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/select_foreign.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_select_foreign_native' => '123',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getSubSchema('typo3tests_contentelementb')->getField('typo3tests_contentelementb_select_foreign_native');
        $subject = $this->get(RecordFieldTransformer::class);
        $propertyClosure = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertInstanceOf(RecordPropertyClosure::class, $propertyClosure);
        $result = $propertyClosure->instantiate();
        self::assertNull($result);

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        self::assertNull($resolvedRecord->get('typo3tests_contentelementb_select_foreign_native'));
    }

    /**
     * Special case where a an empty Collection is returned, since the relation is invalid
     */
    #[Test]
    public function resolveSelectForeignTableToEmptyCollection(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/select_foreign.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_select_foreign' => '123',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_select_foreign');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertInstanceOf(LazyRecordCollection::class, $result);
        self::assertCount(0, $result);

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_select_foreign');
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRelation);
        self::assertCount(0, $resolvedRelation);
    }

    /**
     * Special case where an empty Collection is returned, since the relation is invalid
     */
    #[Test]
    public function resolveSelectForeignTableMultipleToEmptyCollection(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/foreign_table_select_multiple.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_select_foreign_multiple' => '123',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_select_foreign_multiple');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertInstanceOf(LazyRecordCollection::class, $result);
        self::assertCount(0, $result);

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_select_foreign_multiple');
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRelation);
        self::assertCount(0, $resolvedRelation);
    }

    #[Test]
    public function canResolveSelectForeignTableMultiple(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/select_foreign.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_select_foreign_multiple' => '1,2',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_select_foreign_multiple');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertCount(2, $result);
        self::assertSame('Record 1', $result[0]->get('title'));
        self::assertSame('Record 2', $result[1]->get('title'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_select_foreign_multiple');
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRelation);
        self::assertCount(2, $resolvedRelation);
        self::assertSame('Record 1', $resolvedRelation[0]->get('title'));
        self::assertSame('Record 2', $resolvedRelation[1]->get('title'));
    }

    #[Test]
    public function canResolveSelectForeignTableMultipleAndSame(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/foreign_table_select_multiple.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_select_foreign_multiple' => '1,1',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_select_foreign_multiple');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertCount(2, $result);
        self::assertSame('Record 1', $result[0]->get('title'));
        self::assertSame('Collection 1', $result[0]->get('record_collection')[0]->get('text'));
        self::assertSame('Record 1', $result[1]->get('title'));
        self::assertSame('Collection 1', $result[1]->get('record_collection')[0]->get('text'));
        self::assertSame($result[0], $result[1]);

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_select_foreign_multiple');
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRelation);
        self::assertCount(2, $resolvedRelation);
        self::assertSame('Record 1', $resolvedRelation[0]->get('title'));
        self::assertSame('Collection 1', $resolvedRelation[0]->get('record_collection')[0]->get('text'));
        self::assertSame('Record 1', $resolvedRelation[1]->get('title'));
        self::assertSame('Collection 1', $resolvedRelation[1]->get('record_collection')[0]->get('text'));
        self::assertSame($resolvedRelation[0], $resolvedRelation[1]);
    }

    #[Test]
    public function canResolveSelectForeignTableRecursive(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/select_foreign_recursive.csv');
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_select_foreign' => '1',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_select_foreign');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        );

        self::assertCount(1, $result);
        self::assertInstanceOf(LazyRecordCollection::class, $result);
        $result = $result[0];
        self::assertSame('Record 1', $result->get('title'));
        self::assertCount(1, $result->get('record_collection'));
        self::assertSame('Collection 1', $result->get('record_collection')[0]->get('text'));

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_select_foreign');
        self::assertInstanceOf(LazyRecordCollection::class, $resolvedRelation);
        self::assertCount(1, $resolvedRelation);
        self::assertSame('Record 1', $resolvedRelation[0]->get('title'));
        self::assertCount(1, $resolvedRelation[0]->get('record_collection'));
        self::assertSame('Collection 1', $resolvedRelation[0]->get('record_collection')[0]->get('text'));
    }

    #[Test]
    public function canResolveFlexForm(): void
    {
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_flexfield' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="header">
                    <value index="vDEF">Header in Flex</value>
                </field>
                <field index="textarea">
                    <value index="vDEF">Text in Flex</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>',
        ]);
        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_flexfield');

        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        )->instantiate();

        self::assertIsArray($result);
        self::assertSame('Header in Flex', $result['header']);
        self::assertSame('Text in Flex', $result['textarea']);

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_flexfield');
        self::assertIsArray($resolvedRelation);
        self::assertSame('Header in Flex', $resolvedRelation['header']);
        self::assertSame('Text in Flex', $resolvedRelation['textarea']);
    }

    #[Test]
    public function canResolveFlexFormWithSheetsOtherThanDefault(): void
    {
        $dummyRecord = $this->createTestRecordObject([
            'typo3tests_contentelementb_flexfield' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sheet1">
            <language index="lDEF">
                <field index="header">
                    <value index="vDEF">Header in Flex</value>
                </field>
                <field index="textarea">
                    <value index="vDEF">Text in Flex</value>
                </field>
            </language>
        </sheet>
        <sheet index="sheet2">
            <language index="lDEF">
                <field index="link">
                    <value index="vDEF">t3://page?uid=13</value>
                </field>
                <field index="number">
                    <value index="vDEF">12</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>',
        ]);

        $fieldInformation = $this->get(TcaSchemaFactory::class)->get('tt_content')->getField('typo3tests_contentelementb_flexfield');
        $subject = $this->get(RecordFieldTransformer::class);
        $result = $subject->transformField(
            $fieldInformation,
            $dummyRecord,
            $this->get(Context::class)
        )->instantiate();

        self::assertIsArray($result);
        self::assertSame('Header in Flex', $result['header']);
        self::assertSame('Text in Flex', $result['textarea']);
        self::assertSame('t3://page?uid=13', $result['link']->instantiate()->url);
        self::assertSame('12', $result['number']);

        $resolvedRecord = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $dummyRecord->toArray());
        $resolvedRelation = $resolvedRecord->get('typo3tests_contentelementb_flexfield');
        self::assertIsArray($resolvedRelation);
        self::assertSame('Header in Flex', $resolvedRelation['header']);
        self::assertSame('Text in Flex', $resolvedRelation['textarea']);
        self::assertSame('t3://page?uid=13', $resolvedRelation['link']->instantiate()->url);
        self::assertSame('12', $resolvedRelation['number']);
    }

    protected function setWorkspaceId(int $workspaceId): void
    {
        $GLOBALS['BE_USER']->workspace = $workspaceId;
        GeneralUtility::makeInstance(Context::class)->setAspect('workspace', new WorkspaceAspect($workspaceId));
    }

    protected function getTestRecord(): array
    {
        return [
            'uid' => 260,
            'pid' => 1,
            'sys_language_uid' => 0,
            'l18n_parent' => 0,
            't3ver_wsid' => 0,
            't3ver_oid' => 0,
            't3ver_state' => 0,
            't3ver_stage' => 0,
            'crdate' => 0,
            'tstamp' => 0,
            'deleted' => 0,
            'sorting' => 0,
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'fe_group' => '',
            'editlock' => 0,
            'rowDescription' => '',
            'CType' => 'typo3tests_contentelementb',
            'colPos' => 0,
            'image' => 0,
            'typo3tests_contentelementb_collection' => 0,
            'typo3tests_contentelementb_collection2' => 0,
            'typo3tests_contentelementb_collection_external' => 0,
            'typo3tests_contentelementb_collection_recursive' => 0,
            'typo3tests_contentelementb_categories_mm' => 0,
            'typo3tests_contentelementb_categories_11' => 0,
            'typo3tests_contentelementb_categories_1m' => 0,
            'typo3tests_contentelementb_pages_relation' => 0,
            'typo3tests_contentelementb_circular_relation' => 0,
            'typo3tests_contentelementb_record_relation_recursive' => 0,
            'typo3tests_contentelementb_pages_content_relation' => '',
            'typo3tests_contentelementb_pages_mm' => 0,
            'typo3tests_contentelementb_folder' => 0,
            'typo3tests_contentelementb_folder_recursive' => 0,
            'typo3tests_contentelementb_select_single' => '',
            'typo3tests_contentelementb_select_checkbox' => '',
            'typo3tests_contentelementb_select_single_box' => '',
            'typo3tests_contentelementb_select_multiple' => '',
            'typo3tests_contentelementb_select_foreign_multiple' => '',
            'typo3tests_contentelementb_flexfield' => '',
            'typo3tests_contentelementb_json' => '',
            'typo3tests_contentelementb_datetime' => 0,
            'typo3tests_contentelementb_datetime_nullable' => null,
            'typo3tests_contentelementb_select_foreign' => '',
        ];
    }

    protected function createTestRecordObject(array $overriddenValues = []): RawRecord
    {
        $dummyRecordData = $this->getTestRecord();
        $dummyRecordData = array_replace($dummyRecordData, $overriddenValues);
        return $this->get(RecordFactory::class)
            ->createFromDatabaseRow('tt_content', $dummyRecordData)
            ->getRawRecord();
    }
}
