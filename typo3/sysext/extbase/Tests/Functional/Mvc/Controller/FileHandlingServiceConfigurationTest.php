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

namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Mvc\Controller\Argument;
use TYPO3\CMS\Extbase\Mvc\Controller\FileHandlingServiceConfiguration;
use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\FileUpload\Domain\Model\FileReferencePropertyMultiple;
use TYPO3Tests\FileUpload\Domain\Model\FileReferencePropertySingle;

final class FileHandlingServiceConfigurationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/file_upload'];

    #[Test]
    public function validateFileOperationsHasNoErrorsIfNoFileUploadConfiguration(): void
    {
        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceSingle', FileReferencePropertySingle::class);

        self::assertFalse($fileHandlingServiceConfiguration->validateFileOperations($argument)->hasErrors());
    }

    /**
     * This test ensures, that validation of a property with a required file upload does not fail, when the property
     * already contains a file reference and no uploaded file is given
     */
    #[Test]
    public function validateFileOperationsHasNoErrorForNonRequiredEmptyFileReferencePropertyAndNoUpload(): void
    {
        $argumentValue = new FileReferencePropertySingle();

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceSingle', FileReferencePropertySingle::class);
        $argument->setValue($argumentValue);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('file');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => false,
                'maxFiles' => 1,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_file/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertFalse($validationResult->hasErrors());
    }

    /**
     * This test ensures, that validation of a property with a required file upload fails, when no upload is given
     */
    #[Test]
    public function validateFileOperationsHasErrorForRequiredEmptyFileReferencePropertyAndNoUpload(): void
    {
        $argumentValue = new FileReferencePropertySingle();

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceSingle', FileReferencePropertySingle::class);
        $argument->setValue($argumentValue);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('file');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'maxFiles' => 1,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_file/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertEquals(1708596527, $validationResult->forProperty('file')->getFirstError()->getCode());
    }

    /**
     * This test ensures, that validation of a property with a required file upload does not fail, when a valid
     * file upload is given for the property
     */
    #[Test]
    public function validateFileOperationsHasNoErrorForRequiredEmptyFileReferencePropertyAndFileUpload(): void
    {
        $testFilename = $this->createTestFile('testfile.txt', 'test content');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $argumentValue = new FileReferencePropertySingle();

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceSingle', FileReferencePropertySingle::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['file' => $uploadedFile]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('file');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'maxFiles' => 1,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_file/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertFalse($validationResult->hasErrors());
    }

    /**
     * This test ensures, that validation of a property with a required file upload does fail, when the property
     * already contains a file reference and an additional file is uploaded
     */
    #[Test]
    public function validateFileOperationsHasErrorForRequiredFileReferencePropertyAndFileUpload(): void
    {
        $testFilename = $this->createTestFile('testfile.txt', 'test content');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $argumentValue = new FileReferencePropertySingle();
        $argumentValue->setFile(new FileReference());

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceSingle', FileReferencePropertySingle::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['file' => $uploadedFile]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('file');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'maxFiles' => 1,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_file/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertTrue($validationResult->hasErrors());
        self::assertEquals(1708596528, $validationResult->forProperty('file')->getFirstError()->getCode());
    }

    /**
     * This test ensures, that validation of a property with a required file upload does not fail, when the property
     * already contains a file reference, a file is uploaded and the existing file should be deleted
     */
    #[Test]
    public function validateFileOperationsHasNoErrorForRequiredFileReferencePropertyAndFileUploadWithDeletion(): void
    {
        $testFilename = $this->createTestFile('testfile.txt', 'test content');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $argumentValue = new FileReferencePropertySingle();
        $argumentValue->setFile(new FileReference());

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceSingle', FileReferencePropertySingle::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['file' => $uploadedFile]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('file');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'maxFiles' => 1,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_file/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);
        $fileHandlingServiceConfiguration->registerFileDeletion('file', 1);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertFalse($validationResult->hasErrors());
    }

    /**
     * This test ensures, that validation of an ObjectStorage property with a non-required file upload has not error,
     * when no upload is given
     */
    #[Test]
    public function validateFileOperationsHasNoErrorForNonRequiredEmptyMultiFileReferencePropertyAndNoUpload(): void
    {
        $argumentValue = new FileReferencePropertyMultiple();

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => false,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertFalse($validationResult->hasErrors());
    }

    /**
     * This test ensures, that validation of an ObjectStorage property with a required file upload fails,
     * when no upload is given
     */
    #[Test]
    public function validateFileOperationsHasErrorForRequiredMax1EmptyMultiFileReferencePropertyAndNoUpload(): void
    {
        $argumentValue = new FileReferencePropertyMultiple();

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'maxFiles' => 1,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertEquals(1708596527, $validationResult->forProperty('files')->getFirstError()->getCode());
    }

    /**
     * This test ensures, that validation of an ObjectStorage property with a required file upload does not fail,
     * when argument value is empty and a file upload is provided
     */
    #[Test]
    public function validateFileOperationsHasNoErrorForRequiredMax1EmptyMultiFileReferencePropertyAndFileUpload(): void
    {
        $testFilename = $this->createTestFile('testfile.txt', 'test content');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $files = new ObjectStorage();
        $argumentValue = new FileReferencePropertyMultiple();
        $argumentValue->setFiles($files);

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['files' => $uploadedFile]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'maxFiles' => 1,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertFalse($validationResult->hasErrors());
    }

    /**
     * This test ensures, that validation of an ObjectStorage property with a required file upload does fail,
     * when argument value already contains one file reference and the max files value is exceeded
     */
    #[Test]
    public function validateFileOperationsHasErrorForRequiredMax1NonEmptyMultiFileReferencePropertyAndFileUpload(): void
    {
        $testFilename = $this->createTestFile('testfile.txt', 'test content');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $files = new ObjectStorage();
        $files->attach(new FileReference());
        $argumentValue = new FileReferencePropertyMultiple();
        $argumentValue->setFiles($files);

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['files' => $uploadedFile]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'maxFiles' => 1,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertTrue($validationResult->hasErrors());
        self::assertEquals(1708596528, $validationResult->forProperty('files')->getFirstError()->getCode());
    }

    /**
     * This test ensures, that validation of an ObjectStorage property with a required file upload does fail,
     * when argument value already contains one file reference and the max files value is exceeded
     */
    #[Test]
    public function validateFileOperationsHasNoErrorForRequiredMax1NonEmptyMultiFileReferencePropertyAndFileUploadWithDeletion(): void
    {
        $testFilename = $this->createTestFile('testfile.txt', 'test content');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $files = new ObjectStorage();
        $files->attach(new FileReference());
        $argumentValue = new FileReferencePropertyMultiple();
        $argumentValue->setFiles($files);

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['files' => $uploadedFile]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'maxFiles' => 1,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);
        $fileHandlingServiceConfiguration->registerFileDeletion('files', 1);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertFalse($validationResult->hasErrors());
    }

    /**
     * This test ensures, that validation of an ObjectStorage property with a required file upload does not fail,
     * when argument value already contains one file reference and another uploaded file is given
     */
    #[Test]
    public function validateFileOperationsHasNoErrorForRequiredMax2NonEmptyMultiFileReferencePropertyAndUpload(): void
    {
        $testFilename = $this->createTestFile('testfile.txt', 'test content');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $files = new ObjectStorage();
        $files->attach(new FileReference());
        $argumentValue = new FileReferencePropertyMultiple();
        $argumentValue->setFiles($files);

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['files' => $uploadedFile]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'maxFiles' => 2,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertFalse($validationResult->hasErrors());
    }

    /**
     * This test ensures, that validation of an ObjectStorage property with a required file upload fails,
     * when argument value already contains two file references and another uploaded file is given, but the
     * maximum amount of files is exceeded
     */
    #[Test]
    public function validateFileOperationsHasErrorForRequiredNonEmptyMax2MultiFileReferencePropertyAndUpload(): void
    {
        $testFilename = $this->createTestFile('testfile.txt', 'test content');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $files = new ObjectStorage();
        $files->attach(new FileReference());
        $files->attach(new FileReference());
        $argumentValue = new FileReferencePropertyMultiple();
        $argumentValue->setFiles($files);

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['files' => $uploadedFile]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'maxFiles' => 2,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertEquals(1708596528, $validationResult->forProperty('files')->getFirstError()->getCode());
    }

    /**
     * This test ensures, that validation of an ObjectStorage property with a required file upload does not fail,
     * when argument value already contains two file references, an uploaded file is provided and a file deletion
     * is registered for the property
     */
    #[Test]
    public function validateFileOperationsHasNoErrorForRequiredMax2MultiFileReferenceWith2FilesPropertyAndUploadWithDeletion(): void
    {
        $testFilename = $this->createTestFile('testfile.txt', 'test content');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $files = new ObjectStorage();
        $files->attach(new FileReference());
        $files->attach(new FileReference());
        $argumentValue = new FileReferencePropertyMultiple();
        $argumentValue->setFiles($files);

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['files' => $uploadedFile]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'maxFiles' => 2,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);
        $fileHandlingServiceConfiguration->registerFileDeletion('files', 1);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertFalse($validationResult->hasErrors());
    }

    /**
     * This test ensures, that validation of an ObjectStorage property with a required file upload does not fail,
     * when argument value already contains one file reference, an uploaded file is provided and a file deletion
     * is registered for the property
     */
    #[Test]
    public function validateFileOperationsHasNoErrorForRequiredMax2MultiFileReferenceWith1FilePropertyAndUploadWithDeletion(): void
    {
        $testFilename = $this->createTestFile('testfile.txt', 'test content');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $files = new ObjectStorage();
        $files->attach(new FileReference());
        $argumentValue = new FileReferencePropertyMultiple();
        $argumentValue->setFiles($files);

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['files' => $uploadedFile]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'maxFiles' => 2,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);
        $fileHandlingServiceConfiguration->registerFileDeletion('files', 1);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertFalse($validationResult->hasErrors());
    }

    /**
     * This test ensures, that validation of an ObjectStorage property with a required file upload does fail,
     * when argument value already contains two file references, an uploaded file is provided and the max files
     * value is exceeded
     */
    #[Test]
    public function validateFileOperationsHasErrorForRequiredMin2Max2MultiFileReferenceWith2FilesPropertiesAndUpload(): void
    {
        $testFilename = $this->createTestFile('testfile.txt', 'test content');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $files = new ObjectStorage();
        $files->attach(new FileReference());
        $files->attach(new FileReference());
        $argumentValue = new FileReferencePropertyMultiple();
        $argumentValue->setFiles($files);

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['files' => $uploadedFile]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'minFiles' => 2,
                'maxFiles' => 2,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertTrue($validationResult->hasErrors());
        self::assertEquals(1708596527, $validationResult->forProperty('files')->getFirstError()->getCode());
    }

    /**
     * This test ensures, that validation of an ObjectStorage property with a required file upload does not fail,
     * when argument value contains no file references, and 2 uploaded files are provided
     */
    #[Test]
    public function validateFileOperationsHasNoErrorForRequiredMin2Max2EmptyMultiFileReferenceAnd2Uploads(): void
    {
        $testFilename1 = $this->createTestFile('testfile1.txt', 'test content');
        $uploadedFile1 = new UploadedFile($testFilename1, 100, UPLOAD_ERR_OK, 'testfile1.txt');
        $testFilename2 = $this->createTestFile('testfile2.txt', 'test content');
        $uploadedFile2 = new UploadedFile($testFilename2, 100, UPLOAD_ERR_OK, 'testfile2.txt');

        $files = new ObjectStorage();
        $argumentValue = new FileReferencePropertyMultiple();
        $argumentValue->setFiles($files);

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['files' => [$uploadedFile1, $uploadedFile2]]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'minFiles' => 2,
                'maxFiles' => 2,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertFalse($validationResult->hasErrors());
    }

    /**
     * This test ensures, that validation of an ObjectStorage property with a required file upload does fail,
     * when argument value contains no file references, 3 uploaded files are provided and the max files
     * value is exceeded
     */
    #[Test]
    public function validateFileOperationsHasErrorForRequiredMin2Max2EmptyMultiFileReferenceAnd3Uploads(): void
    {
        $testFilename1 = $this->createTestFile('testfile1.txt', 'test content');
        $uploadedFile1 = new UploadedFile($testFilename1, 100, UPLOAD_ERR_OK, 'testfile1.txt');
        $testFilename2 = $this->createTestFile('testfile2.txt', 'test content');
        $uploadedFile2 = new UploadedFile($testFilename2, 100, UPLOAD_ERR_OK, 'testfile2.txt');
        $testFilename3 = $this->createTestFile('testfile3.txt', 'test content');
        $uploadedFile3 = new UploadedFile($testFilename3, 100, UPLOAD_ERR_OK, 'testfile3.txt');

        $files = new ObjectStorage();
        $argumentValue = new FileReferencePropertyMultiple();
        $argumentValue->setFiles($files);

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['files' => [$uploadedFile1, $uploadedFile2, $uploadedFile3]]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'minFiles' => 2,
                'maxFiles' => 2,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertTrue($validationResult->hasErrors());
        self::assertEquals(1708596528, $validationResult->forProperty('files')->getFirstError()->getCode());
    }

    /**
     * This test ensures, that validation of an ObjectStorage property with a required file upload does not fail,
     * when argument value already contains two file references, two uploaded files are provided and 2 file deletions
     * are registered
     */
    #[Test]
    public function validateFileOperationsHasNoErrorForRequiredMin2MultiFileReferenceWith2FilesPropertyAnd2UploadsWithDeletion(): void
    {
        $testFilename1 = $this->createTestFile('testfile1.txt', 'test content');
        $uploadedFile1 = new UploadedFile($testFilename1, 100, UPLOAD_ERR_OK, 'testfile1.txt');
        $testFilename2 = $this->createTestFile('testfile2.txt', 'test content');
        $uploadedFile2 = new UploadedFile($testFilename2, 100, UPLOAD_ERR_OK, 'testfile2.txt');

        $files = new ObjectStorage();
        $files->attach(new FileReference());
        $files->attach(new FileReference());
        $argumentValue = new FileReferencePropertyMultiple();
        $argumentValue->setFiles($files);

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['files' => [$uploadedFile1, $uploadedFile2]]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'minFiles' => 2,
                'maxFiles' => 2,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);
        $fileHandlingServiceConfiguration->registerFileDeletion('files', 1);
        $fileHandlingServiceConfiguration->registerFileDeletion('files', 2);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertFalse($validationResult->hasErrors());
    }

    #[Test]
    public function validateFileOperationsHasErrorIfPhpFileIsProvidedForUpload(): void
    {
        $testFilename = $this->createTestFile('testfile.php', '<php phpinfo(); ');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.php');

        $files = new ObjectStorage();
        $argumentValue = new FileReferencePropertyMultiple();
        $argumentValue->setFiles($files);

        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $argument = new Argument('FileReferenceMultiple', FileReferencePropertyMultiple::class);
        $argument->setValue($argumentValue);
        $argument->setUploadedFiles(['files' => $uploadedFile]);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $fileUploadConfiguration = new FileUploadConfiguration('files');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
            ],
            'uploadFolder' => '1:/user_upload/folder_for_files/',
        ]);
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);

        $validationResult = $fileHandlingServiceConfiguration->validateFileOperations($argument);
        self::assertTrue($validationResult->hasErrors());
        self::assertEquals(1711367029, $validationResult->forProperty('files')->getFirstError()->getCode());
    }

    // @todo Add tests for validators (e.g. MimeType, FileSize, Image dimensions) are executed
    // @todo Add test for custom registered validator

    /**
     * Helper function to create a test file with the given content.
     */
    private function createTestFile(string $filename, string $content): string
    {
        $path = $this->instancePath . '/tmp';
        $testFilename = $path . $filename;

        GeneralUtility::mkdir($path);
        touch($testFilename);
        file_put_contents($testFilename, $content);

        return $testFilename;
    }
}
