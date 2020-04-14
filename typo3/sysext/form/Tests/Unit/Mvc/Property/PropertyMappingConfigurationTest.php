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

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration as ExtbasePropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Mvc\ProcessingRule;
use TYPO3\CMS\Form\Mvc\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\CMS\Form\Mvc\Validation\MimeTypeValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PropertyMappingConfigurationTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /** @var PropertyMappingConfiguration */
    protected $propertyMappingConfiguration;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExtbasePropertyMappingConfiguration */
    protected $extbasePropertyMappingConfiguration;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileUpload */
    protected $fileUpload;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormDefinition */
    protected $rootForm;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProcessingRule */
    protected $processingRule;

    public function setUp(): void
    {
        parent::setUp();
        // Property Mapping Configuration
        $this->extbasePropertyMappingConfiguration = $this->getMockBuilder(ExtbasePropertyMappingConfiguration::class)
            ->setMethods(['setTypeConverterOptions'])
            ->disableOriginalConstructor()
            ->getMock();

        // Processing Rules
        $this->processingRule = $this->getMockBuilder(ProcessingRule::class)
            ->setMethods(['getValidators', 'removeValidator', 'getPropertyMappingConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->processingRule
            ->expects(self::any())
            ->method('getPropertyMappingConfiguration')
            ->willReturn($this->extbasePropertyMappingConfiguration);

        // Root Form
        $this->rootForm = $this->getMockBuilder(FormDefinition::class)
            ->setMethods(['getProcessingRule', 'getPersistenceIdentifier', 'getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->rootForm
            ->expects(self::any())
            ->method('getProcessingRule')
            ->willReturn($this->processingRule);

        // File Upload
        $this->fileUpload = $this->getMockBuilder(FileUpload::class)
            ->setMethods(['getProperties', 'getRootForm', 'getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileUpload
            ->expects(self::any())
            ->method('getRootForm')
            ->willReturn($this->rootForm);

        $this->fileUpload
            ->expects(self::any())
            ->method('getIdentifier')
            ->willReturn('foobar');

        // Property Mapping Configuration
        $this->propertyMappingConfiguration = new PropertyMappingConfiguration();
    }

    /**
     * A bare minimum test that checks if the function maybe potentially works.
     * @test
     */
    public function afterBuildingFinishedAddsFileReferenceConverter(): void
    {
        // Mime Type Validator
        /** @var \PHPUnit\Framework\MockObject\MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->createMock(MimeTypeValidator::class);

        // Resource Factory
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceFactory $resourceFactory */
        $resourceFactory = $this->createMock(ResourceFactory::class);

        // Object Manager (in order to return mocked Resource Factory)
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(MimeTypeValidator::class)->willReturn($mimeTypeValidator);
        $objectManager->get(ResourceFactory::class)->willReturn($resourceFactory);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager->reveal());

        // No validators
        $this->processingRule
            ->expects(self::any())
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        // Mime Types not important
        $this->fileUpload
            ->expects(self::any())
            ->method('getProperties')
            ->willReturn(['allowedMimeTypes' => []]);

        // Check if the UploadFileReference is included
        $this->extbasePropertyMappingConfiguration
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->with(UploadedFileReferenceConverter::class);

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }

    /**
     * @test
     */
    public function afterBuildingFinishedAddsMimeTypeConverter(): void
    {
        $mimeTypes = ['allowedMimeTypes' => ['text/plain', 'application/x-www-form-urlencoded']];

        // Create a MimeTypeValidator Mock
        /** @var \PHPUnit\Framework\MockObject\MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->getMockBuilder(MimeTypeValidator::class)
            ->setMethods(['__construct'])
            ->disableOriginalConstructor()
            ->getMock();

        // Object Manager to return the MimeTypeValidator
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(MimeTypeValidator::class, $mimeTypes)->willReturn($mimeTypeValidator);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager->reveal());

        // Don't add any validators for now
        $this->processingRule
            ->expects(self::any())
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        // Add some Mime types
        $this->fileUpload
            ->expects(self::any())
            ->method('getProperties')
            ->willReturn($mimeTypes);

        // Expect the array to contain the MimeTypeValidator
        $this->extbasePropertyMappingConfiguration
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function ($class, $config) {
                $this->assertArrayHasKey(UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS, $config);
                $validators = $config[UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS];

                $this->assertInstanceOf(MimeTypeValidator::class, $validators[0]);
            });

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }

    /**
     * @test
     */
    public function afterBuildingFinishedSetsUpStoragePathToPropertySaveToFileMountIfItExists(): void
    {
        // Mime Type Validator
        /** @var \PHPUnit\Framework\MockObject\MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->createMock(MimeTypeValidator::class);

        // Resource Factory
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceFactory $resourceFactory */
        $resourceFactory = $this->createMock(ResourceFactory::class);

        // Object Manager (in order to return mocked Resource Factory)
        /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([
                [MimeTypeValidator::class, $mimeTypeValidator],
                [ResourceFactory::class, $resourceFactory]
            ]);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        // Don't add any validators for now
        $this->processingRule
            ->expects(self::any())
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        // Set the file mount
        $this->fileUpload
            ->expects(self::any())
            ->method('getProperties')
            ->willReturn(['saveToFileMount' => '/tmp']);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function ($class, $config) {
                $this->assertArrayHasKey(UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER, $config);
                $folder = $config[UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER];

                $this->assertSame('/tmp', $folder);
            });

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }

    /**
     * @test
     */
    public function afterBuildingFinishedSetsUpStoragePathToToFormDefinitionPathIfSaveToFileMountIsNotDefinedAndFormWasNotAddedProgrammatically(): void
    {
        // Mime Type Validator
        /** @var \PHPUnit\Framework\MockObject\MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->createMock(MimeTypeValidator::class);

        // Resource Factory
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceFactory $resourceFactory */
        $resourceFactory = $this->createMock(ResourceFactory::class);

        // Object Manager (in order to return mocked Resource Factory)
        /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([
                [MimeTypeValidator::class, $mimeTypeValidator],
                [ResourceFactory::class, $resourceFactory]
            ]);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        // Don't add any validators for now
        $this->processingRule
            ->expects(self::any())
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        $this->rootForm
            ->expects(self::any())
            ->method('getPersistenceIdentifier')
            ->willReturn('/tmp/somefile');

        // Set the file mount
        $this->fileUpload
            ->expects(self::any())
            ->method('getProperties')
            ->willReturn(['saveToFileMount' => '']);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function ($class, $config) {
                $this->assertArrayHasKey(UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER, $config);
                $folder = $config[UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER];

                $this->assertSame('/tmp', $folder);
            });

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }

    /**
     * @test
     */
    public function afterBuildingFinishedSetsStoragePathToUserUploadIfNeitherSaveToFileMountIsSetNorThereIsAFormDefinitionPath(): void
    {
        // Mime Type Validator
        /** @var \PHPUnit\Framework\MockObject\MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->createMock(MimeTypeValidator::class);

        // Resource Factory
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceFactory $resourceFactory */
        $resourceFactory = $this->createMock(ResourceFactory::class);

        // Object Manager (in order to return mocked Resource Factory)
        /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([
                [MimeTypeValidator::class, $mimeTypeValidator],
                [ResourceFactory::class, $resourceFactory]
            ]);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        // Don't add any validators for now
        $this->processingRule
            ->expects(self::any())
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        $this->rootForm
            ->expects(self::any())
            ->method('getPersistenceIdentifier')
            ->willReturn('');

        // Set the file mount
        $this->fileUpload
            ->expects(self::any())
            ->method('getProperties')
            ->willReturn(['saveToFileMount' => '']);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function ($class, $config) {
                $this->assertArrayNotHasKey(UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER, $config);
            });

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }

    /**
     * @test
     */
    public function afterBuildingFinishedCopiesValidators(): void
    {
        // Mime Type Validator
        /** @var \PHPUnit\Framework\MockObject\MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->createMock(MimeTypeValidator::class);

        // Some other Validator
        $otherValidator = $this->getMockForAbstractClass(AbstractValidator::class);

        // Resource Factory
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceFactory $resourceFactory */
        $resourceFactory = $this->createMock(ResourceFactory::class);

        // Object Manager (in order to return mocked Resource Factory)
        /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([
                [MimeTypeValidator::class, $mimeTypeValidator],
                [ResourceFactory::class, $resourceFactory]
            ]);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        // Don't add any validators for now
        $validators = new \SplObjectStorage();
        $validators->attach($otherValidator);

        $this->processingRule
            ->expects(self::any())
            ->method('getValidators')
            ->willReturn($validators);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function ($class, $config) use ($otherValidator) {
                $this->assertArrayHasKey(UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS, $config);
                $validators = $config[UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS];

                self::assertContains($otherValidator, $validators);
            });

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }

    /**
     * @test
     */
    public function afterBuildingFinishedDoesNotCopyNotEmptyValidator(): void
    {
        // Mime Type Validator
        /** @var \PHPUnit\Framework\MockObject\MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->createMock(MimeTypeValidator::class);

        // Not Empty Validator
        $notEmptyValidator = $this->getMockForAbstractClass(NotEmptyValidator::class);

        // Resource Factory
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceFactory $resourceFactory */
        $resourceFactory = $this->createMock(ResourceFactory::class);

        // Object Manager (in order to return mocked Resource Factory)
        /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([
                [MimeTypeValidator::class, $mimeTypeValidator],
                [ResourceFactory::class, $resourceFactory]
            ]);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        // Don't add any validators for now
        $validators = new \SplObjectStorage();
        $validators->attach($notEmptyValidator);

        $this->processingRule
            ->expects(self::any())
            ->method('getValidators')
            ->willReturn($validators);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects(self::atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function ($class, $config) use ($notEmptyValidator) {
                $this->assertArrayHasKey(UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS, $config);
                $validators = $config[UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS];

                self::assertNotContains($notEmptyValidator, $validators);
            });

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }
}
