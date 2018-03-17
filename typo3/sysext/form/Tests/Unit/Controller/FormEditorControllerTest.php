<?php
declare(strict_types = 1);
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Controller\FormEditorController;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FormEditorControllerTest extends UnitTestCase
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
    public function getInsertRenderablesPanelConfigurationReturnsGroupedAndSortedConfiguration(): void
    {
        $mockController = $this->getAccessibleMock(FormEditorController::class, [
            'dummy'
        ], [], '', false);

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManagerProphecy->reveal());

        $mockTranslationService = $this->getAccessibleMock(TranslationService::class, [
            'translate'
        ], [], '', false);

        $mockTranslationService
            ->expects($this->any())
            ->method('translate')
            ->willReturnArgument(4);

        $objectManagerProphecy
            ->get(TranslationService::class)
            ->willReturn($mockTranslationService);

        $mockController->_set('prototypeConfiguration', [
            'formEditor' => [
                'formElementGroups' => [
                    'input' => [
                        'label' => 'Basic elements',
                    ],
                    'select' => [
                        'label' => 'Select elements',
                    ],
                ],
            ],
        ]);

        $input = [
            'Password' => [
                'group' => 'input',
                'groupSorting' => 110,
                'iconIdentifier' => 'form-password',
                'label' => 'Password label',
            ],
            'Text' => [
                'group' => 'input',
                'groupSorting' => 100,
                'iconIdentifier' => 'form-text',
                'label' => 'Text label',
            ],
            'SingleSelect' => [
                'group' => 'select',
                'groupSorting' => 100,
                'iconIdentifier' => 'form-single-select',
                'label' => 'Single select label',
            ],
        ];

        $expected = [
            0 => [
                'key' => 'input',
                'elements' => [
                    0 => [
                        'key' => 'Text',
                        'cssKey' => 'text',
                        'label' => 'Text label',
                        'sorting' => 100,
                        'iconIdentifier' => 'form-text',
                    ],
                    1 => [
                        'key' => 'Password',
                        'cssKey' => 'password',
                        'label' => 'Password label',
                        'sorting' => 110,
                        'iconIdentifier' => 'form-password',
                    ],
                ],
                'label' => 'Basic elements',
            ],
            1 => [
                'key' => 'select',
                'elements' => [
                    0 => [
                        'key' => 'SingleSelect',
                        'cssKey' => 'singleselect',
                        'label' => 'Single select label',
                        'sorting' => 100,
                        'iconIdentifier' => 'form-single-select',
                    ],
                ],
                'label' => 'Select elements',
            ],
        ];

        $this->assertSame($expected, $mockController->_call('getInsertRenderablesPanelConfiguration', $input));
    }

    /**
     * @test
     */
    public function getFormEditorDefinitionsReturnReducedConfiguration(): void
    {
        $mockController = $this->getAccessibleMock(FormEditorController::class, [
            'dummy'
        ], [], '', false);

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManagerProphecy->reveal());

        $mockTranslationService = $this->getAccessibleMock(TranslationService::class, [
            'translateValuesRecursive'
        ], [], '', false);

        $mockTranslationService
            ->expects($this->any())
            ->method('translateValuesRecursive')
            ->willReturnArgument(0);

        $objectManagerProphecy
            ->get(TranslationService::class)
            ->willReturn($mockTranslationService);

        $mockController->_set('prototypeConfiguration', [
            'formEditor' => [
                'someOtherValues' => [
                    'horst' => [
                        'key' => 'value',
                    ],
                    'gertrud' => [
                        'key' => 'value',
                    ],
                ],
                'formElementPropertyValidatorsDefinition' => [
                    'NotEmpty' => [
                        'key' => 'value',
                    ],
                ],
            ],
            'formElementsDefinition' => [
                'Form' => [
                    'formEditor' => [
                        'key' => 'value',
                    ],
                    'someOtherValues' => [
                        'horst' => [
                            'key' => 'value',
                        ],
                        'gertrud' => [
                            'key' => 'value',
                        ],
                    ],
                ],
                'Text' => [
                    'formEditor' => [
                        'key' => 'value',
                    ],
                    'someOtherValues' => [
                        'horst' => [
                            'key' => 'value',
                        ],
                        'gertrud' => [
                            'key' => 'value',
                        ],
                    ],
                ],
            ],
            'finishersDefinition' => [
                'Confirmation' => [
                    'formEditor' => [
                        'key' => 'value',
                    ],
                    'someOtherValues' => [
                        'horst' => [
                            'key' => 'value',
                        ],
                        'gertrud' => [
                            'key' => 'value',
                        ],
                    ],
                ],
                'EmailToSender' => [
                    'formEditor' => [
                        'key' => 'value',
                    ],
                    'someOtherValues' => [
                        'horst' => [
                            'key' => 'value',
                        ],
                        'gertrud' => [
                            'key' => 'value',
                        ],
                    ],
                ],
            ],
            'someOtherValues' => [
                'horst' => [
                    'key' => 'value',
                ],
                'gertrud' => [
                    'key' => 'value',
                ],
            ],
        ]);

        $expected = [
            'formElements' => [
                'Form' => [
                    'key' => 'value',
                ],
                'Text' => [
                    'key' => 'value',
                ],
            ],
            'finishers' => [
                'Confirmation' => [
                    'key' => 'value',
                ],
                'EmailToSender' => [
                    'key' => 'value',
                ],
            ],
            'formElementPropertyValidators' => [
                'NotEmpty' => [
                    'key' => 'value',
                ],
            ],
        ];

        $this->assertSame($expected, $mockController->_call('getFormEditorDefinitions'));
    }

    /**
     * @test
     */
    public function renderFormEditorTemplatesThrowsExceptionIfLayoutRootPathsNotSet(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1480294721);

        $mockController = $this->getAccessibleMock(FormEditorController::class, [
            'dummy'
        ], [], '', false);

        $mockController->_set('prototypeConfiguration', [
            'formEditor' => [
                'formEditorFluidConfiguration' => [
                    'templatePathAndFilename' => '',
                ],
            ],
        ]);

        $mockController->_call('renderFormEditorTemplates', []);
    }

    /**
     * @test
     */
    public function renderFormEditorTemplatesThrowsExceptionIfLayoutRootPathsNotArray(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1480294721);

        $mockController = $this->getAccessibleMock(FormEditorController::class, [
            'dummy'
        ], [], '', false);

        $mockController->_set('prototypeConfiguration', [
            'formEditor' => [
                'formEditorFluidConfiguration' => [
                    'templatePathAndFilename' => '',
                    'layoutRootPaths' => '',
                ],
            ],
        ]);

        $mockController->_call('renderFormEditorTemplates', []);
    }

    /**
     * @test
     */
    public function renderFormEditorTemplatesThrowsExceptionIfPartialRootPathsNotSet(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1480294722);

        $mockController = $this->getAccessibleMock(FormEditorController::class, [
            'dummy'
        ], [], '', false);

        $mockController->_set('prototypeConfiguration', [
            'formEditor' => [
                'formEditorFluidConfiguration' => [
                    'templatePathAndFilename' => '',
                    'layoutRootPaths' => [],
                ],
            ],
        ]);

        $mockController->_call('renderFormEditorTemplates', []);
    }

    /**
     * @test
     */
    public function renderFormEditorTemplatesThrowsExceptionIfPartialRootPathsNotArray(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1480294722);

        $mockController = $this->getAccessibleMock(FormEditorController::class, [
            'dummy'
        ], [], '', false);

        $mockController->_set('prototypeConfiguration', [
            'formEditor' => [
                'formEditorFluidConfiguration' => [
                    'templatePathAndFilename' => '',
                    'layoutRootPaths' => [],
                ],
            ],
        ]);

        $mockController->_call('renderFormEditorTemplates', []);
    }

    /**
     * @test
     */
    public function renderFormEditorTemplatesThrowsExceptionIftemplatePathAndFilenameNotSet(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1485636499);

        $mockController = $this->getAccessibleMock(FormEditorController::class, [
            'dummy'
        ], [], '', false);

        $mockController->_set('prototypeConfiguration', [
            'formEditor' => [
                'formEditorFluidConfiguration' => [],
            ],
        ]);

        $mockController->_call('renderFormEditorTemplates', []);
    }
}
