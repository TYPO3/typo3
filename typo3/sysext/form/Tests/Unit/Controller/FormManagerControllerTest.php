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

namespace TYPO3\CMS\Form\Tests\Unit\Controller;

use Prophecy\Argument;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Controller\FormManagerController;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;
use TYPO3\CMS\Form\Service\DatabaseService;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FormManagerControllerTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function getAccessibleFormStorageFoldersReturnsProcessedArray(): void
    {
        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'dummy'
        ], [], '', false);

        $formPersistenceManagerProphecy = $this->prophesize(FormPersistenceManager::class);

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockController->_set('formPersistenceManager', $formPersistenceManagerProphecy->reveal());

        $mockController->_set('formSettings', [
            'persistenceManager' => [
                'allowSaveToExtensionPaths' => true,
            ],
        ]);

        $folder1 = new Folder($mockStorage, '/user_upload/', 'user_upload');
        $folder2 = new Folder($mockStorage, '/forms/', 'forms');

        $formPersistenceManagerProphecy->getAccessibleFormStorageFolders(Argument::cetera())->willReturn([
            '1:/user_upload/' => $folder1,
            '2:/forms/' => $folder2,
        ]);

        $formPersistenceManagerProphecy->getAccessibleExtensionFolders(Argument::cetera())->willReturn([
            'EXT:form/Resources/Forms/' => '/some/path/form/Resources/Forms/',
            'EXT:form_additions/Resources/Forms/' => '/some/path/form_additions/Resources/Forms/',
        ]);

        $expected = [
            0 => [
                'label' => 'user_upload',
                'value' => '1:/user_upload/',
            ],
            1 => [
                'label' => 'forms',
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

        self::assertSame($expected, $mockController->_call('getAccessibleFormStorageFolders'));
    }

    /**
     * @test
     */
    public function getFormManagerAppInitialDataReturnsProcessedArray(): void
    {
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManagerProphecy->reveal());

        $mockTranslationService = $this->getAccessibleMock(TranslationService::class, [
            'translateValuesRecursive'
        ], [], '', false);

        $mockTranslationService
            ->expects(self::any())
            ->method('translateValuesRecursive')
            ->willReturnArgument(0);

        $objectManagerProphecy
            ->get(TranslationService::class)
            ->willReturn($mockTranslationService);

        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'getAccessibleFormStorageFolders'
        ], [], '', false);

        $mockUriBuilder = $this->createMock(UriBuilder::class);
        $mockControllerContext = $this->createMock(ControllerContext::class);
        $mockControllerContext
            ->expects(self::any())
            ->method('getUriBuilder')
            ->willReturn($mockUriBuilder);

        $mockController->_set('controllerContext', $mockControllerContext);

        $mockController->_set('formSettings', [
            'formManager' => [
                'selectablePrototypesConfiguration' => [],
            ],
        ]);

        $mockUriBuilder->expects(self::any())->method('uriFor')->willReturn(
            '/typo3/index.php?some=param'
        );

        $mockController
            ->expects(self::any())
            ->method('getAccessibleFormStorageFolders')
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

        self::assertSame(json_encode($expected), $mockController->_call('getFormManagerAppInitialData'));
    }

    /**
     * @test
     */
    public function getAvailableFormDefinitionsReturnsProcessedArray(): void
    {
        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'dummy'
        ], [], '', false);

        $formPersistenceManagerProphecy = $this->prophesize(FormPersistenceManager::class);
        $mockController->_set('formPersistenceManager', $formPersistenceManagerProphecy->reveal());

        $databaseService = $this->prophesize(DatabaseService::class);
        $mockController->_set('databaseService', $databaseService->reveal());

        $formPersistenceManagerProphecy->listForms(Argument::cetera())->willReturn([
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

        $databaseService->getAllReferencesForFileUid(Argument::cetera())->willReturn([
            0 => 0,
        ]);

        $databaseService->getAllReferencesForPersistenceIdentifier(Argument::cetera())->willReturn([
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

        self::assertSame($expected, $mockController->_call('getAvailableFormDefinitions'));
    }

    /**
     * @test
     */
    public function getProcessedReferencesRowsThrowsExceptionIfPersistenceIdentifierIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1477071939);

        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'dummy'
        ], [], '', false);

        $mockController->_call('getProcessedReferencesRows', '');
    }

    /**
     * @test
     */
    public function getProcessedReferencesRowsReturnsProcessedArray()
    {
        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        $iconProphecy = $this->prophesize(Icon::class);
        $iconFactoryProphecy->getIconForRecord(Argument::cetera())->willReturn($iconProphecy->reveal());
        $iconProphecy->render()->shouldBeCalled()->willReturn('');

        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'getModuleUrl',
            'getRecord',
            'getRecordTitle',
        ], [], '', false);

        $databaseService = $this->prophesize(DatabaseService::class);
        $mockController->_set('databaseService', $databaseService->reveal());

        $databaseService->getReferencesByPersistenceIdentifier(Argument::cetera())->willReturn([
            0 => [
                'tablename' => 'tt_content',
                'recuid' => -1,
            ],
        ]);

        $mockController
            ->expects(self::any())
            ->method('getModuleUrl')
            ->willReturn('/typo3/index.php?some=param');

        $mockController
            ->expects(self::any())
            ->method('getRecord')
            ->willReturn([ 'uid' => 1, 'pid' => 0 ]);

        $mockController
            ->expects(self::any())
            ->method('getRecordTitle')
            ->willReturn('record title');

        $expected = [
            0 => [
                'recordPageTitle' => 'record title',
                'recordTitle' => 'record title',
                'recordIcon' => '',
                'recordUid' => -1,
                'recordEditUrl' => '/typo3/index.php?some=param',
            ],
        ];

        self::assertSame($expected, $mockController->_call('getProcessedReferencesRows', 'fake'));
    }

    /**
     * @test
     */
    public function isValidTemplatePathReturnsTrueIfTemplateIsDefinedAndExists(): void
    {
        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'dummy'
        ], [], '', false);

        $mockController->_set('formSettings', [
            'formManager' => [
                'selectablePrototypesConfiguration' => [
                    0 => [
                        'identifier' => 'standard',
                        'label' => 'some label',
                        'newFormTemplates' => [
                            0 => [
                                'templatePath' => 'EXT:form/Tests/Unit/Controller/Fixtures/BlankForm.yaml',
                                'label' => 'some label',
                            ],
                            1 => [
                                'templatePath' => 'EXT:form/Tests/Unit/Controller/Fixtures/SimpleContactForm.yaml',
                                'label' => 'some other label',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertTrue($mockController->_call('isValidTemplatePath', 'standard', 'EXT:form/Tests/Unit/Controller/Fixtures/SimpleContactForm.yaml'));
    }

    /**
     * @test
     */
    public function isValidTemplatePathReturnsFalseIfTemplateIsDefinedButNotExists(): void
    {
        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'dummy'
        ], [], '', false);

        $mockController->_set('formSettings', [
            'formManager' => [
                'selectablePrototypesConfiguration' => [
                    0 => [
                        'identifier' => 'standard',
                        'label' => 'some label',
                        'newFormTemplates' => [
                            0 => [
                                'templatePath' => 'EXT:form/Tests/Unit/Controller/Fixtures/BlankForm.yaml',
                                'label' => 'some label',
                            ],
                            1 => [
                                'templatePath' => 'EXT:form/Tests/Unit/Controller/Fixtures/SimpleContactForm.yaml',
                                'label' => 'some other label',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertFalse($mockController->_call('isValidTemplatePath', 'standard', 'EXT:form/Tests/Unit/Controller/Fixtures/NonExistingForm.yaml'));
    }

    /**
     * @test
     */
    public function isValidTemplatePathReturnsFalseIfTemplateIsNotDefinedAndExists(): void
    {
        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'dummy'
        ], [], '', false);

        $mockController->_set('formSettings', [
            'formManager' => [
                'selectablePrototypesConfiguration' => [
                    0 => [
                        'identifier' => 'standard',
                        'label' => 'some label',
                        'newFormTemplates' => [
                            0 => [
                                'templatePath' => 'EXT:form/Tests/Unit/Controller/Fixtures/BlankForm.yaml',
                                'label' => 'some label',
                            ],
                            1 => [
                                'templatePath' => 'EXT:form/Tests/Unit/Controller/Fixtures/SimpleContactForm.yaml',
                                'label' => 'some other label',
                            ],
                        ],
                    ],
                    1 => [
                        'identifier' => 'other',
                        'label' => 'some label',
                        'newFormTemplates' => [
                            0 => [
                                'templatePath' => 'EXT:form/Tests/Unit/Controller/Fixtures/BlankForm.yaml',
                                'label' => 'some label',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertFalse($mockController->_call('isValidTemplatePath', 'other', 'EXT:form/Tests/Unit/Controller/Fixtures/SimpleContactForm.yaml'));
    }

    /**
     * @test
     */
    public function convertFormNameToIdentifierRemoveSpaces(): void
    {
        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'dummy'
        ], [], '', false);

        $input = 'test form';
        $expected = 'testform';
        self::assertSame($expected, $mockController->_call('convertFormNameToIdentifier', $input));
    }

    /**
     * @test
     */
    public function convertFormNameToIdentifierConvertAccentedCharacters(): void
    {
        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'dummy'
        ], [], '', false);

        $input = 'téstform';
        $expected = 'testform';
        self::assertSame($expected, $mockController->_call('convertFormNameToIdentifier', $input));
    }

    /**
     * @test
     */
    public function convertFormNameToIdentifierRemoveSpecialChars(): void
    {
        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'dummy'
        ], [], '', false);

        $input = 'test form ä#!_-01';
        $expected = 'testformae_-01';
        self::assertSame($expected, $mockController->_call('convertFormNameToIdentifier', $input));
    }
}
