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

namespace TYPO3\CMS\Core\Tests\Functional\Utility\File;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ExtendedFileUtilityTest extends FunctionalTestCase
{
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Utility/File/Fixtures/Folders/' => 'fileadmin/',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/sys_refindex.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/sys_file.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

        // ensure, temporary uploaded files are purged again
        // @todo move this to the testing framework (which only reinitialized files for the first run)
        $fileCommandsPath = $this->instancePath . '/fileadmin/file-commands';
        if (is_dir($fileCommandsPath)) {
            GeneralUtility::rmdir($fileCommandsPath, true);
        }
        GeneralUtility::mkdir($fileCommandsPath);
        GeneralUtility::writeFile(
            $fileCommandsPath . '/existing.png',
            file_get_contents(__DIR__ . '/Fixtures/temp.png')
        );
    }

    #[Test]
    public function folderHasFilesInUseReturnsTrueIfItHasFilesInUse(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        $resourceStorage = $storageRepository->getDefaultStorage();
        $folder = $resourceStorage->getFolder('FolderWithUsedFile');

        $extendedFileUtility = new ExtendedFileUtility();
        $result = $extendedFileUtility->folderHasFilesInUse($folder);

        self::assertTrue($result);
    }

    #[Test]
    public function folderHasFilesInUseReturnsFalseIfItHasNoFilesInUse(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        $resourceStorage = $storageRepository->getDefaultStorage();
        $folder = $resourceStorage->getFolder('FolderWithUnusedFile');

        $extendedFileUtility = new ExtendedFileUtility();
        $result = $extendedFileUtility->folderHasFilesInUse($folder);

        self::assertFalse($result);
    }

    #[Test]
    public function folderHasFilesInUseReturnsFalseIfItHasNoFiles(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        $resourceStorage = $storageRepository->getDefaultStorage();
        $folder = $resourceStorage->createFolder('EmptyFolder');

        $extendedFileUtility = new ExtendedFileUtility();
        $result = $extendedFileUtility->folderHasFilesInUse($folder);

        self::assertFalse($result);
    }

    public static function fileCommandsAreProcessedDataProvider(): \Generator
    {
        yield 'upload single file' => [
            'fileCommands' => [
                'upload' => [
                    1 => ['target' => '1:/file-commands/', 'data' => 1],
                ],
            ],
            'uploadableFiles' => [
                'upload_1' => __DIR__ . '/Fixtures/temp.png',
            ],
            'expectedResult' => [
                'upload' => [
                    0 => ['1:/file-commands/temp.png'],
                ],
            ],
        ];
        yield 'upload multiple single files' => [
            'fileCommands' => [
                'upload' => [
                    1 => ['target' => '1:/file-commands/', 'data' => 1],
                    2 => ['target' => '1:/file-commands/', 'data' => 2],
                ],
            ],
            'uploadableFiles' => [
                'upload_1' => __DIR__ . '/Fixtures/temp.png',
                'upload_2' => __DIR__ . '/Fixtures/temp.jpg',
            ],
            'expectedResult' => [
                'upload' => [
                    0 => ['1:/file-commands/temp.png'],
                    1 => ['1:/file-commands/temp.jpg'],
                ],
            ],
        ];
        yield 'upload multiple files at once' => [
            'fileCommands' => [
                'upload' => [
                    1 => ['target' => '1:/file-commands/', 'data' => 1],
                ],
            ],
            'uploadableFiles' => [
                'upload_1' => [
                    __DIR__ . '/Fixtures/temp.png',
                    __DIR__ . '/Fixtures/temp.jpg',
                ],
            ],
            'expectedResult' => [
                'upload' => [
                    0 => [
                        '1:/file-commands/temp.png',
                        '1:/file-commands/temp.jpg',
                    ],
                ],
            ],
        ];
        yield 'rename file to same extension' => [
            'fileCommands' => [
                'rename' => [
                    1 => ['target' => 'renamed.png', 'data' => '1:/file-commands/existing.png'],
                ],
            ],
            'uploadableFiles' => [],
            'expectedResult' => [
                'rename' => [
                    0 => '1:/file-commands/renamed.png',
                ],
            ],
        ];
        yield 'rename file to inconsistent extension' => [
            'fileCommands' => [
                'rename' => [
                    1 => ['target' => 'renamed.jpg', 'data' => '1:/file-commands/existing.png'],
                ],
            ],
            'uploadableFiles' => [],
            'expectedResult' => [
                'rename' => [
                    // expected to fail, since it's denied by `ResourceConsistencyService`
                    0 => null,
                ],
            ],
        ];
        yield 'replace file using same extension, keeping name' => [
            'fileCommands' => [
                'replace' => [
                    1 => ['uid' => '1:/file-commands/existing.png', 'data' => '1', 'keepFilename' => true],
                ],
            ],
            'uploadableFiles' => [
                'replace_1' => __DIR__ . '/Fixtures/temp.png',
            ],
            'expectedResult' => [
                'replace' => [
                    0 => ['1:/file-commands/existing.png'],
                ],
            ],
        ];
        yield 'replace file using same extension, not keeping name' => [
            'fileCommands' => [
                'replace' => [
                    1 => ['uid' => '1:/file-commands/existing.png', 'data' => '1', 'keepFilename' => false],
                ],
            ],
            'uploadableFiles' => [
                'replace_1' => __DIR__ . '/Fixtures/temp.png',
            ],
            'expectedResult' => [
                'replace' => [
                    0 => ['1:/file-commands/temp.png'],
                ],
            ],
        ];
        yield 'replace file using inconsistent extension, keeping name' => [
            'fileCommands' => [
                'replace' => [
                    1 => ['uid' => '1:/file-commands/existing.png', 'data' => '1', 'keepFilename' => true],
                ],
            ],
            'uploadableFiles' => [
                'replace_1' => __DIR__ . '/Fixtures/temp.jpg',
            ],
            'expectedResult' => [
                'replace' => [
                    0 => ['1:/file-commands/existing.jpg'],
                ],
            ],
        ];
        yield 'replace file using inconsistent extension, not keeping name' => [
            'fileCommands' => [
                'replace' => [
                    1 => ['uid' => '1:/file-commands/existing.png', 'data' => '1', 'keepFilename' => true],
                ],
            ],
            'uploadableFiles' => [
                'replace_1' => __DIR__ . '/Fixtures/temp.jpg',
            ],
            'expectedResult' => [
                'replace' => [
                    0 => ['1:/file-commands/existing.jpg'],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('fileCommandsAreProcessedDataProvider')]
    public function fileCommandsAreProcessed(array $fileCommands, array $uploadableFiles, array $expectedResult): void
    {
        $uploadedFiles = array_map(
            fn(mixed $data): array|UploadedFile => is_array($data)
                ? array_map($this->createUploadedFile(...), $data)
                : $this->createUploadedFile($data),
            $uploadableFiles
        );

        $extendedFileUtility = new ExtendedFileUtility();
        $extendedFileUtility->start($fileCommands, $uploadedFiles);
        $result = $extendedFileUtility->processData();

        self::assertSame($expectedResult, $this->normalizeProcessedDataResult($result));
    }

    private function normalizeProcessedDataResult(array $result): array
    {
        return array_map(
            static fn(array $actionResult): array => array_map(
                static fn(null|array|File $fileResult): null|array|string => is_array($fileResult)
                    ? array_map(static fn(File $file): string => $file->getCombinedIdentifier(), $fileResult)
                    : $fileResult?->getCombinedIdentifier(),
                $actionResult
            ),
            $result
        );
    }

    private function createUploadedFile(string $filePath): UploadedFile
    {
        $size = filesize($filePath);
        $tempPath = GeneralUtility::tempnam('extended-file-utility-test');
        GeneralUtility::writeFile($tempPath, file_get_contents($filePath));
        // @todo use resource streams of `UploadedFile`, once it's fully supported in FAL
        return new UploadedFile($tempPath, $size, UPLOAD_ERR_OK, basename($filePath));
    }
}
