<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Mvc\Property;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration as ExtbasePropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Mvc\ProcessingRule;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\CMS\Form\Mvc\Validation\MimeTypeValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PropertyMappingConfigurationTest extends UnitTestCase
{
    /** @var PropertyMappingConfiguration */
    protected $propertyMappingConfiguration;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ExtbasePropertyMappingConfiguration */
    protected $extbasePropertyMappingConfiguration;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FileUpload */
    protected $fileUpload;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormDefinition */
    protected $rootForm;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessingRule */
    protected $processingRule;

    protected $singletons = [];

    public function setUp()
    {
        $this->singletons = GeneralUtility::getSingletonInstances();

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
            ->expects($this->any())
            ->method('getPropertyMappingConfiguration')
            ->willReturn($this->extbasePropertyMappingConfiguration);

        // Root Form
        $this->rootForm = $this->getMockBuilder(FormDefinition::class)
            ->setMethods(['getProcessingRule', 'getPersistenceIdentifier', 'getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->rootForm
            ->expects($this->any())
            ->method('getProcessingRule')
            ->willReturn($this->processingRule);

        // File Upload
        $this->fileUpload = $this->getMockBuilder(FileUpload::class)
            ->setMethods(['getProperties', 'getRootForm', 'getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileUpload
            ->expects($this->any())
            ->method('getRootForm')
            ->willReturn($this->rootForm);

        $this->fileUpload
            ->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('foobar');

        // Property Mapping Configuration
        $this->propertyMappingConfiguration = new PropertyMappingConfiguration();
    }

    public function tearDown()
    {
        // Remove all singleton instances
        GeneralUtility::resetSingletonInstances($this->singletons);
    }

    /**
     * A bare minimum test that checks if the function maybe potentially works.
     * @test
     */
    public function afterBuildingFinishedAddsFileReferenceConverter()
    {
        // Mime Type Validator
        /** @var \PHPUnit_Framework_MockObject_MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->createMock(MimeTypeValidator::class);

        // Resource Factory
        /** @var \PHPUnit_Framework_MockObject_MockObject|ResourceFactory $resourceFactory */
        $resourceFactory = $this->createMock(ResourceFactory::class);

        // Object Manager (in order to return mocked Resource Factory)
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [MimeTypeValidator::class, $mimeTypeValidator],
                [ResourceFactory::class, $resourceFactory]
            ]);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        // No validators
        $this->processingRule
            ->expects($this->any())
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        // Mime Types not important
        $this->fileUpload
            ->expects($this->any())
            ->method('getProperties')
            ->willReturn(['allowedMimeTypes' => []]);

        // Check if the UploadFileReference is included
        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
            ->method('setTypeConverterOptions')
            ->with(UploadedFileReferenceConverter::class);

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }

    /**
     * @test
     */
    public function afterBuildingFinishedAddsMimeTypeConverter()
    {
        $mimeTypes = ['allowedMimeTypes' => ['text/plain', 'application/x-www-form-urlencoded']];

        // Create a MimeTypeValidator Mock
        /** @var \PHPUnit_Framework_MockObject_MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->getMockBuilder(MimeTypeValidator::class)
            ->setMethods(['__construct'])
            ->disableOriginalConstructor()
            ->getMock();

        // Object Manager to return the MimeTypeValidator
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager
            ->expects($this->any())
            ->method('get')
            ->with(MimeTypeValidator::class, $mimeTypes)
            ->willReturn($mimeTypeValidator);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        // Don't add any validators for now
        $this->processingRule
            ->expects($this->any())
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        // Add some Mime types
        $this->fileUpload
            ->expects($this->any())
            ->method('getProperties')
            ->willReturn($mimeTypes);

        // Expect the array to contain the MimeTypeValidator
        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
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
    public function afterBuildingFinishedSetsUpStoragePathToPropertySaveToFileMountIfItExists()
    {
        // Mime Type Validator
        /** @var \PHPUnit_Framework_MockObject_MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->createMock(MimeTypeValidator::class);

        // Resource Factory
        /** @var \PHPUnit_Framework_MockObject_MockObject|ResourceFactory $resourceFactory */
        $resourceFactory = $this->createMock(ResourceFactory::class);

        // Object Manager (in order to return mocked Resource Factory)
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [MimeTypeValidator::class, $mimeTypeValidator],
                [ResourceFactory::class, $resourceFactory]
            ]);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        // Don't add any validators for now
        $this->processingRule
            ->expects($this->any())
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        // Set the file mount
        $this->fileUpload
            ->expects($this->any())
            ->method('getProperties')
            ->willReturn(['saveToFileMount' => '/tmp']);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
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
    public function afterBuildingFinishedSetsUpStoragePathToToFormDefinitionPathIfSaveToFileMountIsNotDefinedAndFormWasNotAddedProgrammatically()
    {
        // Mime Type Validator
        /** @var \PHPUnit_Framework_MockObject_MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->createMock(MimeTypeValidator::class);

        // Resource Factory
        /** @var \PHPUnit_Framework_MockObject_MockObject|ResourceFactory $resourceFactory */
        $resourceFactory = $this->createMock(ResourceFactory::class);

        // Object Manager (in order to return mocked Resource Factory)
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [MimeTypeValidator::class, $mimeTypeValidator],
                [ResourceFactory::class, $resourceFactory]
            ]);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        // Don't add any validators for now
        $this->processingRule
            ->expects($this->any())
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        $this->rootForm
            ->expects($this->any())
            ->method('getPersistenceIdentifier')
            ->willReturn('/tmp/somefile');

        // Set the file mount
        $this->fileUpload
            ->expects($this->any())
            ->method('getProperties')
            ->willReturn(['saveToFileMount' => '']);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
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
    public function afterBuildingFinishedSetsStoragePathToUserUploadIfNeitherSaveToFileMountIsSetNorThereIsAFormDefinitionPath()
    {
        // Mime Type Validator
        /** @var \PHPUnit_Framework_MockObject_MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->createMock(MimeTypeValidator::class);

        // Resource Factory
        /** @var \PHPUnit_Framework_MockObject_MockObject|ResourceFactory $resourceFactory */
        $resourceFactory = $this->createMock(ResourceFactory::class);

        // Object Manager (in order to return mocked Resource Factory)
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [MimeTypeValidator::class, $mimeTypeValidator],
                [ResourceFactory::class, $resourceFactory]
            ]);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);

        // Don't add any validators for now
        $this->processingRule
            ->expects($this->any())
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

        $this->rootForm
            ->expects($this->any())
            ->method('getPersistenceIdentifier')
            ->willReturn('');

        // Set the file mount
        $this->fileUpload
            ->expects($this->any())
            ->method('getProperties')
            ->willReturn(['saveToFileMount' => '']);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function ($class, $config) {
                $this->assertArrayNotHasKey(UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER, $config);
            });

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }

    /**
     * @test
     */
    public function afterBuildingFinishedCopiesValidators()
    {
        // Mime Type Validator
        /** @var \PHPUnit_Framework_MockObject_MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->createMock(MimeTypeValidator::class);

        // Some other Validator
        $otherValidator = $this->getMockForAbstractClass(AbstractValidator::class);

        // Resource Factory
        /** @var \PHPUnit_Framework_MockObject_MockObject|ResourceFactory $resourceFactory */
        $resourceFactory = $this->createMock(ResourceFactory::class);

        // Object Manager (in order to return mocked Resource Factory)
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager
            ->expects($this->any())
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
            ->expects($this->any())
            ->method('getValidators')
            ->willReturn($validators);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
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
    public function afterBuildingFinishedDoesNotCopyNotEmptyValidator()
    {
        // Mime Type Validator
        /** @var \PHPUnit_Framework_MockObject_MockObject|MimeTypeValidator $mimeTypeValidator */
        $mimeTypeValidator = $this->createMock(MimeTypeValidator::class);

        // Not Empty Validator
        $notEmptyValidator = $this->getMockForAbstractClass(NotEmptyValidator::class);

        // Resource Factory
        /** @var \PHPUnit_Framework_MockObject_MockObject|ResourceFactory $resourceFactory */
        $resourceFactory = $this->createMock(ResourceFactory::class);

        // Object Manager (in order to return mocked Resource Factory)
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager
            ->expects($this->any())
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
            ->expects($this->any())
            ->method('getValidators')
            ->willReturn($validators);

        // Expect the array to contain the /tmp upload directory
        $this->extbasePropertyMappingConfiguration
            ->expects($this->atLeastOnce())
            ->method('setTypeConverterOptions')
            ->willReturnCallback(function ($class, $config) use ($notEmptyValidator) {
                $this->assertArrayHasKey(UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS, $config);
                $validators = $config[UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS];

                self::assertNotContains($notEmptyValidator, $validators);
            });

        $this->propertyMappingConfiguration->afterBuildingFinished($this->fileUpload);
    }
}
