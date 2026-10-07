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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Property;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime\FormSession;
use TYPO3\CMS\Form\Mvc\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PropertyMappingConfigurationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected array $additionalFoldersToCreate = [
        '/fileadmin/user_upload',
        '/fileadmin/custom_uploads',
        '/fileadmin/form_definitions',
    ];

    private PropertyMappingConfiguration $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ExtbaseConfigurationManagerInterface::class)->setRequest($request);

        $this->subject = $this->get(PropertyMappingConfiguration::class);
    }

    /**
     * The element's own properties are applied after FileUpload::initializeFormElement(),
     * so a value differing from the prototype default must still be picked up.
     */
    #[Test]
    public function saveToFileMountOfFormElementIsUsedAsUploadFolder(): void
    {
        $formDefinition = $this->buildFormDefinition(['saveToFileMount' => '1:/custom_uploads/']);

        $this->subject->afterFormStateInitialized($this->createFormRuntime($formDefinition));

        self::assertSame('1:/custom_uploads/', $this->getUploadFolder($formDefinition));
    }

    #[Test]
    public function folderOfFormDefinitionIsUsedAsUploadFolderIfSaveToFileMountIsEmpty(): void
    {
        $formDefinition = $this->buildFormDefinition(['saveToFileMount' => ''], '1:/form_definitions/contact.form.yaml');

        $this->subject->afterFormStateInitialized($this->createFormRuntime($formDefinition));

        self::assertSame('1:/form_definitions', $this->getUploadFolder($formDefinition));
    }

    #[Test]
    public function uploadFolderIsNotSetIfSaveToFileMountDoesNotExist(): void
    {
        $formDefinition = $this->buildFormDefinition(['saveToFileMount' => '1:/does_not_exist/']);

        $this->subject->afterFormStateInitialized($this->createFormRuntime($formDefinition));

        self::assertNull($this->getUploadFolder($formDefinition));
    }

    #[Test]
    public function uploadFolderIsNotSetIfAccessToSaveToFileMountIsDenied(): void
    {
        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->method('getFolderObjectFromCombinedIdentifier')
            ->willThrowException(new InsufficientFolderAccessPermissionsException('Access denied', 1791380637));
        $subject = new PropertyMappingConfiguration($resourceFactory);
        $formDefinition = $this->buildFormDefinition(['saveToFileMount' => '1:/custom_uploads/']);

        $subject->afterFormStateInitialized($this->createFormRuntime($formDefinition));

        self::assertNull($this->getUploadFolder($formDefinition));
    }

    /**
     * Backend users previewing a form in the form editor may lack access to the upload storage.
     */
    #[Test]
    public function uploadFolderIsNotResolvedInPreviewMode(): void
    {
        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->expects($this->never())->method('getFolderObjectFromCombinedIdentifier');
        $subject = new PropertyMappingConfiguration($resourceFactory);
        $formDefinition = $this->buildFormDefinition(['saveToFileMount' => '1:/custom_uploads/']);
        $formDefinition->setRenderingOption('previewMode', true);

        $subject->afterFormStateInitialized($this->createFormRuntime($formDefinition));

        self::assertNull($this->getUploadFolder($formDefinition));
    }

    private function buildFormDefinition(array $fileUploadProperties, ?string $persistenceIdentifier = null): FormDefinition
    {
        $configuration = [
            'identifier' => 'test-form',
            'prototypeName' => 'standard',
            'type' => 'Form',
            'renderables' => [
                [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'renderables' => [
                        [
                            'identifier' => 'file-1',
                            'type' => 'FileUpload',
                            'properties' => $fileUploadProperties,
                        ],
                    ],
                ],
            ],
        ];
        if ($persistenceIdentifier !== null) {
            $configuration['persistenceIdentifier'] = $persistenceIdentifier;
        }

        return $this->get(ArrayFormFactory::class)->build($configuration);
    }

    private function createFormRuntime(FormDefinition $formDefinition): FormRuntime
    {
        $formRuntime = $this->createMock(FormRuntime::class);
        $formRuntime->method('getFormDefinition')->willReturn($formDefinition);
        $formRuntime->method('getFormSession')->willReturn(new FormSession());
        $formRuntime->method('canProcessFormSubmission')->willReturn(true);

        return $formRuntime;
    }

    private function getUploadFolder(FormDefinition $formDefinition): mixed
    {
        return $formDefinition->getProcessingRule('file-1')
            ->getPropertyMappingConfiguration()
            ->getConfigurationValue(UploadedFileReferenceConverter::class, UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER);
    }
}
