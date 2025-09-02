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

namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Property;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration as ExtbasePropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Mvc\ProcessingRule;
use TYPO3\CMS\Form\Mvc\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\CMS\Form\Mvc\Validation\MimeTypeValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PropertyMappingConfigurationTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected PropertyMappingConfiguration $propertyMappingConfiguration;
    protected ExtbasePropertyMappingConfiguration&MockObject $extbasePropertyMappingConfiguration;
    protected FileUpload&MockObject $fileUpload;
    protected FormDefinition&MockObject $rootForm;
    protected ProcessingRule&MockObject $processingRule;

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
            ->onlyMethods(['getValidators', 'removeValidator', 'getPropertyMappingConfiguration'])
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
        $this->fileUpload = $this->getMockBuilder(FileUpload::class)
            ->onlyMethods(['getProperties', 'getRootForm', 'getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileUpload
            ->method('getRootForm')
            ->willReturn($this->rootForm);

        $this->fileUpload
            ->method('getIdentifier')
            ->willReturn('foobar');

        $this->propertyMappingConfiguration = new PropertyMappingConfiguration($this->createMock(ValidatorResolver::class));
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
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->with(UploadedFileReferenceConverter::class);

        // Property Mapping Configuration
        $propertyMappingConfiguration = new PropertyMappingConfiguration($this->createMock(ValidatorResolver::class));
        $propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
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

        // Expect the array to contain the MimeTypeValidator
        $this->extbasePropertyMappingConfiguration
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function (string $typeConverter, array $options): ExtbasePropertyMappingConfiguration {
                $this->assertArrayHasKey(UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS, $options);
                $validators = $options[UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS];

                $this->assertInstanceOf(MimeTypeValidator::class, $validators[0]);

                return $this->extbasePropertyMappingConfiguration;
            });

        $mimeTypeValidator = new MimeTypeValidator();
        $mimeTypeValidator->setOptions(['allowedMimeTypes' => []]);
        $validatorResolver = $this->createMock(ValidatorResolver::class);
        $validatorResolver->method('createValidator')->with(
            MimeTypeValidator::class,
            ['allowedMimeTypes' => ['text/plain', 'application/x-www-form-urlencoded']]
        )->willReturn($mimeTypeValidator);
        $propertyMappingConfiguration = new PropertyMappingConfiguration($validatorResolver);
        $propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
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
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function (string $typeConverter, array $options): ExtbasePropertyMappingConfiguration {
                $this->assertArrayHasKey(UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER, $options);
                $folder = $options[UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER];

                $this->assertSame('/tmp', $folder);

                return $this->extbasePropertyMappingConfiguration;
            });

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
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
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function (string $typeConverter, array $options): ExtbasePropertyMappingConfiguration {
                $this->assertArrayHasKey(UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER, $options);
                $folder = $options[UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER];

                $this->assertSame('/tmp', $folder);

                return $this->extbasePropertyMappingConfiguration;
            });

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }

    #[Test]
    public function afterBuildingFinishedSetsStoragePathToUserUploadIfNeitherSaveToFileMountIsSetNorThereIsAFormDefinitionPath(): void
    {
        // Don't add any validators for now
        $this->processingRule
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        $this->rootForm
            ->method('getPersistenceIdentifier')
            ->willReturn('');

        // Set the file mount
        $this->fileUpload
            ->method('getProperties')
            ->willReturn(['saveToFileMount' => '']);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function (string $typeConverter, array $options): ExtbasePropertyMappingConfiguration {
                $this->assertArrayNotHasKey(UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER, $options);

                return $this->extbasePropertyMappingConfiguration;
            });

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }

    #[Test]
    public function afterBuildingFinishedCopiesValidators(): void
    {
        // Some other Validator
        $otherValidator = $this->createMock(ValidatorInterface::class);

        // Don't add any validators for now
        $validators = new \SplObjectStorage();
        $validators->offsetSet($otherValidator);

        $this->processingRule
            ->method('getValidators')
            ->willReturn($validators);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function (string $typeConverter, array $options) use ($otherValidator): ExtbasePropertyMappingConfiguration {
                $this->assertArrayHasKey(UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS, $options);
                $validators = $options[UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS];

                self::assertContains($otherValidator, $validators);

                return $this->extbasePropertyMappingConfiguration;
            });

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }

    #[Test]
    public function afterBuildingFinishedDoesNotCopyNotEmptyValidator(): void
    {
        // Not Empty Validator
        $notEmptyValidator = new NotEmptyValidator();
        $notEmptyValidator->setOptions([]);

        // Don't add any validators for now
        $validators = new \SplObjectStorage();
        $validators->offsetSet($notEmptyValidator);

        $this->processingRule
            ->method('getValidators')
            ->willReturn($validators);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function (string $typeConverter, array $options) use ($notEmptyValidator): ExtbasePropertyMappingConfiguration {
                $this->assertArrayHasKey(UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS, $options);
                $validators = $options[UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS];

                self::assertNotContains($notEmptyValidator, $validators);

                return $this->extbasePropertyMappingConfiguration;
            });

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }
}
