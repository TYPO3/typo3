<?php
namespace TYPO3\CMS\Form\Tests\Unit\Controller;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Controller\FormManagerController;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Form\Tests\Unit\Controller\Fixtures\BackendUtilityFixture;

/**
 * Test case
 */
class FormManagerControllerTest extends UnitTestCase
{

    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * Set up
     */
    public function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
    }

    /**
     * Tear down
     */
    public function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getAccessibleFormStorageFoldersReturnsProcessedArray()
    {
        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'dummy'
        ], [], '', false);

        $formPersistenceManagerProphecy = $this->prophesize(FormPersistenceManager::class);

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockController->_set('formPersistenceManager', $formPersistenceManagerProphecy->reveal());

        $folder1 = new Folder($mockStorage, '/user_upload/', 'user_upload');
        $folder2 = new Folder($mockStorage, '/forms/', 'forms');

        $formPersistenceManagerProphecy->getAccessibleFormStorageFolders(Argument::cetera())->willReturn([
            '1:/user_upload/' => $folder1,
            '2:/forms/' => $folder2,
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
        ];

        $this->assertSame($expected, $mockController->_call('getAccessibleFormStorageFolders'));
    }

    /**
     * @test
     */
    public function getFormManagerAppInitialDataReturnsProcessedArray()
    {
        $objectMangerProphecy = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectMangerProphecy->reveal());

        $mockTranslationService = $this->getAccessibleMock(TranslationService::class, [
            'translateValuesRecursive'
        ], [], '', false);

        $mockTranslationService
            ->expects($this->any())
            ->method('translateValuesRecursive')
            ->willReturnArgument(0);

        $objectMangerProphecy
            ->get(TranslationService::class)
            ->willReturn($mockTranslationService);

        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'getAccessibleFormStorageFolders'
        ], [], '', false);

        $mockUriBuilder = $this->createMock(UriBuilder::class);
        $mockControllerContext = $this->createMock(ControllerContext::class);
        $mockControllerContext
            ->expects($this->any())
            ->method('getUriBuilder')
            ->will($this->returnValue($mockUriBuilder));

        $mockController->_set('controllerContext', $mockControllerContext);

        $mockController->_set('formSettings', [
            'formManager' => [
                'selectablePrototypesConfiguration' => [],
            ],
        ]);

        $mockUriBuilder->expects($this->any())->method('uriFor')->willReturn(
            '/typo3/index.php?some=param'
        );

        $mockController
            ->expects($this->any())
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

        $this->assertSame(json_encode($expected), $mockController->_call('getFormManagerAppInitialData'));
    }

    /**
     * @test
     */
    public function getAvailableFormDefinitionsReturnsProcessedArray()
    {
        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'getReferences'
        ], [], '', false);

        $formPersistenceManagerProphecy = $this->prophesize(FormPersistenceManager::class);
        $mockController->_set('formPersistenceManager', $formPersistenceManagerProphecy->reveal());

        $formPersistenceManagerProphecy->listForms(Argument::cetera())->willReturn([
            0 => [
                'identifier' => 'ext-form-identifier',
                'name' => 'some name',
                'persistenceIdentifier' => '1:/user_uploads/someFormName.yaml',
                'readOnly' => false,
                'location' => 'storage',
                'duplicateIdentifier' => false,
            ],
        ]);

        $mockController
            ->expects($this->any())
            ->method('getReferences')
            ->willReturn([
                'someRow',
                'anotherRow',
            ]);

        $expected = [
            0 => [
                'identifier' => 'ext-form-identifier',
                'name' => 'some name',
                'persistenceIdentifier' => '1:/user_uploads/someFormName.yaml',
                'readOnly' => false,
                'location' => 'storage',
                'duplicateIdentifier' => false,
                'referenceCount' => 2,
            ],
        ];

        $this->assertSame($expected, $mockController->_call('getAvailableFormDefinitions'));
    }

    /**
     * @test
     */
    public function getProcessedReferencesRowsThrowsExceptionIfPersistenceIdentifierIsEmpty()
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
        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'getReferences',
            'getBackendUtility',
        ], [], '', false);

        $mockController
            ->expects($this->any())
            ->method('getBackendUtility')
            ->willReturn(BackendUtilityFixture::class);

        $mockController
            ->expects($this->any())
            ->method('getReferences')
            ->willReturn([
                0 => [
                    'tablename' => 'tt_content',
                    'recuid' => -1,
                ],
            ]);

        $expected = [
            0 => [
                'recordPageTitle' => 'record title',
                'recordTitle' => 'record title',
                'recordIcon' =>
'<span class="t3js-icon icon icon-size-small icon-state-default icon-default-not-found" data-identifier="default-not-found">
	<span class="icon-markup">
<img src="typo3/sysext/core/Resources/Public/Icons/T3Icons/default/default-not-found.svg" width="16" height="16" />
	</span>
	
</span>',
                'recordUid' => -1,
                'recordEditUrl' => '/typo3/index.php?some=param',
            ],
        ];

        $this->assertSame($expected, $mockController->_call('getProcessedReferencesRows', 'fake'));
    }

    /**
     * @test
     */
    public function isValidTemplatePathReturnsTrueIfTemplateIsDefinedAndExists()
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

        $this->assertTrue($mockController->_call('isValidTemplatePath', 'standard', 'EXT:form/Tests/Unit/Controller/Fixtures/SimpleContactForm.yaml'));
    }

    /**
     * @test
     */
    public function isValidTemplatePathReturnsFalseIfTemplateIsDefinedButNotExists()
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

        $this->assertFalse($mockController->_call('isValidTemplatePath', 'standard', 'EXT:form/Tests/Unit/Controller/Fixtures/NonExistingForm.yaml'));
    }

    /**
     * @test
     */
    public function isValidTemplatePathReturnsFalseIfTemplateIsNotDefinedAndExists()
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

        $this->assertFalse($mockController->_call('isValidTemplatePath', 'other', 'EXT:form/Tests/Unit/Controller/Fixtures/SimpleContactForm.yaml'));
    }

    /**
     * @test
     */
    public function convertFormNameToIdentifierRemoveSpaces()
    {
        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'dummy'
        ], [], '', false);

        $input = 'test form';
        $expected = 'testform';
        $this->assertSame($expected, $mockController->_call('convertFormNameToIdentifier', $input));
    }

    /**
     * @test
     */
    public function convertFormNameToIdentifierRemoveSpecialChars()
    {
        $mockController = $this->getAccessibleMock(FormManagerController::class, [
            'dummy'
        ], [], '', false);

        $input = 'test form ä#!_-01';
        $expected = 'testform_-01';
        $this->assertSame($expected, $mockController->_call('convertFormNameToIdentifier', $input));
    }
}
