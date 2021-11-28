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

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;

class PagesAndTtContentWithImagesTest extends AbstractImportExportTestCase
{
    /**
     * @var array
     */
    protected array $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/tt_content-with-image.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_metadata.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_reference.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_storage.xml');
    }

    /**
     * @test
     */
    public function exportPagesAndRelatedTtContentWithImages(): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.xml');

        /** @var Export|MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(Export::class, ['setMetaData']);
        $this->compileExportPagesAndRelatedTtContentWithImages($subject);
        $out = $subject->render();

        self::assertFalse($subject->hasErrors());

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-image.xml',
            $out
        );
    }

    /**
     * @test
     */
    public function exportPagesAndRelatedTtContentWithImagesFromCorruptSysFileRecord(): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_corrupt.xml');

        /** @var Export|MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(Export::class, ['setMetaData']);
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

    /**
     * @test
     */
    public function exportPagesAndRelatedTtContentWithImagesButNotIncluded(): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.xml');

        /** @var Export|MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(Export::class, ['setMetaData']);
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

    /**
     * @test
     */
    public function exportPagesAndRelatedTtContentWithImagesButNotIncludedAndInvalidHash(): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_invalid_hash.xml');

        /** @var Export|MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(Export::class, ['setMetaData']);
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
     *
     * @param $subject Export
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
                    'table_local',
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
