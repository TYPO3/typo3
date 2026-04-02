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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Property\TypeConverter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\CMS\Form\Security\HashScope;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for UploadedFileReferenceConverter with multiple file upload support
 */
final class MultipleFileUploadTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected function setUp(): void
    {
        parent::setUp();

        $uploadPath = $this->instancePath . '/fileadmin/user_upload/';
        GeneralUtility::mkdir_deep($uploadPath);
    }

    protected function tearDown(): void
    {
        $uploadPath = $this->instancePath . '/fileadmin/user_upload/';
        if (is_dir($uploadPath)) {
            GeneralUtility::rmdir($uploadPath, true);
        }

        parent::tearDown();
    }

    #[Test]
    public function convertFromReturnsNullForEmptyString(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $configuration = new PropertyMappingConfiguration();

        $result = $subject->convertFrom('', FileReference::class, [], $configuration);

        self::assertNull($result);
    }

    #[Test]
    public function convertFromReturnsNullForEmptyArray(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $configuration = new PropertyMappingConfiguration();

        $result = $subject->convertFrom([], FileReference::class, [], $configuration);

        self::assertNull($result);
    }

    #[Test]
    public function convertFromReturnsNullForNoFileError(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $source = [
            'error' => \UPLOAD_ERR_NO_FILE,
        ];

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(
            UploadedFileReferenceConverter::class,
            UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER,
            '1:/user_upload/'
        );

        $result = $subject->convertFrom($source, FileReference::class, [], $configuration);

        self::assertNull($result);
    }

    public static function uploadErrorCodeDataProvider(): \Generator
    {
        yield 'UPLOAD_ERR_INI_SIZE' => [\UPLOAD_ERR_INI_SIZE];
        yield 'UPLOAD_ERR_FORM_SIZE' => [\UPLOAD_ERR_FORM_SIZE];
        yield 'UPLOAD_ERR_PARTIAL' => [\UPLOAD_ERR_PARTIAL];
        yield 'UPLOAD_ERR_NO_TMP_DIR' => [\UPLOAD_ERR_NO_TMP_DIR];
        yield 'UPLOAD_ERR_CANT_WRITE' => [\UPLOAD_ERR_CANT_WRITE];
        yield 'UPLOAD_ERR_EXTENSION' => [\UPLOAD_ERR_EXTENSION];
    }

    #[DataProvider('uploadErrorCodeDataProvider')]
    #[Test]
    public function convertFromReturnsErrorForVariousUploadErrors(int $errorCode): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $source = [
            'error' => $errorCode,
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/phptest',
            'size' => 1024,
        ];

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(
            UploadedFileReferenceConverter::class,
            UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER,
            '1:/user_upload/'
        );

        $result = $subject->convertFrom($source, FileReference::class, [], $configuration);

        self::assertInstanceOf(Error::class, $result);
    }

    #[Test]
    public function convertFromReturnsSingleFileReferenceForSingleUpload(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);

        $testFilePath = $this->createTestFile('test-single.pdf');

        $source = [
            'error' => \UPLOAD_ERR_OK,
            'name' => 'test-single.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $testFilePath,
            'size' => filesize($testFilePath),
        ];

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-single',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
            ]
        );

        $result = $subject->convertFrom($source, FileReference::class, [], $configuration);

        self::assertInstanceOf(FileReference::class, $result);
        self::assertSame('test-single.pdf', $result->getOriginalResource()->getOriginalFile()->getName());
    }

    #[Test]
    public function convertFromReturnsSingleFileReferenceForExistingSubmittedFile(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $hashService = $this->get(HashService::class);

        // Upload a file first
        $testFilePath = $this->createTestFile('test-single-existing.pdf');

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-single-existing',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
            ]
        );

        $uploadSource = [
            'error' => \UPLOAD_ERR_OK,
            'name' => 'test-single-existing.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $testFilePath,
            'size' => filesize($testFilePath),
        ];

        /** @var FileReference $uploadedFile */
        $uploadedFile = $subject->convertFrom($uploadSource, FileReference::class, [], $configuration);
        $fileUid = $uploadedFile->getOriginalResource()->getOriginalFile()->getUid();

        $resourcePointer = $hashService->appendHmac(
            'file:' . $fileUid,
            HashScope::ResourcePointer->prefix()
        );

        // Re-submit with only the existing file via __submittedFiles, no new upload
        $source = [
            '__submittedFiles' => [
                0 => [
                    'submittedFile' => [
                        'resourcePointer' => $resourcePointer,
                    ],
                ],
            ],
        ];

        $result = $subject->convertFrom($source, FileReference::class, [], $configuration);

        self::assertInstanceOf(FileReference::class, $result);
        self::assertSame('test-single-existing.pdf', $result->getOriginalResource()->getOriginalFile()->getName());
    }

    #[Test]
    public function convertFromReplacesSingleExistingFileWithNewUploadViaSubmittedFiles(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $hashService = $this->get(HashService::class);

        // Upload a file first
        $testFilePath = $this->createTestFile('test-single-old.pdf');

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-single-replace',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
            ]
        );

        $uploadSource = [
            'error' => \UPLOAD_ERR_OK,
            'name' => 'test-single-old.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $testFilePath,
            'size' => filesize($testFilePath),
        ];

        /** @var FileReference $uploadedFile */
        $uploadedFile = $subject->convertFrom($uploadSource, FileReference::class, [], $configuration);
        $fileUid = $uploadedFile->getOriginalResource()->getOriginalFile()->getUid();

        $resourcePointer = $hashService->appendHmac(
            'file:' . $fileUid,
            HashScope::ResourcePointer->prefix()
        );

        // Re-submit with existing file in __submittedFiles + new upload replacing it
        $newFilePath = $this->createTestFile('test-single-new.pdf');
        $source = [
            '__submittedFiles' => [
                0 => [
                    'submittedFile' => [
                        'resourcePointer' => $resourcePointer,
                    ],
                ],
            ],
            'error' => \UPLOAD_ERR_OK,
            'name' => 'test-single-new.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $newFilePath,
            'size' => filesize($newFilePath),
        ];

        $result = $subject->convertFrom($source, FileReference::class, [], $configuration);

        self::assertInstanceOf(FileReference::class, $result);
        self::assertSame('test-single-new.pdf', $result->getOriginalResource()->getOriginalFile()->getName());
    }

    #[Test]
    public function convertFromReturnsObjectStorageForMultipleUploads(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);

        $testFilePath1 = $this->createTestFile('test-multi-1.pdf');
        $testFilePath2 = $this->createTestFile('test-multi-2.pdf');

        $source = [
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-multi-1.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $testFilePath1,
                'size' => filesize($testFilePath1),
            ],
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-multi-2.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $testFilePath2,
                'size' => filesize($testFilePath2),
            ],
        ];

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-multi',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
            ]
        );

        $result = $subject->convertFrom($source, ObjectStorage::class, [], $configuration);

        self::assertInstanceOf(ObjectStorage::class, $result);
        self::assertCount(2, $result);

        $fileNames = [];
        foreach ($result as $fileReference) {
            self::assertInstanceOf(FileReference::class, $fileReference);
            $fileNames[] = $fileReference->getOriginalResource()->getOriginalFile()->getName();
        }

        self::assertContains('test-multi-1.pdf', $fileNames);
        self::assertContains('test-multi-2.pdf', $fileNames);
    }

    #[Test]
    public function convertFromSkipsFilesWithNoFileErrorInMultipleUploads(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);

        $testFilePath = $this->createTestFile('test-valid.pdf');

        $source = [
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-valid.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $testFilePath,
                'size' => filesize($testFilePath),
            ],
            [
                'error' => \UPLOAD_ERR_NO_FILE,
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'size' => 0,
            ],
        ];

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-skip',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
            ]
        );

        $result = $subject->convertFrom($source, ObjectStorage::class, [], $configuration);

        self::assertInstanceOf(ObjectStorage::class, $result);
        self::assertCount(1, $result);
    }

    #[Test]
    public function convertFromReturnsEmptyObjectStorageWhenAllUploadsHaveNoFileError(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);

        $source = [
            [
                'error' => \UPLOAD_ERR_NO_FILE,
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'size' => 0,
            ],
            [
                'error' => \UPLOAD_ERR_NO_FILE,
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'size' => 0,
            ],
        ];

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(
            UploadedFileReferenceConverter::class,
            UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER,
            '1:/user_upload/'
        );

        $result = $subject->convertFrom($source, ObjectStorage::class, [], $configuration);

        self::assertInstanceOf(ObjectStorage::class, $result);
        self::assertCount(0, $result);
    }

    #[Test]
    public function convertFromHandlesSingleFileInArrayFormat(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);

        $testFilePath = $this->createTestFile('test-array-single.pdf');

        $source = [
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-array-single.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $testFilePath,
                'size' => filesize($testFilePath),
            ],
        ];

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-array-single',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
            ]
        );

        $result = $subject->convertFrom($source, ObjectStorage::class, [], $configuration);

        self::assertInstanceOf(ObjectStorage::class, $result);
        self::assertCount(1, $result);
    }

    #[Test]
    public function convertFromHandlesMultipleFilesFromFixtures(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);

        $testFiles = ['test-attachment-1.pdf', 'test-attachment-2.pdf', 'test-attachment-3.pdf'];
        $source = [];

        foreach ($testFiles as $filename) {
            $fixtureFile = __DIR__ . '/Fixtures/' . $filename;

            $testFilePath = $this->createTestFileFromFixture($filename, $fixtureFile);
            $source[] = [
                'error' => \UPLOAD_ERR_OK,
                'name' => $filename,
                'type' => 'application/pdf',
                'tmp_name' => $testFilePath,
                'size' => filesize($testFilePath),
            ];
        }

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-fixtures',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
            ]
        );

        $result = $subject->convertFrom($source, ObjectStorage::class, [], $configuration);

        self::assertInstanceOf(ObjectStorage::class, $result);
        self::assertCount(3, $result);

        $uploadedFilenames = [];
        foreach ($result as $fileReference) {
            self::assertInstanceOf(FileReference::class, $fileReference);
            $uploadedFilenames[] = $fileReference->getOriginalResource()->getOriginalFile()->getName();
        }

        self::assertContains('test-attachment-1.pdf', $uploadedFilenames);
        self::assertContains('test-attachment-2.pdf', $uploadedFilenames);
        self::assertContains('test-attachment-3.pdf', $uploadedFilenames);
    }

    #[Test]
    public function convertFromRemovesFilesWithValidDeleteSignature(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $hashService = $this->get(HashService::class);

        $testFilePath1 = $this->createTestFile('test-delete-1.pdf');
        $testFilePath2 = $this->createTestFile('test-delete-2.pdf');
        $testFilePath3 = $this->createTestFile('test-delete-3.pdf');

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-delete',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
                UploadedFileReferenceConverter::CONFIGURATION_ALLOW_REMOVAL => true,
            ]
        );

        // First upload 3 files
        $uploadSource = [
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-delete-1.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $testFilePath1,
                'size' => filesize($testFilePath1),
            ],
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-delete-2.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $testFilePath2,
                'size' => filesize($testFilePath2),
            ],
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-delete-3.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $testFilePath3,
                'size' => filesize($testFilePath3),
            ],
        ];

        /** @var ObjectStorage<FileReference> $uploadedFiles */
        $uploadedFiles = $subject->convertFrom($uploadSource, ObjectStorage::class, [], $configuration);
        self::assertCount(3, $uploadedFiles);

        $fileRefs = $uploadedFiles->toArray();
        $fileUid1 = $fileRefs[0]->getOriginalResource()->getOriginalFile()->getUid();
        $fileUid2 = $fileRefs[1]->getOriginalResource()->getOriginalFile()->getUid();
        $fileUid3 = $fileRefs[2]->getOriginalResource()->getOriginalFile()->getUid();

        $resourcePointer1 = $hashService->appendHmac('file:' . $fileUid1, HashScope::ResourcePointer->prefix());
        $resourcePointer2 = $hashService->appendHmac('file:' . $fileUid2, HashScope::ResourcePointer->prefix());
        $resourcePointer3 = $hashService->appendHmac('file:' . $fileUid3, HashScope::ResourcePointer->prefix());

        // Delete file at index 1 (second file)
        $deleteSignature = $this->createSignedDeleteFileValue(1, 'testProperty', $fileUid2);

        $source = [
            '__deleteFile' => [$deleteSignature],
            '__submittedFiles' => [
                0 => ['submittedFile' => ['resourcePointer' => $resourcePointer1]],
                1 => ['submittedFile' => ['resourcePointer' => $resourcePointer2]],
                2 => ['submittedFile' => ['resourcePointer' => $resourcePointer3]],
            ],
        ];

        $result = $subject->convertFrom($source, ObjectStorage::class, [], $configuration);

        self::assertInstanceOf(ObjectStorage::class, $result);
        self::assertCount(2, $result);

        $uploadedFilenames = [];
        foreach ($result as $fileReference) {
            self::assertInstanceOf(FileReference::class, $fileReference);
            $uploadedFilenames[] = $fileReference->getOriginalResource()->getOriginalFile()->getName();
        }

        self::assertContains('test-delete-1.pdf', $uploadedFilenames);
        self::assertNotContains('test-delete-2.pdf', $uploadedFilenames);
        self::assertContains('test-delete-3.pdf', $uploadedFilenames);
    }

    #[Test]
    public function convertFromIgnoresDeleteSignatureWithInvalidHmac(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);

        $testFilePath1 = $this->createTestFile('test-invalid-1.pdf');
        $testFilePath2 = $this->createTestFile('test-invalid-2.pdf');

        $invalidSignature = base64_encode(json_encode(['fileIndex' => 1]));

        $source = [
            '__deleteFile' => [$invalidSignature],
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-invalid-1.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $testFilePath1,
                'size' => filesize($testFilePath1),
            ],
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-invalid-2.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $testFilePath2,
                'size' => filesize($testFilePath2),
            ],
        ];

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-invalid',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
            ]
        );

        $result = $subject->convertFrom($source, ObjectStorage::class, [], $configuration);

        self::assertInstanceOf(ObjectStorage::class, $result);
        self::assertCount(2, $result);
    }

    #[Test]
    public function convertFromDeletesFileFromServerWithValidDeleteSignature(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $hashService = $this->get(HashService::class);

        $testFilePath = $this->createTestFile('test-server-delete.pdf');

        $uploadSource = [
            'error' => \UPLOAD_ERR_OK,
            'name' => 'test-server-delete.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $testFilePath,
            'size' => filesize($testFilePath),
        ];

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-server-delete',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
                UploadedFileReferenceConverter::CONFIGURATION_ALLOW_REMOVAL => true,
            ]
        );

        /** @var FileReference $uploadedFile */
        $uploadedFile = $subject->convertFrom($uploadSource, FileReference::class, [], $configuration);

        $fileUid = $uploadedFile->getOriginalResource()->getOriginalFile()->getUid();
        $filePath = $uploadedFile->getOriginalResource()->getOriginalFile()->getForLocalProcessing(false);

        self::assertFileExists($filePath);

        $resourcePointer = $hashService->appendHmac(
            'file:' . $fileUid,
            HashScope::ResourcePointer->prefix()
        );

        $deleteSignature = $this->createSignedDeleteFileValue(0, 'attachments', $fileUid);

        $deleteSource = [
            '__deleteFile' => [$deleteSignature],
            '__submittedFiles' => [
                0 => [
                    'submittedFile' => [
                        'resourcePointer' => $resourcePointer,
                    ],
                ],
            ],
        ];

        $result = $subject->convertFrom($deleteSource, ObjectStorage::class, [], $configuration);

        self::assertInstanceOf(ObjectStorage::class, $result);
        self::assertCount(0, $result);
        self::assertFileDoesNotExist($filePath);
    }

    #[Test]
    public function convertFromDeletesEmptyUploadFolderAfterFileDeletion(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $hashService = $this->get(HashService::class);

        $testFilePath = $this->createTestFile('test-folder-cleanup.pdf');

        $uploadSource = [
            'error' => \UPLOAD_ERR_OK,
            'name' => 'test-folder-cleanup.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $testFilePath,
            'size' => filesize($testFilePath),
        ];

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-folder-cleanup',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
                UploadedFileReferenceConverter::CONFIGURATION_ALLOW_REMOVAL => true,
            ]
        );

        /** @var FileReference $uploadedFile */
        $uploadedFile = $subject->convertFrom($uploadSource, FileReference::class, [], $configuration);

        $fileUid = $uploadedFile->getOriginalResource()->getOriginalFile()->getUid();
        $parentFolderPath = dirname($uploadedFile->getOriginalResource()->getOriginalFile()->getForLocalProcessing(false));

        self::assertDirectoryExists($parentFolderPath);
        self::assertStringStartsWith('form_', basename($parentFolderPath));

        $deleteSignature = $this->createSignedDeleteFileValue(0, 'attachments', $fileUid);
        $resourcePointer = $hashService->appendHmac(
            'file:' . $fileUid,
            HashScope::ResourcePointer->prefix()
        );

        $deleteSource = [
            '__deleteFile' => [$deleteSignature],
            '__submittedFiles' => [
                0 => [
                    'submittedFile' => [
                        'resourcePointer' => $resourcePointer,
                    ],
                ],
            ],
        ];

        $subject->convertFrom($deleteSource, ObjectStorage::class, [], $configuration);

        self::assertDirectoryDoesNotExist($parentFolderPath);
    }

    #[Test]
    public function convertFromDoesNotDeleteFolderWhenOtherFilesRemain(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $hashService = $this->get(HashService::class);

        $testFilePath1 = $this->createTestFile('test-remain-1.pdf');
        $testFilePath2 = $this->createTestFile('test-remain-2.pdf');

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-remain',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
                UploadedFileReferenceConverter::CONFIGURATION_ALLOW_REMOVAL => true,
            ]
        );

        $uploadSource = [
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-remain-1.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $testFilePath1,
                'size' => filesize($testFilePath1),
            ],
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-remain-2.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $testFilePath2,
                'size' => filesize($testFilePath2),
            ],
        ];

        /** @var ObjectStorage<FileReference> $uploadedFiles */
        $uploadedFiles = $subject->convertFrom($uploadSource, ObjectStorage::class, [], $configuration);
        self::assertCount(2, $uploadedFiles);

        $fileRefs = $uploadedFiles->toArray();
        $firstFileUid = $fileRefs[0]->getOriginalResource()->getOriginalFile()->getUid();
        $secondFilePath = $fileRefs[1]->getOriginalResource()->getOriginalFile()->getForLocalProcessing(false);
        $parentFolderPath = dirname($secondFilePath);

        $deleteSignature = $this->createSignedDeleteFileValue(0, 'attachments', $firstFileUid);
        $resourcePointer1 = $hashService->appendHmac(
            'file:' . $firstFileUid,
            HashScope::ResourcePointer->prefix()
        );
        $resourcePointer2 = $hashService->appendHmac(
            'file:' . $fileRefs[1]->getOriginalResource()->getOriginalFile()->getUid(),
            HashScope::ResourcePointer->prefix()
        );

        $deleteSource = [
            '__deleteFile' => [$deleteSignature],
            '__submittedFiles' => [
                0 => [
                    'submittedFile' => [
                        'resourcePointer' => $resourcePointer1,
                    ],
                ],
                1 => [
                    'submittedFile' => [
                        'resourcePointer' => $resourcePointer2,
                    ],
                ],
            ],
        ];

        $result = $subject->convertFrom($deleteSource, ObjectStorage::class, [], $configuration);

        self::assertCount(1, $result);
        self::assertDirectoryExists($parentFolderPath);
        self::assertFileExists($secondFilePath);
    }

    #[Test]
    public function convertFromHandlesNonExistentFileGracefully(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);

        $deleteSignature = $this->createSignedDeleteFileValue(0, 'attachments', 99999);

        $source = [
            '__deleteFile' => [$deleteSignature],
            [
                'error' => \UPLOAD_ERR_NO_FILE,
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'size' => 0,
            ],
        ];

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(
            UploadedFileReferenceConverter::class,
            UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER,
            '1:/user_upload/'
        );

        $result = $subject->convertFrom($source, ObjectStorage::class, [], $configuration);

        self::assertInstanceOf(ObjectStorage::class, $result);
        self::assertCount(0, $result);
    }

    #[Test]
    public function convertFromMergesSubmittedFilesWithNewUploads(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $hashService = $this->get(HashService::class);

        // First, upload 2 existing files
        $existingFilePath1 = $this->createTestFile('test-existing-1.pdf');
        $existingFilePath2 = $this->createTestFile('test-existing-2.pdf');

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-merge',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
            ]
        );

        $uploadSource = [
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-existing-1.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $existingFilePath1,
                'size' => filesize($existingFilePath1),
            ],
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-existing-2.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $existingFilePath2,
                'size' => filesize($existingFilePath2),
            ],
        ];

        /** @var ObjectStorage<FileReference> $existingFiles */
        $existingFiles = $subject->convertFrom($uploadSource, ObjectStorage::class, [], $configuration);
        self::assertCount(2, $existingFiles);

        $fileRefs = $existingFiles->toArray();
        $fileUid1 = $fileRefs[0]->getOriginalResource()->getOriginalFile()->getUid();
        $fileUid2 = $fileRefs[1]->getOriginalResource()->getOriginalFile()->getUid();

        $resourcePointer1 = $hashService->appendHmac(
            'file:' . $fileUid1,
            HashScope::ResourcePointer->prefix()
        );
        $resourcePointer2 = $hashService->appendHmac(
            'file:' . $fileUid2,
            HashScope::ResourcePointer->prefix()
        );

        // Now simulate re-submission with 2 existing files in __submittedFiles + 1 new upload
        $newFilePath = $this->createTestFile('test-new-upload.pdf');

        $source = [
            '__submittedFiles' => [
                0 => [
                    'submittedFile' => [
                        'resourcePointer' => $resourcePointer1,
                    ],
                ],
                1 => [
                    'submittedFile' => [
                        'resourcePointer' => $resourcePointer2,
                    ],
                ],
            ],
            0 => [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-new-upload.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $newFilePath,
                'size' => filesize($newFilePath),
            ],
        ];

        $result = $subject->convertFrom($source, ObjectStorage::class, [], $configuration);

        self::assertInstanceOf(ObjectStorage::class, $result);
        // Must contain 3 files: 2 existing + 1 new
        self::assertCount(3, $result);

        $uploadedFilenames = [];
        foreach ($result as $fileReference) {
            self::assertInstanceOf(FileReference::class, $fileReference);
            $uploadedFilenames[] = $fileReference->getOriginalResource()->getOriginalFile()->getName();
        }

        self::assertContains('test-existing-1.pdf', $uploadedFilenames);
        self::assertContains('test-existing-2.pdf', $uploadedFilenames);
        self::assertContains('test-new-upload.pdf', $uploadedFilenames);
    }

    #[Test]
    public function convertFromHandlesOnlySubmittedFilesWithoutNewUploads(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $hashService = $this->get(HashService::class);

        // Upload a file first
        $existingFilePath = $this->createTestFile('test-only-existing.pdf');

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-only-existing',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
            ]
        );

        $uploadSource = [
            'error' => \UPLOAD_ERR_OK,
            'name' => 'test-only-existing.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $existingFilePath,
            'size' => filesize($existingFilePath),
        ];

        /** @var FileReference $uploadedFile */
        $uploadedFile = $subject->convertFrom($uploadSource, FileReference::class, [], $configuration);
        $fileUid = $uploadedFile->getOriginalResource()->getOriginalFile()->getUid();

        $resourcePointer = $hashService->appendHmac(
            'file:' . $fileUid,
            HashScope::ResourcePointer->prefix()
        );

        // Simulate re-submission with only existing files, no new uploads
        $source = [
            '__submittedFiles' => [
                0 => [
                    'submittedFile' => [
                        'resourcePointer' => $resourcePointer,
                    ],
                ],
            ],
        ];

        $result = $subject->convertFrom($source, ObjectStorage::class, [], $configuration);

        self::assertInstanceOf(ObjectStorage::class, $result);
        self::assertCount(1, $result);
    }

    /**
     * Regression test for bug: resubmitting a multi-upload form with exactly
     * one previously uploaded file caused an exception because the converter
     * returned a single PseudoFileReference instead of an ObjectStorage.
     *
     * The root cause was that FileUpload::initializeFormElement() always set
     * dataType to FileReference::class, causing isMultiUploadTarget() to fail
     * when __submittedFiles had only one entry. The fix in
     * PropertyMappingConfiguration sets dataType to ObjectStorage::class for
     * multiple uploads, so this test verifies the converter works correctly
     * with ObjectStorage as the target type for single-file resubmissions.
     */
    #[Test]
    public function convertFromReturnsObjectStorageForSingleSubmittedFileInMultiUploadContext(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $hashService = $this->get(HashService::class);

        $testFilePath = $this->createTestFile('test-multi-resubmit.pdf');

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-multi-resubmit',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
            ]
        );

        $uploadSource = [
            'error' => \UPLOAD_ERR_OK,
            'name' => 'test-multi-resubmit.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $testFilePath,
            'size' => filesize($testFilePath),
        ];

        /** @var FileReference $uploadedFile */
        $uploadedFile = $subject->convertFrom($uploadSource, FileReference::class, [], $configuration);
        $fileUid = $uploadedFile->getOriginalResource()->getOriginalFile()->getUid();

        $resourcePointer = $hashService->appendHmac(
            'file:' . $fileUid,
            HashScope::ResourcePointer->prefix()
        );

        // Simulate resubmission of a multi-upload element with exactly 1 existing
        // file. The key difference is using ObjectStorage::class as target type,
        // which is what PropertyMappingConfiguration now sets for multiple=true.
        $source = [
            '__submittedFiles' => [
                0 => [
                    'submittedFile' => [
                        'resourcePointer' => $resourcePointer,
                    ],
                ],
            ],
        ];

        $result = $subject->convertFrom($source, ObjectStorage::class, [], $configuration);

        self::assertInstanceOf(ObjectStorage::class, $result);
        self::assertCount(1, $result);

        $fileNames = [];
        foreach ($result as $fileReference) {
            self::assertInstanceOf(FileReference::class, $fileReference);
            $fileNames[] = $fileReference->getOriginalResource()->getOriginalFile()->getName();
        }
        self::assertContains('test-multi-resubmit.pdf', $fileNames);
    }

    #[Test]
    public function convertFromDeletesExistingFileFromSubmittedFiles(): void
    {
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $hashService = $this->get(HashService::class);

        // Upload 2 files first
        $existingFilePath1 = $this->createTestFile('test-del-existing-1.pdf');
        $existingFilePath2 = $this->createTestFile('test-del-existing-2.pdf');

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-del-existing',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
                UploadedFileReferenceConverter::CONFIGURATION_ALLOW_REMOVAL => true,
            ]
        );

        $uploadSource = [
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-del-existing-1.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $existingFilePath1,
                'size' => filesize($existingFilePath1),
            ],
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test-del-existing-2.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $existingFilePath2,
                'size' => filesize($existingFilePath2),
            ],
        ];

        /** @var ObjectStorage<FileReference> $existingFiles */
        $existingFiles = $subject->convertFrom($uploadSource, ObjectStorage::class, [], $configuration);
        self::assertCount(2, $existingFiles);

        $fileRefs = $existingFiles->toArray();
        $fileUid1 = $fileRefs[0]->getOriginalResource()->getOriginalFile()->getUid();
        $fileUid2 = $fileRefs[1]->getOriginalResource()->getOriginalFile()->getUid();

        $resourcePointer1 = $hashService->appendHmac(
            'file:' . $fileUid1,
            HashScope::ResourcePointer->prefix()
        );
        $resourcePointer2 = $hashService->appendHmac(
            'file:' . $fileUid2,
            HashScope::ResourcePointer->prefix()
        );

        // Delete file at index 0 from the existing submitted files
        $deleteSignature = $this->createSignedDeleteFileValue(0, 'attachments', $fileUid1);

        $source = [
            '__deleteFile' => [$deleteSignature],
            '__submittedFiles' => [
                0 => [
                    'submittedFile' => [
                        'resourcePointer' => $resourcePointer1,
                    ],
                ],
                1 => [
                    'submittedFile' => [
                        'resourcePointer' => $resourcePointer2,
                    ],
                ],
            ],
        ];

        $result = $subject->convertFrom($source, ObjectStorage::class, [], $configuration);

        self::assertInstanceOf(ObjectStorage::class, $result);
        // Only 1 file should remain after deletion
        self::assertCount(1, $result);
    }

    /**
     * Creates a test file from the fixture directory
     */
    private function createTestFile(string $filename): string
    {
        $fixtureFile = __DIR__ . '/Fixtures/test-document.pdf';
        return $this->createTestFileFromFixture($filename, $fixtureFile);
    }

    /**
     * Creates a test file from a fixture file
     */
    private function createTestFileFromFixture(string $filename, string $fixtureFilePath): string
    {
        $testFilesPath = $this->instancePath . '/typo3temp/var/transient/';
        GeneralUtility::mkdir_deep($testFilesPath);

        $filePath = $testFilesPath . $filename;
        copy($fixtureFilePath, $filePath);

        return $filePath;
    }

    /**
     * Creates a signed delete file value using HMAC
     */
    private function createSignedDeleteFileValue(int $fileIndex, string $property = 'file', int $fileUid = 1): string
    {
        $hashService = $this->get(HashService::class);
        $deleteData = [
            'property' => $property,
            'fileIndex' => $fileIndex,
            'fileUid' => $fileUid,
        ];
        return $hashService->appendHmac(
            json_encode($deleteData, JSON_THROW_ON_ERROR),
            HashScope::DeleteFile->prefix()
        );
    }
}
