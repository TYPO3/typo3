<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class TcaFlexPrepareTest extends UnitTestCase
{
    /**
     * @var TcaFlexPrepare
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new TcaFlexPrepare();
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfBothSheetsAndRootDefined()
    {
        $input = [
            'systemLanguageRows' => [],
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                    'meta' => [],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'ROOT' => [],
                                'sheets' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1440676540);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataRemovesTceFormsFromArrayKeys()
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'TCEforms' => [
                                                'sheetDescription' => 'aDescription',
                                                'displayCond' => 'aDisplayCond',
                                            ],
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'TCEforms' => [
                                                        'label' => 'aFlexFieldLabel',
                                                        'config' => [
                                                            'type' => 'input',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'sOther' => [
                                        'ROOT' => [
                                            'TCEforms' => [
                                                'sheetTitle' => 'anotherTitle',
                                            ],
                                            'type' => 'array',
                                            'el' => [
                                                'bFlexField' => [
                                                    'TCEforms' => [
                                                        'label' => 'bFlexFieldLabel',
                                                        'config' => [
                                                            'type' => 'input',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                        'sheetDescription' => 'aDescription',
                        'displayCond' => 'aDisplayCond',
                    ],
                ],
                'sOther' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'bFlexField' => [
                                'label' => 'bFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                        'sheetTitle' => 'anotherTitle',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataMigratesFlexformTca()
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'TCEforms' => [
                                                        'label' => 'aFlexFieldLabel',
                                                        'config' => [
                                                            'type' => 'text',
                                                            'default' => 'defaultValue',
                                                            'wizards' => [
                                                                't3editor' => [
                                                                    'type' => 'userFunc',
                                                                    'userFunc' => 'TYPO3\\CMS\\T3editor\\FormWizard->main',
                                                                    'title' => 't3editor',
                                                                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                                                                    'module' => [
                                                                        'name' => 'wizard_table',
                                                                    ],
                                                                    'params' => [
                                                                        'format' => 'html',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'text',
                                    'default' => 'defaultValue',
                                    'renderType' => 't3editor',
                                    'format' => 'html',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataMigratesFlexformTcaInContainer()
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'section_1' => [
                                                    'title' => 'section_1',
                                                    'type' => 'array',
                                                    'section' => '1',
                                                    'el' => [
                                                        'aFlexContainer' => [
                                                            'type' => 'array',
                                                            'title' => 'aFlexContainerLabel',
                                                            'el' => [
                                                                'aFlexField' => [
                                                                    'TCEforms' => [
                                                                        'label' => 'aFlexFieldLabel',
                                                                        'config' => [
                                                                            'type' => 'text',
                                                                            'default' => 'defaultValue',
                                                                            'wizards' => [
                                                                                't3editor' => [
                                                                                    'type' => 'userFunc',
                                                                                    'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
                                                                                    'title' => 't3editor',
                                                                                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                                                                                    'module' => [
                                                                                        'name' => 'wizard_table',
                                                                                    ],
                                                                                    'params' => [
                                                                                        'format' => 'html',
                                                                                    ],
                                                                                ],
                                                                            ],
                                                                        ],
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'section_1' => [
                                'title' => 'section_1',
                                'type' => 'array',
                                'section' => '1',
                                'el' => [
                                    'aFlexContainer' => [
                                        'type' => 'array',
                                        'title' => 'aFlexContainerLabel',
                                        'el' => [
                                            'aFlexField' => [
                                                'label' => 'aFlexFieldLabel',
                                                'config' => [
                                                    'type' => 'text',
                                                    'default' => 'defaultValue',
                                                    'renderType' => 't3editor',
                                                    'format' => 'html',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }
}
