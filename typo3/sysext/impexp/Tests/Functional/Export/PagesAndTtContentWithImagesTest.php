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

namespace TYPO3\CMS\Impexp\Tests\Functional\Export;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

final class PagesAndTtContentWithImagesTest extends AbstractImportExportTestCase
{
    protected array $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/tt_content-with-image.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_metadata.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_reference.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_storage.csv');
    }

    #[Test]
    public function exportPagesAndRelatedTtContentWithImages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.csv');

        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $subject->injectResourceFactory($this->get(ResourceFactory::class));
        $this->compileExportPagesAndRelatedTtContentWithImages($subject);
        $out = $subject->render();

        self::assertFalse($subject->hasErrors());

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-image.xml',
            $out
        );
    }

    #[Test]
    public function exportPagesAndRelatedTtContentWithImagesFromCorruptSysFileRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_corrupt.csv');

        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $subject->injectResourceFactory($this->get(ResourceFactory::class));
        $this->compileExportPagesAndRelatedTtContentWithImages($subject);
        $out = $subject->render();

        $expectedErrors = [
            'The SHA-1 file hash of 1:/user_upload/typo3_image2.jpg is not up-to-date in the index! ' .
            'The file was added based on the current file hash.',
        ];
        $errors = $subject->getErrorLog();
        self::assertSame($expectedErrors, $errors);

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-corrupt-image.xml',
            $out
        );
    }

    #[Test]
    public function exportPagesAndRelatedTtContentWithImagesButNotIncluded(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.csv');

        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $subject->injectResourceFactory($this->get(ResourceFactory::class));
        $subject->setSaveFilesOutsideExportFile(true);
        $this->compileExportPagesAndRelatedTtContentWithImages($subject);
        $out = $subject->render();

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-image-but-not-included.xml',
            $out
        );

        $temporaryFilesDirectory = $subject->getOrCreateTemporaryFolderName();
        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', $temporaryFilesDirectory . '/' . 'da9acdf1e105784a57bbffec9520969578287797');
    }

    #[Test]
    public function exportPagesAndRelatedTtContentWithImagesButNotIncludedAndInvalidHash(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_invalid_hash.csv');

        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $subject->injectResourceFactory($this->get(ResourceFactory::class));
        $subject->setSaveFilesOutsideExportFile(true);
        $this->compileExportPagesAndRelatedTtContentWithImages($subject);
        $out = $subject->render();

        $expectedErrors = [
            'The SHA-1 file hash of 1:/user_upload/typo3_image2.jpg is not up-to-date in the index! ' .
            'The file was added based on the current file hash.',
        ];
        $errors = $subject->getErrorLog();
        self::assertSame($expectedErrors, $errors);

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-image-but-not-included.xml',
            $out
        );

        $temporaryFilesDirectory = $subject->getOrCreateTemporaryFolderName();
        self::assertFileEquals(
            __DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg',
            $temporaryFilesDirectory . '/' . 'da9acdf1e105784a57bbffec9520969578287797'
        );
    }

    /**
     * Add default set of records to export
     */
    protected function compileExportPagesAndRelatedTtContentWithImages(Export $subject): void
    {
        $recordTypesIncludeFields =
            [
                'pages' => [
                    'title',
                    'deleted',
                    'doktype',
                    'hidden',
                    'perms_everybody',
                ],
                'tt_content' => [
                    'CType',
                    'header',
                    'header_link',
                    'deleted',
                    'hidden',
                    'image',
                    't3ver_oid',
                ],
                'sys_file_reference' => [
                    'uid_local',
                    'uid_foreign',
                    'tablenames',
                    'fieldname',
                    'sorting_foreign',
                    'title',
                    'description',
                    'alternative',
                    'link',
                ],
                'sys_file' => [
                    'storage',
                    'type',
                    'metadata',
                    'identifier',
                    'identifier_hash',
                    'folder_hash',
                    'mime_type',
                    'name',
                    'sha1',
                    'size',
                    'creation_date',
                    'modification_date',
                ],
                'sys_file_storage' => [
                    'name',
                    'description',
                    'driver',
                    'configuration',
                    'is_default',
                    'is_browsable',
                    'is_public',
                    'is_writable',
                    'is_online',
                ],
                'sys_file_metadata' => [
                    'title',
                    'width',
                    'height',
                    'description',
                    'alternative',
                    'file',
                    'sys_language_uid',
                    'l10n_parent',
                ],
            ]
        ;

        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRecordTypesIncludeFields($recordTypesIncludeFields);
        $subject->setRelOnlyTables([
            'sys_file',
            'sys_file_metadata',
            'sys_file_storage',
        ]);
        $subject->process();
    }
}
