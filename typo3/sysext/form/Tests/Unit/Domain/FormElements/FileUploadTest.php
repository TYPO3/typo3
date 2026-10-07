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

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration as ExtbasePropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Mvc\ProcessingRule;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
final class FileUploadTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private ExtbasePropertyMappingConfiguration&MockObject $extbasePropertyMappingConfiguration;
    private FileUpload&MockObject $fileUpload;
    private FormDefinition $rootForm;
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
        $this->rootForm = new FormDefinition('root');
        GeneralUtility::addInstance(ProcessingRule::class, $this->processingRule);

        // File Upload
        $this->fileUpload = $this->getMockBuilder(FileUpload::class)
            ->onlyMethods(['getRootForm', 'getProperties'])
            ->setConstructorArgs(['foo', 'FileUpload'])
            ->getMock();

        $this->fileUpload
            ->method('getRootForm')
            ->willReturnCallback(fn(): FormDefinition => $this->rootForm);
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
    public function afterBuildingFinishedSetsStoragePathToUserUploadIfNeitherSaveToFileMountIsSetNorThereIsAFormDefinitionPath(): void
    {
        $this->processingRule
            ->method('getValidators')
            ->willReturn(new \SplObjectStorage());

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
}
