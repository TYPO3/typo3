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

namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration;
use TYPO3\CMS\Extbase\Validation\Validator\FileNameValidator;
use TYPO3\CMS\Extbase\Validation\Validator\FileSizeValidator;
use TYPO3\CMS\Extbase\Validation\Validator\MimeTypeValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileUploadConfigurationTest extends UnitTestCase
{
    #[Test]
    public function propertyNameIsSetInConstructor(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        self::assertEquals('myProperty', $fileUploadConfiguration->getPropertyName());
    }

    #[Test]
    public function getUploadFolderReturnsInitialValue(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        self::assertEmpty($fileUploadConfiguration->getUploadFolder());
    }

    #[Test]
    public function uploadFolderCanBeSet(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->setUploadFolder('1:/upload_folder/');
        self::assertEquals('1:/upload_folder/', $fileUploadConfiguration->getUploadFolder());
    }

    #[Test]
    public function getValidatorsIsEmptyWhenConfigurationCreatedByConstructorInjection(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        self::assertEmpty($fileUploadConfiguration->getValidators());
    }

    #[Test]
    public function getValidatorsContainsFileNameValidatorWhenValidatorsAreReset(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->resetValidators();
        self::assertNotEmpty($fileUploadConfiguration->getValidators());
        self::assertInstanceOf(FileNameValidator::class, $fileUploadConfiguration->getValidators()[0]);
    }

    #[Test]
    public function newValidatorCanBeAdded(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $notEmptyValidator = GeneralUtility::makeInstance(NotEmptyValidator::class);
        $fileUploadConfiguration->addValidator($notEmptyValidator);
        self::assertNotEmpty($fileUploadConfiguration->getValidators());
    }

    #[Test]
    public function getMinFilesReturnsInitialValue(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        self::assertEquals(0, $fileUploadConfiguration->getMinFiles());
    }

    #[Test]
    public function minFilesCanBeSet(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->setMinFiles(2);
        self::assertEquals(2, $fileUploadConfiguration->getMinFiles());
    }

    #[Test]
    public function getMaxFilesReturnsInitialValue(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        self::assertEquals(PHP_INT_MAX, $fileUploadConfiguration->getMaxFiles());
    }

    #[Test]
    public function maxFilesCanBeSet(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->setMaxFiles(2);
        self::assertEquals(2, $fileUploadConfiguration->getMaxFiles());
    }

    #[Test]
    public function isAddRandomSuffixReturnsInitialValue(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        self::assertTrue($fileUploadConfiguration->isAddRandomSuffix());
    }

    #[Test]
    public function addRandomSuffixCanBeSet(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->setAddRandomSuffix(false);
        self::assertFalse($fileUploadConfiguration->isAddRandomSuffix());
    }

    #[Test]
    public function isCreateUploadFolderIfNotExistReturnsInitialValue(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        self::assertTrue($fileUploadConfiguration->isCreateUploadFolderIfNotExist());
    }

    #[Test]
    public function isCreateUploadFolderIfNotExistCanBeSet(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->setCreateUploadFolderIfNotExist(false);
        self::assertFalse($fileUploadConfiguration->isCreateUploadFolderIfNotExist());
    }

    #[Test]
    public function getDuplicationBehaviorReturnsInitialValue(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        self::assertEquals(DuplicationBehavior::RENAME, $fileUploadConfiguration->getDuplicationBehavior());
    }

    #[Test]
    public function duplicationBehaviorCanBeSet(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->setDuplicationBehavior(DuplicationBehavior::CANCEL);
        self::assertEquals(DuplicationBehavior::CANCEL, $fileUploadConfiguration->getDuplicationBehavior());
    }

    #[Test]
    public function initializeWithConfigurationSetsMinFilesForRequiredSetting(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
            ],
        ]);
        self::assertEquals(1, $fileUploadConfiguration->getMinFiles());
    }

    #[Test]
    public function initializeWithConfigurationSetsMinFilesForMinFilesSetting(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'minFiles' => 1,
            ],
        ]);
        self::assertEquals(1, $fileUploadConfiguration->getMinFiles());
    }

    #[Test]
    public function initializeWithConfigurationSetsMaxFilesForMaxFilesSetting(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'maxFiles' => 10,
            ],
        ]);
        self::assertEquals(10, $fileUploadConfiguration->getMaxFiles());
    }

    #[Test]
    public function initializeWithConfigurationAddsAllowedMimeTypeValidatorIfConfigured(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'allowedMimeTypes' => ['image/jpeg', 'image/png'],
            ],
        ]);
        self::assertInstanceOf(FileNameValidator::class, $fileUploadConfiguration->getValidators()[0]);
        self::assertInstanceOf(MimeTypeValidator::class, $fileUploadConfiguration->getValidators()[1]);
    }

    #[Test]
    public function initializeWithConfigurationAddsMimeTypeValidatorIfConfigured(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'mimeType' => ['allowedMimeTypes' => ['image/jpeg', 'image/png']],
            ],
        ]);
        self::assertInstanceOf(FileNameValidator::class, $fileUploadConfiguration->getValidators()[0]);
        self::assertInstanceOf(MimeTypeValidator::class, $fileUploadConfiguration->getValidators()[1]);
    }

    #[Test]
    public function initializeWithConfigurationAddsFileSizeValidatorIfConfigured(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'fileSize' => ['minimum' => '0K', 'maximum' => '2M'],
            ],
        ]);
        self::assertInstanceOf(FileNameValidator::class, $fileUploadConfiguration->getValidators()[0]);
        self::assertInstanceOf(FileSizeValidator::class, $fileUploadConfiguration->getValidators()[1]);
    }

    #[Test]
    public function initializeWithConfigurationSetsUploadFolderIfConfigured(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
            ],
            'uploadFolder' => '1:/user_upload/files/',
        ]);
        self::assertEquals('1:/user_upload/files/', $fileUploadConfiguration->getUploadFolder());
    }

    #[Test]
    public function initializeWithConfigurationSetsAddRandomSuffixIfConfigured(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
            ],
            'addRandomSuffix' => false,
        ]);
        self::assertFalse($fileUploadConfiguration->isAddRandomSuffix());
    }

    #[Test]
    public function initializeWithConfigurationSetsCreateUploadFolderIfNotExistIfConfigured(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
            ],
            'createUploadFolderIfNotExist' => false,
        ]);
        self::assertFalse($fileUploadConfiguration->isCreateUploadFolderIfNotExist());
    }

    #[Test]
    public function initializeWithConfigurationSetsDuplicationBehaviorIfConfigured(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
            ],
            'duplicationBehavior' => DuplicationBehavior::CANCEL,
        ]);
        self::assertEquals(DuplicationBehavior::CANCEL, $fileUploadConfiguration->getDuplicationBehavior());
    }

    #[Test]
    public function ensureValidConfigurationThrowsExceptionIfTargetTypeIsNotFileReference(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The FileUploadConfiguration can only be used for properties of type FileReference.');
        $this->expectExceptionCode(1721623184);

        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
            ],
            'uploadFolder' => '1:/user_upload/files/',
        ]);

        $fileUploadConfiguration->ensureValidConfiguration(UploadedFile::class);
    }

    #[Test]
    public function ensureValidConfigurationThrowsExceptionIfPropertyNameContainsDot(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The property name for the FileUploadConfiguration must not contain any dot.');
        $this->expectExceptionCode(1724585391);

        $fileUploadConfiguration = new FileUploadConfiguration('foo.myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
            ],
            'uploadFolder' => '1:/user_upload/files/',
        ]);

        $fileUploadConfiguration->ensureValidConfiguration(FileReference::class);
    }

    #[Test]
    public function ensureValidConfigurationThrowsExceptionIfMaxFilesIsLessThanMinFiles(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Maximum number of files cannot be less than minimum number of files.');
        $this->expectExceptionCode(1711799765);

        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
                'minFiles' => 2,
                'maxFiles' => 1,
            ],
            'uploadFolder' => '1:/user_upload/files/',
        ]);

        $fileUploadConfiguration->ensureValidConfiguration(FileReference::class);
    }

    #[Test]
    public function ensureValidConfigurationThrowsExceptionIfUploadFolderIsNotSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('An upload folder must be defined for the FileUploadConfiguration.');
        $this->expectExceptionCode(1711799735);

        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
            ],
            'uploadFolder' => '',
        ]);

        $fileUploadConfiguration->ensureValidConfiguration(FileReference::class);
    }

    public static function invalidFoldersDataProvider(): array
    {
        return [
            'Missing storage identifier' => [
                '/not-valid/',
            ],
            'Invalid storage identifier' => [
                'foo:bar',
            ],
            'Missing upload path' => [
                '1:',
            ],
        ];
    }

    #[DataProvider('invalidFoldersDataProvider')]
    #[Test]
    public function ensureValidConfigurationThrowsExceptionIfUploadFolderIsNoCombinedIdentifier(string $folder): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The upload folder must be a combined identifier - e.g. 1:/user_upload/');
        $this->expectExceptionCode(1711801071);

        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileUploadConfiguration->initializeWithConfiguration([
            'validation' => [
                'required' => true,
            ],
            'uploadFolder' => $folder,
        ]);

        $fileUploadConfiguration->ensureValidConfiguration(FileReference::class);
    }
}
