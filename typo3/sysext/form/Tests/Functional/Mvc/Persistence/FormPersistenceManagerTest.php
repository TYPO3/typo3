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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Persistence;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Form\Mvc\Configuration\TypoScriptService;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniqueIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;
use TYPO3\CMS\Form\Slot\FilePersistenceSlot;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormPersistenceManagerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    #[Test]
    public function loadThrowsExceptionIfPersistenceIdentifierHasNoYamlExtension(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1477679819);
        $subject = new FormPersistenceManager(
            $this->createMock(YamlSource::class),
            $this->createMock(StorageRepository::class),
            new FilePersistenceSlot(new HashService()),
            $this->createMock(ResourceFactory::class),
            $this->createMock(FrontendInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TypoScriptService::class)
        );
        $subject->load('-1:/user_uploads/_example.php', [], []);
    }

    #[Test]
    public function loadThrowsExceptionIfPersistenceIdentifierIsAExtensionLocationWhichIsNotAllowed(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1484071985);
        $subject = new FormPersistenceManager(
            $this->createMock(YamlSource::class),
            $this->createMock(StorageRepository::class),
            new FilePersistenceSlot(new HashService()),
            $this->createMock(ResourceFactory::class),
            $this->createMock(FrontendInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TypoScriptService::class)
        );
        $formSettings = [
            'persistenceManager' => [
                'allowedExtensionPaths' => [],
            ],
        ];
        $subject->load('EXT:form/Resources/Forms/_example.form.yaml', $formSettings, []);
    }

    #[Test]
    public function loadReturnsFormArray(): void
    {
        $formSettings = [
            'persistenceManager' => [
                'allowedExtensionPaths' => ['EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/'],
            ],
        ];
        $expected = [
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'label' => 'Label',
            'renderables' => [
                0 => [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Label',
                    'renderables' => [
                        0 => [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'label' => 'Label',
                        ],
                    ],
                ],
            ],
        ];
        $subject = $this->get(FormPersistenceManager::class);
        $result = $subject->load('EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/Simple.form.yaml', $formSettings, []);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function loadReturnsOverriddenConfigurationIfTypoScriptOverridesExists(): void
    {
        $formSettings = [
            'persistenceManager' => [
                'allowedExtensionPaths' => ['EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/'],
            ],
        ];
        $typoScriptSettings = [
            'formDefinitionOverrides' => [
                'ext-form-identifier' => [
                    'label' => 'Label override',
                    'renderables' => [
                        0 => [
                            'renderables' => [
                                0 => [
                                    'label' => 'Label override',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'label' => 'Label override',
            'renderables' => [
                0 => [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Label',
                    'renderables' => [
                        0 => [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'label' => 'Label override',
                        ],
                    ],
                ],
            ],
        ];
        $subject = $this->get(FormPersistenceManager::class);
        $result = $subject->load('EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/Simple.form.yaml', $formSettings, $typoScriptSettings);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function overrideFormDefinitionDoesNotEvaluateTypoScriptLookalikeInstructionsFromYamlSettings(): void
    {
        $formSettings = [
            'persistenceManager' => [
                'allowedExtensionPaths' => ['EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/'],
            ],
        ];
        $typoScriptSettings = [
            'formDefinitionOverrides' => [
                'ext-form-identifier' => [
                    'renderables' => [
                        0 => [
                            'renderables' => [
                                0 => [
                                    'label' => [
                                        'value' => 'Label override',
                                        '_typoScriptNodeValue' => 'TEXT',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $formDefinitionYaml = [
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'label' => [
                'value' => 'Label override',
                '_typoScriptNodeValue' => 'TEXT',
            ],
            'renderables' => [
                0 => [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Label',
                    'renderables' => [
                        0 => [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'label' => 'Label',
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'label' => [
                'value' => 'Label override',
                '_typoScriptNodeValue' => 'TEXT',
            ],
            'renderables' => [
                0 => [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Label',
                    'renderables' => [
                        0 => [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'label' =>  'Label override',
                        ],
                    ],
                ],
            ],
        ];
        $evaluatedFormTypoScript = [
            'renderables' => [
                0 => [
                    'renderables' => [
                        0 => [
                            'label' => 'Label override',
                        ],
                    ],
                ],
            ],
        ];
        $typoScriptServiceMock = $this->createMock(TypoScriptService::class);
        $typoScriptServiceMock->method('resolvePossibleTypoScriptConfiguration')
            ->with($typoScriptSettings['formDefinitionOverrides']['ext-form-identifier'])
            ->willReturn($evaluatedFormTypoScript);
        $yamlSourceMock = $this->createMock(YamlSource::class);
        $yamlSourceMock->method('load')->willReturn($formDefinitionYaml);
        $subject = new FormPersistenceManager(
            $yamlSourceMock,
            $this->get(StorageRepository::class),
            $this->get(FilePersistenceSlot::class),
            $this->get(ResourceFactory::class),
            $this->createMock(FrontendInterface::class),
            $this->get(EventDispatcherInterface::class),
            $typoScriptServiceMock
        );
        $result = $subject->load('EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/Simple.form.yaml', $formSettings, $typoScriptSettings);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function saveThrowsExceptionIfPersistenceIdentifierHasNoYamlExtension(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1477679820);
        $subject = new FormPersistenceManager(
            $this->createMock(YamlSource::class),
            $this->createMock(StorageRepository::class),
            new FilePersistenceSlot(new HashService()),
            $this->createMock(ResourceFactory::class),
            $this->createMock(FrontendInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TypoScriptService::class)
        );
        $subject->save('-1:/user_uploads/_example.php', [], []);
    }

    #[Test]
    public function saveThrowsExceptionIfPersistenceIdentifierIsAExtensionLocationAndSaveToExtensionLocationIsNotAllowed(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1477680881);
        $subject = new FormPersistenceManager(
            $this->createMock(YamlSource::class),
            $this->createMock(StorageRepository::class),
            new FilePersistenceSlot(new HashService()),
            $this->createMock(ResourceFactory::class),
            $this->createMock(FrontendInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TypoScriptService::class)
        );
        $formSettings = [
            'persistenceManager' => [
                'allowSaveToExtensionPaths' => false,
            ],
        ];
        $subject->save('EXT:form/Resources/Forms/_example.form.yaml', [], $formSettings);
    }

    #[Test]
    public function saveThrowsExceptionIfPersistenceIdentifierIsAExtensionLocationWhichIsNotAllowed(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1484073571);
        $subject = new FormPersistenceManager(
            $this->createMock(YamlSource::class),
            $this->createMock(StorageRepository::class),
            new FilePersistenceSlot(new HashService()),
            $this->createMock(ResourceFactory::class),
            $this->createMock(FrontendInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TypoScriptService::class)
        );
        $formSettings = [
            'persistenceManager' => [
                'allowSaveToExtensionPaths' => true,
                'allowedExtensionPaths' => [],
            ],
        ];
        $subject->save('EXT:form/Resources/Forms/_example.form.yaml', [], $formSettings);
    }

    #[Test]
    public function deleteThrowsExceptionIfPersistenceIdentifierHasNoYamlExtension(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1472239534);
        $subject = new FormPersistenceManager(
            $this->createMock(YamlSource::class),
            $this->createMock(StorageRepository::class),
            new FilePersistenceSlot(new HashService()),
            $this->createMock(ResourceFactory::class),
            $this->createMock(FrontendInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TypoScriptService::class)
        );
        $subject->delete('-1:/user_uploads/_example.php', []);
    }

    #[Test]
    public function deleteThrowsExceptionIfPersistenceIdentifierFileDoesNotExists(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1472239535);
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            ['exists'],
            [
                $this->createMock(YamlSource::class),
                $this->createMock(StorageRepository::class),
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ]
        );
        $subjectMock->method('exists')->willReturn(false);
        $subjectMock->delete('-1:/user_uploads/_example.form.yaml', []);
    }

    #[Test]
    public function deleteThrowsExceptionIfPersistenceIdentifierIsExtensionLocationAndDeleteFromExtensionLocationsIsNotAllowed(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1472239536);
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            ['exists'],
            [
                $this->createMock(YamlSource::class),
                $this->createMock(StorageRepository::class),
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ]
        );
        $subjectMock->method('exists')->willReturn(true);
        $formSettings = [
            'persistenceManager' => [
                'allowDeleteFromExtensionPaths' => false,
            ],
        ];
        $subjectMock->delete('EXT:form/Resources/Forms/_example.form.yaml', $formSettings);
    }

    #[Test]
    public function deleteThrowsExceptionIfPersistenceIdentifierIsExtensionLocationWhichIsNotAllowed(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1484073878);
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            ['exists'],
            [
                $this->createMock(YamlSource::class),
                $this->createMock(StorageRepository::class),
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ]
        );
        $subjectMock->method('exists')->willReturn(true);
        $formSettings = [
            'persistenceManager' => [
                'allowDeleteFromExtensionPaths' => true,
                'allowedExtensionPaths' => [],
            ],
        ];
        $subjectMock->delete('EXT:form/Resources/Forms/_example.form.yaml', $formSettings);
    }

    #[Test]
    public function deleteThrowsExceptionIfPersistenceIdentifierIsStorageLocationAndDeleteFromStorageIsNotAllowed(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1472239516);
        $resourceStorageMock = $this->createMock(ResourceStorage::class);
        $resourceStorageMock->method('checkFileActionPermission')->willReturn(false);
        $file = new File(['name' => 'foo', 'identifier' => '', 'mime_type' => ''], $resourceStorageMock);
        $resourceStorageMock->method('getFile')->willReturn($file);
        $resourceStorageMock->method('isBrowsable')->willReturn(true);
        $storageRepositoryMock = $this->createMock(StorageRepository::class);
        $storageRepositoryMock->method('findByUid')->willReturn($resourceStorageMock);
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            ['exists'],
            [
                $this->createMock(YamlSource::class),
                $storageRepositoryMock,
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ]
        );
        $subjectMock->method('exists')->willReturn(true);
        $subjectMock->delete('-1:/user_uploads/_example.form.yaml', []);
    }

    #[Test]
    public function existsReturnsTrueIfPersistenceIdentifierIsExtensionLocationAndFileExistsAndFileHasYamlExtension(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            null,
            [
                $this->createMock(YamlSource::class),
                $this->createMock(StorageRepository::class),
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ]
        );
        $formSettings = [
            'persistenceManager' => [
                'allowedExtensionPaths' => [
                    'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/',
                ],
            ],
        ];
        self::assertTrue($subjectMock->_call('exists', 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/BlankForm.form.yaml', $formSettings));
    }

    #[Test]
    public function existsReturnsFalseIfPersistenceIdentifierIsExtensionLocationAndFileExistsAndFileHasNoYamlExtension(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            null,
            [
                $this->createMock(YamlSource::class),
                $this->createMock(StorageRepository::class),
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ]
        );
        self::assertFalse($subjectMock->_call('exists', 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/BlankForm.txt', []));
    }

    #[Test]
    public function existsReturnsFalseIfPersistenceIdentifierIsExtensionLocationAndFileExistsAndExtensionLocationIsNotAllowed(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            null,
            [
                $this->createMock(YamlSource::class),
                $this->createMock(StorageRepository::class),
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ]
        );
        $formSettings = [
            'persistenceManager' => [
                'allowedExtensionPaths' => [],
            ],
        ];
        self::assertFalse($subjectMock->_call('exists', 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/BlankForm.yaml', $formSettings));
    }

    #[Test]
    public function existsReturnsFalseIfPersistenceIdentifierIsExtensionLocationAndFileNotExistsAndFileHasYamlExtension(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            null,
            [
                $this->createMock(YamlSource::class),
                $this->createMock(StorageRepository::class),
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ]
        );
        self::assertFalse($subjectMock->_call('exists', 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/_BlankForm.yaml', []));
    }

    #[Test]
    public function existsReturnsTrueIfPersistenceIdentifierIsStorageLocationAndFileExistsAndFileHasYamlExtension(): void
    {
        $resourceStorageMock = $this->createMock(ResourceStorage::class);
        $resourceStorageMock->method('hasFile')->willReturn(true);
        $resourceStorageMock->method('isBrowsable')->willReturn(true);
        $storageRepositoryMock = $this->createMock(StorageRepository::class);
        $storageRepositoryMock->method('findByUid')->willReturn($resourceStorageMock);
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            null,
            [
                $this->createMock(YamlSource::class),
                $storageRepositoryMock,
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ]
        );
        self::assertTrue($subjectMock->_call('exists', '-1:/user_uploads/_example.form.yaml', []));
    }

    #[Test]
    public function existsReturnsFalseIfPersistenceIdentifierIsStorageLocationAndFileExistsAndFileNoYamlExtension(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            null,
            [
                $this->createMock(YamlSource::class),
                $this->createMock(StorageRepository::class),
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ]
        );
        self::assertFalse($subjectMock->_call('exists', '-1:/user_uploads/_example.php', []));
    }

    #[Test]
    public function existsReturnsFalseIfPersistenceIdentifierIsStorageLocationAndFileNotExistsAndFileHasYamlExtension(): void
    {
        $resourceStorageMock = $this->createMock(ResourceStorage::class);
        $resourceStorageMock->method('hasFile')->willReturn(false);
        $resourceStorageMock->method('isBrowsable')->willReturn(true);
        $storageRepositoryMock = $this->createMock(StorageRepository::class);
        $storageRepositoryMock->method('findByUid')->willReturn($resourceStorageMock);
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            null,
            [
                $this->createMock(YamlSource::class),
                $storageRepositoryMock,
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ]
        );
        self::assertFalse($subjectMock->_call('exists', '-1:/user_uploads/_example.form.yaml', []));
    }

    #[Test]
    public function getUniquePersistenceIdentifierAppendNumberIfPersistenceIdentifierExists(): void
    {
        $subjectMock = $this->getAccessibleMock(FormPersistenceManager::class, ['exists'], [], '', false);
        $subjectMock->expects(self::exactly(3))->method('exists')->willReturn(true, true, false);
        $expected = '-1:/user_uploads/example_2.form.yaml';
        $result = $subjectMock->getUniquePersistenceIdentifier('example', '-1:/user_uploads/', []);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getUniquePersistenceIdentifierAppendTimestampIfPersistenceIdentifierExists(): void
    {
        $subjectMock = $this->getAccessibleMock(FormPersistenceManager::class, ['exists'], [], '', false);
        $subjectMock->expects(self::exactly(101))->method('exists')->willReturn(...array_values($this->returnTrue100TimesThenFalse()));
        $expected = '#^-1:/user_uploads/example_([0-9]{10}).form.yaml$#';
        $returnValue = $subjectMock->getUniquePersistenceIdentifier('example', '-1:/user_uploads/', []);
        self::assertMatchesRegularExpression($expected, $returnValue);
    }

    /**
     * Helper function to trigger the fallback unique identifier creation after 100 attempts
     * @return bool[]
     */
    private function returnTrue100TimesThenFalse(): array
    {
        $returnValues = [];
        $returnValues = array_pad($returnValues, 100, true);
        $returnValues[] = false;
        return $returnValues;
    }

    #[Test]
    public function getUniqueIdentifierThrowsExceptionIfIdentifierExists(): void
    {
        $this->expectException(NoUniqueIdentifierException::class);
        $this->expectExceptionCode(1477688567);
        $subjectMock = $this->getAccessibleMock(FormPersistenceManager::class, ['checkForDuplicateIdentifier'], [], '', false);
        $subjectMock->method('checkForDuplicateIdentifier')->willReturn(true);
        $subjectMock->getUniqueIdentifier([], 'example');
    }

    #[Test]
    public function getUniqueIdentifierAppendTimestampIfIdentifierExists(): void
    {
        $subjectMock = $this->getAccessibleMock(FormPersistenceManager::class, ['checkForDuplicateIdentifier'], [], '', false);
        $subjectMock->expects(self::exactly(101))->method('checkForDuplicateIdentifier')->willReturn(...array_values($this->returnTrue100TimesThenFalse()));
        $expected = '#^example_([0-9]{10})$#';
        $returnValue = $subjectMock->getUniqueIdentifier([], 'example');
        self::assertMatchesRegularExpression($expected, $returnValue);
    }

    #[Test]
    public function checkForDuplicateIdentifierReturnsTrueIfIdentifierIsUsed(): void
    {
        $subjectMock = $this->getAccessibleMock(FormPersistenceManager::class, ['listForms'], [], '', false);
        $subjectMock->expects(self::once())->method('listForms')->willReturn([
            0 => [
                'identifier' => 'example',
            ],
        ]);
        self::assertTrue($subjectMock->_call('checkForDuplicateIdentifier', [], 'example'));
    }

    #[Test]
    public function checkForDuplicateIdentifierReturnsFalseIfIdentifierIsUsed(): void
    {
        $subjectMock = $this->getAccessibleMock(FormPersistenceManager::class, ['listForms'], [], '', false);
        $subjectMock->expects(self::once())->method('listForms')->willReturn([
            0 => [
                'identifier' => 'example',
            ],
        ]);
        self::assertFalse($subjectMock->_call('checkForDuplicateIdentifier', [], 'other-example'));
    }

    public static function metaDataIsExtractedDataProvider(): \Generator
    {
        yield 'enclosed with single quotation marks and escaped single quotation marks within the label' => [
            'maybeRawFormDefinition' => "label: 'Ouverture d''un compte'",
            'expectedMetaData' => ['label' => "Ouverture d'un compte"],
        ];
        yield 'enclosed with double quotation marks and single quotation marks within the label' => [
            'maybeRawFormDefinition' => 'label: "Demo: Just a \'label\'"',
            'expectedMetaData' => ['label' => "Demo: Just a 'label'"],
        ];
        yield 'label enclosed with single quotation marks' => [
            'maybeRawFormDefinition' => "label: 'Demo'",
            'expectedMetaData' => ['label' => 'Demo'],
        ];
        yield 'label enclosed with double quotation marks' => [
            'maybeRawFormDefinition' => 'label: "Demo"',
            'expectedMetaData' => ['label' => 'Demo'],
        ];
        yield 'single quotation mark only at the start of the label' => [
            'maybeRawFormDefinition' => "label: \\'Demo",
            'expectedMetaData' => ['label' => "\\'Demo"],
        ];
        yield 'double quotation mark only at the start of the label' => [
            'maybeRawFormDefinition' => 'label: \"Demo',
            'expectedMetaData' => ['label' => '\"Demo'],
        ];
        yield 'multiple properties are extracted' => [
            'maybeRawFormDefinition' => implode("\n", [
                'type: "type:type"',
                "identifier: 'identifier:identifier'",
                'prototypeName: prototypeName:prototypeName',
                'label: "Demo: Label"',
                'other: "any-other-property"',
            ]),
            'expectedMetaData' => [
                'type' => 'type:type',
                'identifier' => 'identifier:identifier',
                'prototypeName' => 'prototypeName:prototypeName',
                'label' => 'Demo: Label',
            ],
        ];
    }

    #[DataProvider('metaDataIsExtractedDataProvider')]
    #[Test]
    public function metaDataIsExtracted(string $maybeRawFormDefinition, array $expectedMetaData): void
    {
        $subjectMock = $this->getAccessibleMock(FormPersistenceManager::class, null, [], '', false);
        self::assertSame($expectedMetaData, $subjectMock->_call('extractMetaDataFromCouldBeFormDefinition', $maybeRawFormDefinition));
    }

    #[Test]
    public function retrieveFileByPersistenceIdentifierThrowsExceptionIfReadFromStorageIsNotAllowed(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1471630578);
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('checkFileActionPermission')->willReturn(false);
        $file = new File(['name' => 'foo', 'identifier' => '', 'mime_type' => ''], $storageMock);
        $resourceFactoryMock = $this->createMock(ResourceFactory::class);
        $resourceFactoryMock->method('retrieveFileOrFolderObject')->willReturn($file);
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            null,
            [
                $this->createMock(YamlSource::class),
                $this->createMock(StorageRepository::class),
                new FilePersistenceSlot(new HashService()),
                $resourceFactoryMock,
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ]
        );
        $subjectMock->_call('retrieveFileByPersistenceIdentifier', '-1:/user_uploads/example.yaml', []);
    }

    #[Test]
    public function getOrCreateFileThrowsExceptionIfFolderNotExistsInStorage(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1471630579);
        $resourceStorageMock = $this->createMock(ResourceStorage::class);
        $resourceStorageMock->method('hasFolder')->willReturn(false);
        $subjectMock = $this->getAccessibleMock(FormPersistenceManager::class, ['getStorageByUid'], [], '', false);
        $subjectMock->method('getStorageByUid')->willReturn($resourceStorageMock);
        $subjectMock->_call('getOrCreateFile', '-1:/user_uploads/example.yaml');
    }

    #[Test]
    public function getOrCreateFileThrowsExceptionIfWriteToStorageIsNotAllowed(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1471630580);
        $resourceStorageMock = $this->createMock(ResourceStorage::class);
        $resourceStorageMock->method('hasFolder')->willReturn(true);
        $resourceStorageMock->method('checkFileActionPermission')->willReturn(false);
        $file = new File(['name' => 'foo', 'identifier' => '', 'mime_type' => ''], $resourceStorageMock);
        $resourceStorageMock->method('getFile')->willReturn($file);
        $subjectMock = $this->getAccessibleMock(FormPersistenceManager::class, ['getStorageByUid'], [], '', false);
        $subjectMock->method('getStorageByUid')->willReturn($resourceStorageMock);
        $subjectMock->_call('getOrCreateFile', '-1:/user_uploads/example.yaml');
    }

    #[Test]
    public function getStorageByUidThrowsExceptionIfStorageNotExists(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1471630581);
        $storageRepositoryMock = $this->createMock(StorageRepository::class);
        $storageRepositoryMock->method('findByUid')->willReturn(null);
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            null,
            [
                $this->createMock(YamlSource::class),
                $storageRepositoryMock,
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ]
        );
        $subjectMock->_call('getStorageByUid', -1);
    }

    #[Test]
    public function getStorageByUidThrowsExceptionIfStorageIsNotBrowsable(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1471630581);
        $resourceStorageMock = $this->createMock(ResourceStorage::class);
        $resourceStorageMock->method('isBrowsable')->willReturn(false);
        $storageRepositoryMock = $this->createMock(StorageRepository::class);
        $storageRepositoryMock->method('findByUid')->willReturn($resourceStorageMock);
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            null,
            [
                $this->createMock(YamlSource::class),
                $storageRepositoryMock,
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ],
        );
        $subjectMock->_call('getStorageByUid', -1);
    }

    public static function isAllowedPersistencePathReturnsProperValuesDataProvider(): array
    {
        return [
            [
                'persistencePath' => '',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/example.form.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/BlankForm.form.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],

            [
                'persistencePath' => '-1:/user_uploads/',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/some_path/'],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads/'],
                'expected' => true,
            ],
            [
                'persistencePath' => '-1:/user_uploads',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads/'],
                'expected' => true,
            ],
            [
                'persistencePath' => '-1:/user_uploads/',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads'],
                'expected' => true,
            ],

            [
                'persistencePath' => 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/',
                'allowedExtensionPaths' => ['EXT:some_extension/Tests/Functional/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/',
                'allowedExtensionPaths' => ['EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => true,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures',
                'allowedExtensionPaths' => ['EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => true,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/',
                'allowedExtensionPaths' => ['EXT:form/Tests/Functional/Mvc/Persistence/Fixtures'],
                'allowedFileMounts' => [],
                'expected' => true,
            ],

            [
                'persistencePath' => '-1:/user_uploads/example.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/some_path/'],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/example.form.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/some_path/'],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/example.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads/'],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/example.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads'],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/example.form.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads/'],
                'expected' => true,
            ],
            [
                'persistencePath' => '-1:/user_uploads/example.form.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads'],
                'expected' => true,
            ],

            [
                'persistencePath' => 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/BlankForm.txt',
                'allowedExtensionPaths' => ['EXT:some_extension/Tests/Functional/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/BlankForm.form.yaml',
                'allowedExtensionPaths' => ['EXT:some_extension/Tests/Functional/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/BlankForm.txt',
                'allowedExtensionPaths' => ['EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/BlankForm.txt',
                'allowedExtensionPaths' => ['EXT:form/Tests/Functional/Mvc/Persistence/Fixtures'],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/BlankForm.form.yaml',
                'allowedExtensionPaths' => ['EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => true,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/BlankForm.form.yaml',
                'allowedExtensionPaths' => ['EXT:form/Tests/Functional/Mvc/Persistence/Fixtures'],
                'allowedFileMounts' => [],
                'expected' => true,
            ],
        ];
    }

    #[DataProvider('isAllowedPersistencePathReturnsProperValuesDataProvider')]
    #[Test]
    public function isAllowedPersistencePathReturnsProperValues(string $persistencePath, array $allowedExtensionPaths, array $allowedFileMounts, $expected): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getRootLevelFolder')->willReturn(new Folder($storageMock, '', ''));
        $storageMock->method('getFileMounts')->willReturn([]);
        $storageMock->method('getFolder')->willReturn(new Folder($storageMock, '', ''));
        $formSettings = [
            'persistenceManager' => [
                'allowedExtensionPaths' => $allowedExtensionPaths,
                'allowedFileMounts' => $allowedFileMounts,
            ],
        ];
        $subjectMock = $this->getAccessibleMock(
            FormPersistenceManager::class,
            ['getStorageByUid'],
            [
                $this->createMock(YamlSource::class),
                $this->createMock(StorageRepository::class),
                new FilePersistenceSlot(new HashService()),
                $this->createMock(ResourceFactory::class),
                $this->createMock(FrontendInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(TypoScriptService::class),
            ],
        );
        $subjectMock->method('getStorageByUid')->willReturn($storageMock);
        self::assertEquals($expected, $subjectMock->isAllowedPersistencePath($persistencePath, $formSettings));
    }
}
