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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaColumnsProcessCommonTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addDataRegistersOrigUidColumn()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [
                    'origUid' => 't3_origuid'
                ]
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['t3_origuid'];
        $this->assertSame($expected, (new TcaColumnsProcessCommon)->addData($input));
    }

    /**
     * @test
     */
    public function addDataRegistersRecordTypeColumn()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [
                    'type' => 'doktype'
                ]
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['doktype'];
        $this->assertSame($expected, (new TcaColumnsProcessCommon)->addData($input));
    }

    /**
     * @test
     */
    public function addDataRegistersRecordTypeRelationColumn()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [
                    'type' => 'relation_field:foreign_type_field'
                ]
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['relation_field'];
        $this->assertSame($expected, (new TcaColumnsProcessCommon)->addData($input));
    }

    /**
     * @test
     */
    public function addDataRegistersLanguageFieldColumn()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid'
                ]
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['sys_language_uid'];
        $this->assertSame($expected, (new TcaColumnsProcessCommon)->addData($input));
    }

    /**
     * @test
     */
    public function addDataRegistersTransOrigPointerColumn()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [
                    'transOrigPointerField' => 'l10n_parent'
                ]
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['l10n_parent'];
        $this->assertSame($expected, (new TcaColumnsProcessCommon)->addData($input));
    }

    /**
     * @test
     */
    public function addDataRegistersTransOrigDiffSourceColumn()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [
                    'transOrigDiffSourceField' => 'l18n_diffsource'
                ]
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['l18n_diffsource'];
        $this->assertSame($expected, (new TcaColumnsProcessCommon)->addData($input));
    }

    /**
     * @test
     */
    public function addDataRegistersSingleSubtypesAddlistFields()
    {
        $input = [
            'recordTypeValue' => 'list',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [],
                'types' => [
                    'list' => [
                        'subtype_value_field' => 'list_type',
                        'subtypes_addlist' => [
                            'aType' => 'aField',
                        ]
                    ],
                ],
            ],
            'databaseRow' => [
                'list_type' => 'aType',
            ],
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['aField'];
        $this->assertSame($expected, (new TcaColumnsProcessCommon)->addData($input));
    }

    /**
     * @test
     */
    public function addDataRegistersMultipleSubtypesAddlistFields()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [],
                'types' => [
                    'aType' => [
                        'subtype_value_field' => 'theSubtypeValueField',
                        'subtypes_addlist' => [
                            'theSubtypeValue' => 'aField, bField',
                        ]
                    ],
                ],
            ],
            'databaseRow' => [
                'theSubtypeValueField' => 'theSubtypeValue',
            ],
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['aField', 'bField'];
        $this->assertSame($expected, (new TcaColumnsProcessCommon)->addData($input));
    }
}
