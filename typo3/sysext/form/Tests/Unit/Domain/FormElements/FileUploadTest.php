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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\FormElements;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration as ExtbasePropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Mvc\ProcessingRule;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\CMS\Form\Mvc\Validation\MimeTypeValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileUploadTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private ExtbasePropertyMappingConfiguration&MockObject $extbasePropertyMappingConfiguration;
    private FileUpload&MockObject $fileUpload;
    private FormDefinition&MockObject $rootForm;
    private ProcessingRule&MockObject $processingRule;

    public function setUp(): void
    {
        parent::setUp();
        // Property Mapping Configuration
        $this->extbasePropertyMappingConfiguration = $this->getMockBuilder(ExtbasePropertyMappingConfiguration::class)
            ->onlyMethods(['setTypeConverterOptions'])
            ->disableOriginalConstructor()
            ->getMock();

        // Processing Rules
        $this->processingRule = $this->getMockBuilder(ProcessingRule::class)
            ->onlyMethods(['getValidators', 'addValidator', 'removeValidator', 'getPropertyMappingConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->processingRule
            ->method('getPropertyMappingConfiguration')
            ->willReturn($this->extbasePropertyMappingConfiguration);

        // Root Form
        $this->rootForm = $this->getMockBuilder(FormDefinition::class)
            ->onlyMethods(['getProcessingRule', 'getPersistenceIdentifier', 'getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->rootForm
            ->method('getProcessingRule')
            ->willReturn($this->processingRule);

        // File Upload
        $this->fileUpload = $this->getAccessibleMock(FileUpload::class, ['getRootForm', 'getProperties'], ['foo', 'FileUpload']);

        $this->fileUpload
            ->method('getRootForm')
            ->willReturn($this->rootForm);
    }

    /**
     * A bare minimum test that checks if the function maybe potentially works.
     */
    #[Test]
    public function afterBuildingFinishedAddsFileReferenceConverter(): void
    {
        // No validators
        $this->processingRule
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        // Mime Types not important
        $this->fileUpload
            ->method('getProperties')
            ->willReturn(['allowedMimeTypes' => []]);

        // Check if the UploadFileReference is included
        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
            ->method('setTypeConverterOptions')
            ->with(UploadedFileReferenceConverter::class);

        $this->fileUpload->initializeFormElement();
    }

    #[Test]
    public function afterBuildingFinishedAddsMimeTypeConverter(): void
    {
        $mimeTypes = ['allowedMimeTypes' => ['text/plain', 'application/x-www-form-urlencoded']];

        // Don't add any validators for now
        $this->processingRule
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        // Add some Mime types
        $this->fileUpload
            ->method('getProperties')
            ->willReturn($mimeTypes);

        $mimeTypeValidator = new MimeTypeValidator();
        $mimeTypeValidator->setOptions(['allowedMimeTypes' => []]);
        $validatorResolver = $this->createMock(ValidatorResolver::class);
        $validatorResolver->method('createValidator')->with(
            MimeTypeValidator::class,
            ['allowedMimeTypes' => ['text/plain', 'application/x-www-form-urlencoded']]
        )->willReturn($mimeTypeValidator);
        GeneralUtility::setSingletonInstance(ValidatorResolver::class, $validatorResolver);

        // Expect the MimeTypeValidator to be added to the ProcessingRule
        $this->processingRule
            ->expects($this->once())
            ->method('addValidator')
            ->with(self::isInstanceOf(MimeTypeValidator::class));

        $this->fileUpload->initializeFormElement();
    }

    #[Test]
    public function afterBuildingFinishedSetsUpStoragePathToPropertySaveToFileMountIfItExists(): void
    {
        $resourceFactory = $this->createMock(ResourceFactory::class);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);

        // Don't add any validators for now
        $this->processingRule
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        // Set the file mount
        $this->fileUpload
            ->method('getProperties')
            ->willReturn(['saveToFileMount' => '/tmp']);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function (string $typeConverter, array $options): ExtbasePropertyMappingConfiguration {
                $this->assertArrayHasKey(UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER, $options);
                $folder = $options[UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER];

                $this->assertSame('/tmp', $folder);

                return $this->extbasePropertyMappingConfiguration;
            });

        $this->fileUpload->initializeFormElement();
    }

    #[Test]
    public function afterBuildingFinishedSetsUpStoragePathToToFormDefinitionPathIfSaveToFileMountIsNotDefinedAndFormWasNotAddedProgrammatically(): void
    {
        $resourceFactory = $this->createMock(ResourceFactory::class);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);

        // Don't add any validators for now
        $this->processingRule
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        $this->rootForm
            ->method('getPersistenceIdentifier')
            ->willReturn('/tmp/somefile');

        // Set the file mount
        $this->fileUpload
            ->method('getProperties')
            ->willReturn(['saveToFileMount' => '']);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function (string $typeConverter, array $options): ExtbasePropertyMappingConfiguration {
                $this->assertArrayHasKey(UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER, $options);
                $folder = $options[UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER];

                $this->assertSame('/tmp', $folder);

                return $this->extbasePropertyMappingConfiguration;
            });

        $this->fileUpload->initializeFormElement();
    }

    #[Test]
    public function afterBuildingFinishedSetsStoragePathToUserUploadIfNeitherSaveToFileMountIsSetNorThereIsAFormDefinitionPath(): void
    {
        $this->processingRule
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        $this->rootForm
            ->method('getPersistenceIdentifier')
            ->willReturn('');

        $this->fileUpload
            ->method('getProperties')
            ->willReturn(['saveToFileMount' => '']);

        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function (string $typeConverter, array $options): ExtbasePropertyMappingConfiguration {
                $this->assertArrayNotHasKey(UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER, $options);

                return $this->extbasePropertyMappingConfiguration;
            });

        $this->fileUpload->initializeFormElement();
    }

    #[Test]
    public function afterBuildingFinishedDoesNotAddMimeTypeValidatorWhenNoMimeTypesConfigured(): void
    {
        $this->processingRule
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        $this->fileUpload
            ->method('getProperties')
            ->willReturn(['allowedMimeTypes' => []]);

        $this->processingRule
            ->expects($this->never())
            ->method('addValidator');

        $this->fileUpload->initializeFormElement();
    }

    #[Test]
    public function afterBuildingFinishedKeepsExistingValidatorsInProcessingRule(): void
    {
        $notEmptyValidator = new NotEmptyValidator();
        $notEmptyValidator->setOptions([]);

        $validators = new \SplObjectStorage();
        $validators->offsetSet($notEmptyValidator);

        $this->processingRule
            ->method('getValidators')
            ->willReturn($validators);

        $this->fileUpload
            ->method('getProperties')
            ->willReturn(['allowedMimeTypes' => []]);

        $this->fileUpload->initializeFormElement();

        self::assertTrue($validators->offsetExists($notEmptyValidator));
    }

    #[Test]
    public function initializeFormElementSkipsUploadFolderResolutionInPreviewMode(): void
    {
        // In preview mode, ResourceFactory should never be called
        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->expects($this->never())->method('getFolderObjectFromCombinedIdentifier');
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);

        $this->processingRule
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        $this->fileUpload
            ->method('getProperties')
            ->willReturn(['saveToFileMount' => '2:/secureUploads/']);

        // Set previewMode rendering option on the root form
        $this->rootForm->setRenderingOption('previewMode', true);

        // Expect the upload configuration to NOT contain an upload folder key
        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function (string $typeConverter, array $options): ExtbasePropertyMappingConfiguration {
                $this->assertArrayNotHasKey(UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER, $options);

                return $this->extbasePropertyMappingConfiguration;
            });

        $this->fileUpload->initializeFormElement();
    }

    #[Test]
    public function initializeFormElementHandlesInsufficientFolderAccessPermissionsGracefully(): void
    {
        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->method('getFolderObjectFromCombinedIdentifier')
            ->willThrowException(new InsufficientFolderAccessPermissionsException('Access denied', 1430317630));
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);

        $this->processingRule
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        $this->rootForm
            ->method('getPersistenceIdentifier')
            ->willReturn('');

        $this->fileUpload
            ->method('getProperties')
            ->willReturn(['saveToFileMount' => '2:/secureUploads/']);

        // Should not throw, and should not contain an upload folder since access is denied
        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function (string $typeConverter, array $options): ExtbasePropertyMappingConfiguration {
                $this->assertArrayNotHasKey(UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER, $options);

                return $this->extbasePropertyMappingConfiguration;
            });

        $this->fileUpload->initializeFormElement();
    }

    #[Test]
    public function initializeFormElementHandlesFolderDoesNotExistExceptionGracefully(): void
    {
        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->method('getFolderObjectFromCombinedIdentifier')
            ->willThrowException(new FolderDoesNotExistException('Folder not found', 1314516809));
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);

        $this->processingRule
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        $this->rootForm
            ->method('getPersistenceIdentifier')
            ->willReturn('');

        $this->fileUpload
            ->method('getProperties')
            ->willReturn(['saveToFileMount' => '2:/nonExistentFolder/']);

        // Should not throw, and should not contain an upload folder since folder doesn't exist
        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function (string $typeConverter, array $options): ExtbasePropertyMappingConfiguration {
                $this->assertArrayNotHasKey(UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER, $options);

                return $this->extbasePropertyMappingConfiguration;
            });

        $this->fileUpload->initializeFormElement();
    }
}
