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

namespace TYPO3\CMS\Impexp\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Controller\ImportController;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

final class ImportControllerTest extends AbstractImportExportTestCase
{
    /**
     * The upload target of the import module must be pinned server-side
     * to the import/export temp folder.
     */
    #[Test]
    public function handleFileUploadPinsUploadTargetAndIgnoresClientProvidedTarget(): void
    {
        $importFolder = $this->createImportFolder();
        $request = $this->createUploadRequest('import.xml', 'text/xml');

        $file = $this->invokeHandleFileUpload($request, $importFolder);

        self::assertInstanceOf(File::class, $file);
        self::assertSame('xml', $file->getExtension());
        self::assertSame(
            $importFolder->getCombinedIdentifier(),
            $file->getParentFolder()->getCombinedIdentifier()
        );
        self::assertStringStartsWith('/user_upload/_temp_/importexport/', $file->getIdentifier());
        // The public storage root must not contain the uploaded file.
        self::assertFalse($file->getStorage()->hasFile('/import.xml'));
    }

    /**
     * Only "t3d" and "xml" import files may be uploaded. A file with any other extension must be
     * rejected before it is written to storage.
     */
    #[Test]
    public function handleFileUploadRejectsFilesWithDisallowedExtension(): void
    {
        $importFolder = $this->createImportFolder();
        $request = $this->createUploadRequest('import.yaml', 'application/yaml');

        $file = $this->invokeHandleFileUpload($request, $importFolder);

        self::assertNull($file);
        // Nothing must have been written, neither to the import folder nor to the storage root.
        self::assertFalse($importFolder->hasFile('import.yaml'));
        self::assertFalse($importFolder->getStorage()->hasFile('/import.yaml'));
    }

    private function createImportFolder(): Folder
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_storage.csv');
        $importFolder = GeneralUtility::makeInstance(Import::class)->getOrCreateDefaultImportExportFolder();
        self::assertNotNull($importFolder);
        return $importFolder;
    }

    private function createUploadRequest(string $clientFilename, string $clientMediaType): ServerRequestInterface
    {
        $uploadTmp = GeneralUtility::tempnam('impexp_upload_');
        file_put_contents($uploadTmp, '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . "\n<T3RecordDocument></T3RecordDocument>\n");
        $this->testFilesToDelete[] = $uploadTmp;
        $uploadedFile = new UploadedFile($uploadTmp, (int)filesize($uploadTmp), \UPLOAD_ERR_OK, $clientFilename, $clientMediaType);

        return (new ServerRequest('https://example.com/typo3/record/importexport/import', 'POST'))
            ->withParsedBody([
                '_upload' => '1',
                'overwriteExistingFiles' => 'replace',
                // this data is supposed to be ignored, since the target folder is
                // declared in the ImportController
                'file' => ['upload' => [1 => ['target' => '1:/', 'data' => '1']]],
            ])
            ->withUploadedFiles(['upload_1' => $uploadedFile]);
    }

    private function invokeHandleFileUpload(ServerRequestInterface $request, Folder $importFolder): ?File
    {
        $controller = $this->get(ImportController::class);
        $view = $this->get(ModuleTemplateFactory::class)->create($GLOBALS['TYPO3_REQUEST']);
        $method = new \ReflectionMethod($controller, 'handleFileUpload');
        return $method->invoke($controller, $request, $importFolder, $view);
    }
}
