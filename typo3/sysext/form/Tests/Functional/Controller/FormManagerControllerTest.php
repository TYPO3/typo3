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

namespace TYPO3\CMS\Form\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\UriBuilder as CoreUriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder as ExtbaseUriBuilder;
use TYPO3\CMS\Form\Controller\FormManagerController;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\CMS\Form\Service\DatabaseService;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormManagerControllerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    #[Test]
    public function getAccessibleFormStorageFoldersReturnsProcessedArray(): void
    {
        $formPersistenceManagerMock = $this->createMock(FormPersistenceManagerInterface::class);
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $formPersistenceManagerMock,
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->createMock(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
            ],
        );

        $storageMock1 = $this->createMock(ResourceStorage::class);
        $storageMock2 = $this->createMock(ResourceStorage::class);

        $storageMock1->method('isPublic')->willReturn(true);
        $storageMock2->method('isPublic')->willReturn(false);

        $folder1Mock = $this->createMock(Folder::class);
        $folder1Mock->method('getPublicUrl')->willReturn('/fileadmin/user_upload/');
        $folder1Mock->method('getStorage')->willReturn($storageMock1);

        $folder2Mock = $this->createMock(Folder::class);
        $folder2Mock->method('getStorage')->willReturn($storageMock2);

        $formPersistenceManagerMock->method('getAccessibleFormStorageFolders')->willReturn([
            '1:/user_upload/' => $folder1Mock,
            '2:/forms/' => $folder2Mock,
        ]);
        $formPersistenceManagerMock->method('getAccessibleExtensionFolders')->willReturn([
            'EXT:form/Resources/Forms/' => '/some/path/form/Resources/Forms/',
            'EXT:form_additions/Resources/Forms/' => '/some/path/form_additions/Resources/Forms/',
        ]);

        $expected = [
            0 => [
                'label' => '/fileadmin/user_upload/',
                'value' => '1:/user_upload/',
            ],
            1 => [
                'label' => '2:/forms/',
                'value' => '2:/forms/',
            ],
            2 => [
                'label' => 'EXT:form/Resources/Forms/',
                'value' => 'EXT:form/Resources/Forms/',
            ],
            3 => [
                'label' => 'EXT:form_additions/Resources/Forms/',
                'value' => 'EXT:form_additions/Resources/Forms/',
            ],
        ];

        self::assertSame($expected, $subjectMock->_call('getAccessibleFormStorageFolders', [], true));
    }

    #[Test]
    public function getFormManagerAppInitialDataReturnsProcessedArray(): void
    {
        $translationServiceMock = $this->createMock(TranslationService::class);
        $translationServiceMock->method('translateValuesRecursive')->willReturnArgument(0);
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            ['getAccessibleFormStorageFolders'],
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $translationServiceMock,
                $this->createMock(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
            ],
        );

        $mockUriBuilder = $this->createMock(ExtbaseUriBuilder::class);
        $mockUriBuilder->method('uriFor')->willReturn('/typo3/index.php?some=param');
        $subjectMock->_set('uriBuilder', $mockUriBuilder);

        $subjectMock->method('getAccessibleFormStorageFolders')
            ->willReturn([
                0 => [
                    'label' => 'user_upload',
                    'value' => '1:/user_upload/',
                ],
            ]);
        $expected = [
            'selectablePrototypesConfiguration' => [],
            'accessibleFormStorageFolders' => [
                0 => [
                    'label' => 'user_upload',
                    'value' => '1:/user_upload/',
                ],
            ],
            'endpoints' => [
                'create' => '/typo3/index.php?some=param',
                'duplicate' => '/typo3/index.php?some=param',
                'delete' => '/typo3/index.php?some=param',
                'references' => '/typo3/index.php?some=param',
            ],
        ];
        $result = $subjectMock->_call(
            'getFormManagerAppInitialData',
            [
                'formManager' => [
                    'selectablePrototypesConfiguration' => [],
                ],
            ]
        );
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getAvailableFormDefinitionsReturnsProcessedArray(): void
    {
        $formPersistenceManagerMock = $this->createMock(FormPersistenceManagerInterface::class);
        $databaseServiceMock = $this->createMock(DatabaseService::class);
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $databaseServiceMock,
                $formPersistenceManagerMock,
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->createMock(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
            ],
        );
        $formPersistenceManagerMock->method('listForms')->willReturn([
            0 => [
                'identifier' => 'ext-form-identifier',
                'name' => 'some name',
                'persistenceIdentifier' => '1:/user_uploads/someFormName.yaml',
                'readOnly' => false,
                'removable' => true,
                'location' => 'storage',
                'duplicateIdentifier' => false,
            ],
        ]);
        $databaseServiceMock->method('getAllReferencesForFileUid')->willReturn([
            0 => 0,
        ]);
        $databaseServiceMock->method('getAllReferencesForPersistenceIdentifier')->willReturn([
            '1:/user_uploads/someFormName.yaml' => 2,
        ]);
        $expected = [
            0 => [
                'identifier' => 'ext-form-identifier',
                'name' => 'some name',
                'persistenceIdentifier' => '1:/user_uploads/someFormName.yaml',
                'readOnly' => false,
                'removable' => true,
                'location' => 'storage',
                'duplicateIdentifier' => false,
                'referenceCount' => 2,
            ],
        ];
        self::assertSame($expected, $subjectMock->_call('getAvailableFormDefinitions', []));
    }

    #[Test]
    public function getProcessedReferencesRowsThrowsExceptionIfPersistenceIdentifierIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1477071939);
        $subjectMock = $this->getAccessibleMock(FormManagerController::class, null, [], '', false);
        $subjectMock->_call('getProcessedReferencesRows', '');
    }

    #[Test]
    public function getProcessedReferencesRowsReturnsProcessedArray(): void
    {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconMock = $this->createMock(Icon::class);
        $iconMock->expects(self::atLeastOnce())->method('render')->willReturn('');
        $iconFactoryMock->method('getIconForRecord')->withAnyParameters()->willReturn($iconMock);
        $databaseServiceMock = $this->createMock(DatabaseService::class);
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            [
                'getModuleUrl',
                'getRecord',
                'getRecordTitle',
            ],
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $iconFactoryMock,
                $databaseServiceMock,
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->createMock(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
            ]
        );
        $databaseServiceMock
            ->method('getReferencesByPersistenceIdentifier')
            ->with(self::anything())
            ->willReturn([
                0 => [
                    'tablename' => 'tt_content',
                    'recuid' => -1,
                ],
            ]);
        $subjectMock->method('getModuleUrl')->willReturn('/typo3/index.php?some=param');
        $subjectMock->method('getRecord')->willReturn([ 'uid' => 1, 'pid' => 0 ]);
        $subjectMock->method('getRecordTitle')->willReturn('record title');
        $expected = [
            0 => [
                'recordPageTitle' => 'record title',
                'recordTitle' => 'record title',
                'recordIcon' => '',
                'recordUid' => -1,
                'recordEditUrl' => '/typo3/index.php?some=param',
            ],
        ];
        self::assertSame($expected, $subjectMock->_call('getProcessedReferencesRows', 'fake'));
    }

    #[Test]
    public function isValidTemplatePathReturnsTrueIfTemplateIsDefinedAndExists(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->createMock(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
            ],
        );
        self::assertTrue($subjectMock->_call(
            'isValidTemplatePath',
            [
                'formManager' => [
                    'selectablePrototypesConfiguration' => [
                        0 => [
                            'identifier' => 'standard',
                            'label' => 'some label',
                            'newFormTemplates' => [
                                0 => [
                                    'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/BlankForm.yaml',
                                    'label' => 'some label',
                                ],
                                1 => [
                                    'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/SimpleContactForm.yaml',
                                    'label' => 'some other label',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'standard',
            'EXT:form/Tests/Functional/Controller/Fixtures/SimpleContactForm.yaml'
        ));
    }

    #[Test]
    public function isValidTemplatePathReturnsFalseIfTemplateIsDefinedButNotExists(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->createMock(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
            ],
        );
        self::assertFalse(
            $subjectMock->_call(
                'isValidTemplatePath',
                [
                    'formManager' => [
                        'selectablePrototypesConfiguration' => [
                            0 => [
                                'identifier' => 'standard',
                                'label' => 'some label',
                                'newFormTemplates' => [
                                    0 => [
                                        'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/BlankForm.yaml',
                                        'label' => 'some label',
                                    ],
                                    1 => [
                                        'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/SimpleContactForm.yaml',
                                        'label' => 'some other label',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'standard',
                'EXT:form/Tests/Functional/Controller/Fixtures/NonExistingForm.yaml'
            )
        );
    }

    #[Test]
    public function isValidTemplatePathReturnsFalseIfTemplateIsNotDefinedAndExists(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->createMock(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
            ],
        );
        self::assertFalse(
            $subjectMock->_call(
                'isValidTemplatePath',
                [
                    'formManager' => [
                        'selectablePrototypesConfiguration' => [
                            0 => [
                                'identifier' => 'standard',
                                'label' => 'some label',
                                'newFormTemplates' => [
                                    0 => [
                                        'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/BlankForm.yaml',
                                        'label' => 'some label',
                                    ],
                                    1 => [
                                        'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/SimpleContactForm.yaml',
                                        'label' => 'some other label',
                                    ],
                                ],
                            ],
                            1 => [
                                'identifier' => 'other',
                                'label' => 'some label',
                                'newFormTemplates' => [
                                    0 => [
                                        'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/BlankForm.yaml',
                                        'label' => 'some label',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'other',
                'EXT:form/Tests/Functional/Controller/Fixtures/SimpleContactForm.yaml'
            )
        );
    }

    #[Test]
    public function convertFormNameToIdentifierRemoveSpaces(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                new CharsetConverter(),
                $this->createMock(CoreUriBuilder::class),
            ],
        );
        $input = 'test form';
        $expected = 'testform';
        self::assertSame($expected, $subjectMock->_call('convertFormNameToIdentifier', $input));
    }

    #[Test]
    public function convertFormNameToIdentifierConvertAccentedCharacters(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                new CharsetConverter(),
                $this->createMock(CoreUriBuilder::class),
            ],
        );
        $input = 'téstform';
        $expected = 'testform';
        self::assertSame($expected, $subjectMock->_call('convertFormNameToIdentifier', $input));
    }

    #[Test]
    public function convertFormNameToIdentifierConvertAccentedCharactersNotInNFC(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                new CharsetConverter(),
                $this->createMock(CoreUriBuilder::class),
            ],
        );
        $input = 'test form ' . hex2bin('667275cc88686e65757a6569746c696368656e');
        $expected = 'testformfruehneuzeitlichen';
        self::assertSame($expected, $subjectMock->_call('convertFormNameToIdentifier', $input));
    }

    #[Test]
    public function convertFormNameToIdentifierRemoveSpecialChars(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                new CharsetConverter(),
                $this->createMock(CoreUriBuilder::class),
            ],
        );
        $input = 'test form ä#!_-01';
        $expected = 'testformae_-01';
        self::assertSame($expected, $subjectMock->_call('convertFormNameToIdentifier', $input));
    }
}
