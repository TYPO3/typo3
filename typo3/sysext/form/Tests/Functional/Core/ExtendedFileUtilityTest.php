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

namespace TYPO3\CMS\Form\Tests\Functional\Core;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Tests\Functional\Fixtures\ResourceStorageUploadMock;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ExtendedFileUtilityTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

        // ensure, temporary uploaded files are purged again
        // @todo move this to the testing framework (which only reinitialized files for the first run)
        $fileCommandsPath = $this->instancePath . '/fileadmin/file-commands';
        if (is_dir($fileCommandsPath)) {
            GeneralUtility::rmdir($fileCommandsPath, true);
        }
        GeneralUtility::mkdir($fileCommandsPath);

        // Configure ResourceStorage mock to overwrite the `is_uploaded_file`
        // check which can not be mocked
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][ResourceStorage::class] = [
            'className' => ResourceStorageUploadMock::class,
        ];
        GeneralUtility::flushInternalRuntimeCaches();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][ResourceStorage::class]);
        GeneralUtility::flushInternalRuntimeCaches();
        parent::tearDown();
    }

    public static function fileCommandsAreProcessedDataProvider(): iterable
    {
        yield 'protected file suffix (case-insensitive storage)' => [
            'caseSensitiveFileStorage' => false,
            'fileCommands' => [
                'upload' => [
                    1 => ['target' => '1:/file-commands/', 'data' => 1],
                ],
            ],
            'uploadableFiles' => [
                'upload_1' => [
                    __DIR__ . '/../Fixtures/Files/temp-lowercase.form.yaml',
                    __DIR__ . '/../Fixtures/Files/temp-uppercase.FORM.YAML',
                ],
            ],
            'expectedResult' => [
                'upload' => [
                    // none of the uploaded files is supposed to be accepted
                    0 => [],
                ],
            ],
        ];
        yield 'protected file suffix (case-sensitive storage)' => [
            'caseSensitiveFileStorage' => true,
            'fileCommands' => [
                'upload' => [
                    1 => ['target' => '1:/file-commands/', 'data' => 1],
                ],
            ],
            'uploadableFiles' => [
                'upload_1' => [
                    __DIR__ . '/../Fixtures/Files/temp-lowercase.form.yaml',
                    __DIR__ . '/../Fixtures/Files/temp-uppercase.FORM.YAML',
                ],
            ],
            'expectedResult' => [
                'upload' => [
                    // none of the uploaded files is supposed to be accepted
                    0 => [],
                ],
            ],
        ];
        yield 'regular-file (case-sensitive storage)' => [
            'caseSensitiveFileStorage' => true,
            'fileCommands' => [
                'upload' => [
                    1 => ['target' => '1:/file-commands/', 'data' => 1],
                ],
            ],
            'uploadableFiles' => [
                'upload_1' => [
                    __DIR__ . '/../Fixtures/Files/regular-file.txt',
                ],
            ],
            'expectedResult' => [
                'upload' => [
                    // none of the uploaded files is supposed to be accepted
                    0 => ['1:/file-commands/regular-file.txt'],
                ],
            ],
        ];
    }

    /**
     * Specific implementation for EXT:form of
     * \TYPO3\CMS\Core\Tests\Functional\Utility\File\ExtendedFileUtilityTest::fileCommandsAreProcessed
     */
    #[Test]
    #[DataProvider('fileCommandsAreProcessedDataProvider')]
    public function fileCommandsAreProcessed(bool $caseSensitiveFileStorage, array $fileCommands, array $uploadableFiles, array $expectedResult): void
    {
        $this->createDefaultFileStorage($caseSensitiveFileStorage);
        $uploadedFiles = array_map(
            fn(array|string $data): array|UploadedFile => is_array($data)
                ? array_map($this->createUploadedFile(...), $data)
                : $this->createUploadedFile($data),
            $uploadableFiles
        );

        $this->mockFilesArrayFromUploadedFiles($uploadedFiles);

        $extendedFileUtility = new ExtendedFileUtility();
        $extendedFileUtility->setExistingFilesConflictMode(DuplicationBehavior::getDefaultDuplicationBehaviour());
        $extendedFileUtility->start($fileCommands);
        $result = $extendedFileUtility->processData();

        self::assertSame($expectedResult, $this->normalizeProcessedDataResult($result));
    }

    /**
     * @param array<string, UploadedFile|list<UploadedFile>> $uploadedFiles
     */
    private function mockFilesArrayFromUploadedFiles(array $uploadedFiles): void
    {
        $_FILES = [];
        foreach ($uploadedFiles as $name => $files) {
            if ($files instanceof UploadedFile) {
                $file = $files;
                $_FILES[$name]['name'] = $file->getClientFilename();
                $_FILES[$name]['tmp_name'] = $file->getTemporaryFileName();
                $_FILES[$name]['error'] = $file->getError();
                $_FILES[$name]['size'] = $file->getSize();
                $_FILES[$name]['type'] = '';
            } else {
                foreach ($files as $index => $file) {
                    $_FILES[$name]['name'][$index] = $file->getClientFilename();
                    $_FILES[$name]['tmp_name'][$index] = $file->getTemporaryFileName();
                    $_FILES[$name]['error'][$index] = $file->getError();
                    $_FILES[$name]['size'][$index] = $file->getSize();
                    $_FILES[$name]['type'][$index] = '';
                }
            }
        }
    }

    private function createDefaultFileStorage(bool $caseSensitive): void
    {
        $caseSensitiveValue = $caseSensitive ? 1 : 0;
        $configuration = <<<XML
            <?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
            <data>
            <sheet index="sDEF">
            <language index="lDEF">
            <field index="basePath"><value index="vDEF">fileadmin/</value></field>
            <field index="pathType"><value index="vDEF">relative</value></field>
            <field index="caseSensitive"><value index="vDEF">{$caseSensitiveValue}</value></field>
            </language>
            </sheet>
            </data>
            </T3FlexForms>
            XML;
        $this->get(ConnectionPool::class)
            ->getConnectionForTable('sys_file_storage')
            ->insert('sys_file_storage', [
                'uid' => 1,
                'pid' => 0,
                'name' => 'fileadmin/ (auto-created)',
                'processingfolder' => 'temp/assets/_processed_/',
                'driver' => 'Local',
                'is_browsable' => 1,
                'is_public' => 1,
                'is_writable' => 1,
                'is_online' => 1,
                'configuration' => $configuration,
            ]);
    }

    private function createUploadedFile(string $filePath): UploadedFile
    {
        $size = filesize($filePath);
        $tempPath = GeneralUtility::tempnam('extended-file-utility-test');
        GeneralUtility::writeFile($tempPath, file_get_contents($filePath));
        // @todo use resource streams of `UploadedFile`, once it's fully supported in FAL
        return new UploadedFile($tempPath, $size, UPLOAD_ERR_OK, basename($filePath));
    }

    private function normalizeProcessedDataResult(array $result): array
    {
        return array_map(
            static fn(array $actionResult): array => array_map(
                static fn(array|File|null $fileResult): array|string|null => is_array($fileResult)
                    ? array_map(static fn(File $file): string => $file->getCombinedIdentifier(), $fileResult)
                    : $fileResult?->getCombinedIdentifier(),
                $actionResult
            ),
            $result
        );
    }
}
