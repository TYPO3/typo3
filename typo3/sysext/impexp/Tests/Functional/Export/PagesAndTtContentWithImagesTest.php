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
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

final class PagesAndTtContentWithImagesTest extends AbstractImportExportTestCase
{
    protected array $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Extensions/template_extension',
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

        $subject = $this->get(Export::class);
        $this->compileExportPagesAndRelatedTtContentWithImages($subject);
        $out = $subject->render();

        self::assertFalse($subject->hasErrors());

        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-image.xml',
            $out
        );
    }

    #[Test]
    public function exportPagesAndRelatedTtContentWithImagesFromCorruptSysFileRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_corrupt.csv');

        $subject = $this->get(Export::class);
        $this->compileExportPagesAndRelatedTtContentWithImages($subject);
        $out = $subject->render();

        $expectedErrors = [
            'The SHA-1 file hash of 1:/user_upload/typo3_image2.jpg is not up-to-date in the index! '
            . 'The file was added based on the current file hash.',
        ];
        $errors = $subject->getErrorLog();
        self::assertSame($expectedErrors, $errors);

        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-corrupt-image.xml',
            $out
        );
    }

    #[Test]
    public function exportPagesAndRelatedTtContentWithImagesButNotIncluded(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.csv');

        $subject = $this->get(Export::class);
        $subject->setSaveFilesOutsideExportFile(true);
        $this->compileExportPagesAndRelatedTtContentWithImages($subject);
        $out = $subject->render();

        self::assertXmlStringEqualsXmlFile(
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

        $subject = $this->get(Export::class);
        $subject->setSaveFilesOutsideExportFile(true);
        $this->compileExportPagesAndRelatedTtContentWithImages($subject);
        $out = $subject->render();

        $expectedErrors = [
            'The SHA-1 file hash of 1:/user_upload/typo3_image2.jpg is not up-to-date in the index! '
            . 'The file was added based on the current file hash.',
        ];
        $errors = $subject->getErrorLog();
        self::assertSame($expectedErrors, $errors);

        self::assertXmlStringEqualsXmlFile(
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
    private function compileExportPagesAndRelatedTtContentWithImages(Export $subject): void
    {
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelOnlyTables([
            'sys_file',
            'sys_file_metadata',
            'sys_file_storage',
        ]);
        $subject->process();
    }
}
