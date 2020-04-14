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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class EvaluateDisplayConditionsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addDataThrowsExceptionIfMultipleConditionsAreNotCombinedWithAndOrOr()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => [
                            'FOO' => [
                                'condition1',
                                'condition2',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481380393);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIAConditionHasNoStringAsKey()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => [
                            ['condition1'],
                        ],
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481380393);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfConditionIsNotStringOrArray()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => false,
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481381058);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfConditionTypeIsUnknown()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'foo',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481381950);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfFieldConditionHasNoFieldName()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'FIELD',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481385695);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfFieldConditionHasNoOperator()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'FIELD:fieldName',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481386239);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfFieldConditionHasInvalidOperator()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'FIELD:fieldName:foo',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481386239);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfFieldConditionHasNoOperand()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'FIELD:fieldName:REQ',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481401543);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfFieldConditionReqHasInvalidOperand()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'FIELD:fieldName:REQ:foo',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481401892);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfFieldConditionNumberComparisonHasInvalidOperand()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'FIELD:fieldName:>=:foo',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481456806);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfFieldConditionRangeComparisonHasInvalidOperand()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'FIELD:fieldName:-:23-',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481457277);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfFieldConditionRangeComparisonHasInvalidMaxOperand()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'FIELD:fieldName:-:23-foo',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481457277);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfRecordConditionHasNoNewKeyword()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'REC',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481384784);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfRecordConditionHasInvalidNewKeyword()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'REC:foo',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481384784);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfRecordConditionHasNoOperand()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'REC:NEW',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481384947);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfRecordConditionHasInvalidOperand()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'REC:NEW:foo',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481385173);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfRecordConditionHasNoUidInDatabaseRow()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'REC:NEW:false',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481467208);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfVersionConditionHasNoIsKeyword()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'VERSION',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481383660);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfVersionConditionHasInvalidIsKeyword()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'VERSION:foo',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481383660);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfVersionConditionHasNoOperand()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'VERSION:IS',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481383888);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfVersionConditionHasInvalidOperand()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'VERSION:IS:foo',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481384123);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfVersionConditionHasNoUidInDatabaseRow()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'VERSION:IS:false',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481469854);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfUserConditionHasNoUserfuncSpecified()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'USER',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481382954);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataEvaluatesUserCondition()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'USER:' . self::class . '->addDataEvaluatesUserConditionCallback:more:arguments',
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1488130499);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * Callback method of addDataEvaluatesUserCondition. A USER condition
     * Throws an exception if data is correct!
     *
     * @param array $parameter
     * @throws \RuntimeException if data is ok
     */
    public function addDataEvaluatesUserConditionCallback(array $parameter)
    {
        $expected = [
            'record' => [],
            'flexContext' => [],
            'flexformValueKey' => 'vDEF',
            'conditionParameters' => [
                0 => 'more',
                1 => 'arguments',
            ],
        ];
        if ($expected === $parameter) {
            throw new \RuntimeException('testing', 1488130499);
        }
    }

    /**
     * @test
     */
    public function addDataResolvesAllUserParameters()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'displayCond' => 'USER:' . self::class . '->addDataResolvesAllUserParametersCallback:some:more:info',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        unset($expected['processedTca']['columns']['field_1']['displayCond']);

        self::assertSame($expected, (new EvaluateDisplayConditions())->addData($input));
    }

    /**
     * Callback method of addDataResolvesAllUserParameters. A USER condition
     * receives all condition parameter!
     *
     * @param array $parameter
     * @throws \RuntimeException if condition parameter not resolved correctly
     * @return bool
     */
    public function addDataResolvesAllUserParametersCallback(array $parameter)
    {
        $expected = [
            0 => 'some',
            1 => 'more',
            2 => 'info',
        ];

        if ($expected !== $parameter['conditionParameters']) {
            throw new \RuntimeException('testing', 1538055997);
        }

        return true;
    }

    /**
     * @test
     */
    public function addDataPassesFlexContextToUserCondition()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'foo' => [
                                                    'displayCond' => 'USER:' . self::class . '->addDataPassesFlexContextToUserConditionCallback:some:info',
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
        unset($expected['processedTca']['columns']['field_1']['config']['ds']['sheets']['sDEF']['ROOT']['el']['foo']['displayCond']);

        self::assertSame($expected, (new EvaluateDisplayConditions())->addData($input));
    }

    /**
     * Callback method of addDataEvaluatesUserCondition. A USER condition
     * Throws an exception if data is correct!
     *
     * @param array $parameter
     * @throws \RuntimeException if FlexForm context is not as expected
     * @return bool
     */
    public function addDataPassesFlexContextToUserConditionCallback(array $parameter)
    {
        $expected = [
            'context' => 'flexField',
            'sheetNameFieldNames' => [
                'sDEF.foo' => [
                    'sheetName' => 'sDEF',
                    'fieldName' => 'foo',
                ],
            ],
            'currentSheetName' => 'sDEF',
            'currentFieldName' => 'foo',
            'flexFormDataStructure' => [
                'sheets' => [
                    'sDEF' => [
                        'ROOT' => [
                            'type' => 'array',
                            'el' => [
                                'foo' => [
                                    'displayCond' => 'USER:' . self::class . '->addDataPassesFlexContextToUserConditionCallback:some:info'
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'flexFormRowData' => null,
        ];

        if ($expected !== $parameter['flexContext']) {
            throw new \RuntimeException('testing', 1538057402);
        }

        return true;
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfFlexSheetNameAndFieldNameCombinationsOverlap()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'sheets' => [
                                    'sheet' => [
                                        'ROOT' => [
                                            'el' => [
                                                'name.field' => [],
                                            ],
                                        ],
                                    ],
                                    'sheet.name' => [
                                        'ROOT' => [
                                            'el' => [
                                                'field' => [],
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
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481483061);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfFlexSheetConditionReferencesFieldFromSameSheet()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'sheets' => [
                                    'aSheet' => [
                                        'ROOT' => [
                                            'displayCond' => 'FIELD:aSheet.aField:=:foo',
                                            'el' => [
                                                'aField' => [],
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
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481485705);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataTrowsExceptionIfFlexFieldSheetConditionReferencesNotExistingFieldValue()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'sheets' => [
                                    'sheet_1' => [],
                                    'sheet_2' => [
                                        'ROOT' => [
                                            'displayCond' => 'FIELD:sheet_1.flexField_1:!=:foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481488492);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfFlexFieldFieldConditionReferencesNotExistingFieldValue()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'sheets' => [
                                    'sheet_1' => [
                                        'ROOT' => [
                                            'el' => [
                                                'flexField_1' => [],
                                                'flexField_2' => [
                                                    'displayCond' => 'FIELD:flexField_1:!=:foo',
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
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481492953);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfFlexFieldReferencingFlexFieldIsNotFoundInFieldValue()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'sheets' => [
                                    'sheet_1' => [
                                        'ROOT' => [
                                            'el' => [
                                                'flexField_1' => [
                                                    'displayCond' => 'FIELD:foo.flexField_1:!=:foo',
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
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481496170);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfFlexSectionContainerFoundNoReferencedFieldValue()
    {
        $input = [
            'databaseRow' => [
                'field_1' => [
                    'data' => [
                        'sheet_1' => [
                            'lDEF' => [
                                'section_1' => [
                                    'el' => [
                                        '1' => [
                                            'container_1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'field_1' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'sheets' => [
                                    'sheet_1' => [
                                        'ROOT' => [
                                            'el' => [
                                                'section_1' => [
                                                    'type' => 'array',
                                                    'section' => 1,
                                                    'children' => [
                                                        '1' => [
                                                            'el' => [
                                                                'containerField_1' => [
                                                                    'displayCond' => 'FIELD:flexField_1:!=:foo',
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
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1481634649);
        (new EvaluateDisplayConditions())->addData($input);
    }

    /**
     * Test scenarios for "a display condition references the value of another field"
     *
     * @return array
     */
    public function addDataRemovesTcaReferencingOtherFieldsInDisplayConditionDataProvider()
    {
        return [

            // tca field to tca field value tests
            'remove tca field by tca field value' => [
                // path that should be removed from 'processedTca' by condition
                'columns/field_2',
                // 'databaseRow'
                [
                    'field_1' => 'foo',
                ],
                // 'processedTca'
                [
                    'columns' => [
                        'field_2' => [
                            'displayCond' => 'FIELD:field_1:!=:foo',
                        ],
                    ],
                ],
            ],

            // flex field to tca field value tests
            'remove flex form field by tca field value' => [
                'columns/field_2/config/ds/sheets/sheet_1/ROOT/el/flexField_1',
                [
                    'field_1' => 'foo',
                ],
                [
                    'columns' => [
                        'field_2' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [
                                                        'displayCond' => 'FIELD:parentRec.field_1:!=:foo',
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

            // flex field to flex field value on same sheet tests
            'remove flex form field by flex field value on same flex sheet' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/flexField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                    'flexField_2' => [
                                                        'displayCond' => 'FIELD:flexField_1:!=:foo',
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

            'remove flex form field by flex field value on same flex sheet with dot in flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/flexField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                    'flexField_2' => [
                                                        'displayCond' => 'FIELD:flexField_1:!=:foo',
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

            'remove flex form field by flex field value on same flex sheet with dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/flexField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                    'flexField_2' => [
                                                        'displayCond' => 'FIELD:flexField.1:!=:foo',
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

            'remove flex form field by flex field value on same flex sheet with dot in flex sheet name and dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/flexField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                    'flexField_2' => [
                                                        'displayCond' => 'FIELD:flexField.1:!=:foo',
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

            'remove flex form field by flex field value on same flex sheet with specified flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/flexField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                    'flexField_2' => [
                                                        'displayCond' => 'FIELD:sheet_1.flexField_1:!=:foo',
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

            'remove flex form field by flex field value on same flex sheet with specified flex sheet name with dot in flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/flexField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                    'flexField_2' => [
                                                        'displayCond' => 'FIELD:sheet.1.flexField_1:!=:foo',
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

            'remove flex form field by flex field value on same flex sheet with specified flex sheet name with dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/flexField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                    'flexField_2' => [
                                                        'displayCond' => 'FIELD:sheet_1.flexField.1:!=:foo',
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

            'remove flex form field by flex field value on same flex sheet with specified flex sheet name with dot in flex sheet name and dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/flexField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                    'flexField_2' => [
                                                        'displayCond' => 'FIELD:sheet.1.flexField.1:!=:foo',
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

            // flex field to flex field value on other sheet tests
            'remove flex form field by flex field value on other flex sheet' => [
                'columns/field_1/config/ds/sheets/sheet_2/ROOT/el/flexField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                ],
                                            ],
                                        ],
                                        'sheet_2' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [
                                                        'displayCond' => 'FIELD:sheet_1.flexField_1:!=:foo',
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

            'remove flex form field by flex field value on other flex sheet with dot in flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet_2/ROOT/el/flexField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                ],
                                            ],
                                        ],
                                        'sheet_2' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [
                                                        'displayCond' => 'FIELD:sheet.1.flexField_1:!=:foo',
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

            'remove flex form field by flex field value on other flex sheet with dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet_2/ROOT/el/flexField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                ],
                                            ],
                                        ],
                                        'sheet_2' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [
                                                        'displayCond' => 'FIELD:sheet_1.flexField.1:!=:foo',
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

            'remove flex form field by flex field value on other flex sheet with dot in flex sheet name and dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet_2/ROOT/el/flexField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                ],
                                            ],
                                        ],
                                        'sheet_2' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [
                                                        'displayCond' => 'FIELD:sheet.1.flexField.1:!=:foo',
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

            // flex sheet to tca field value tests
            'remove flex form sheet by tca field value' => [
                'columns/field_2/config/ds/sheets/sheet_1',
                [
                    'field_1' => 'foo',
                ],
                [
                    'columns' => [
                        'field_2' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'displayCond' => 'FIELD:parentRec.field_1:!=:foo',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // flex sheet to flex field value tests
            'remove flex form sheet by flex field value on different flex sheet' => [
                'columns/field_1/config/ds/sheets/sheet_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                ],
                                            ],
                                        ],
                                        'sheet_2' => [
                                            'ROOT' => [
                                                'displayCond' => 'FIELD:sheet_1.flexField_1:!=:foo',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'remove flex form sheet by flex field value on different flex sheet with dot in flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet.2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                ],
                                            ],
                                        ],
                                        'sheet.2' => [
                                            'ROOT' => [
                                                'displayCond' => 'FIELD:sheet.1.flexField_1:!=:foo',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'remove flex form sheet by flex field value on different flex sheet with dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                ],
                                            ],
                                        ],
                                        'sheet_2' => [
                                            'ROOT' => [
                                                'displayCond' => 'FIELD:sheet_1.flexField.1:!=:foo',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'remove flex form sheet by flex field value on different flex sheet with dot in flex sheet name and dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet.2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                ],
                                            ],
                                        ],
                                        'sheet.2' => [
                                            'ROOT' => [
                                                'displayCond' => 'FIELD:sheet.1.flexField.1:!=:foo',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // flex section container field to tca value tests
            'remove flex section container field by tca field value' => [
                'columns/field_2/config/ds/sheets/sheet_1/ROOT/el/section_1/children/1/el/containerField_1',
                [
                    'field_1' => 'foo',
                    'field_2' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'section_1' => [
                                        'el' => [
                                            '1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_2' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [
                                                                        'displayCond' => 'FIELD:parentRec.field_1:!=:foo',
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

            // flex section container field to flex field value of same sheet
            'remove flex section container field by flex field value on same flex sheet' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/section_1/children/1/el/containerField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [
                                                                        'displayCond' => 'FIELD:flexField_1:!=:foo',
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

            'remove flex section container field by flex field value on same flex sheet with dot in flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/section_1/children/1/el/containerField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [
                                                                        'displayCond' => 'FIELD:flexField_1:!=:foo',
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

            'remove flex section container field by flex field value on same flex sheet with dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/section_1/children/1/el/containerField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [
                                                                        'displayCond' => 'FIELD:flexField.1:!=:foo',
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

            'remove flex section container field by flex field value on same flex sheet with dot in flex sheet name and dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/section_1/children/1/el/containerField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [
                                                                        'displayCond' => 'FIELD:flexField.1:!=:foo',
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

            'remove flex section container field by flex field value on same flex sheet with specified flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/section_1/children/1/el/containerField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [
                                                                        'displayCond' => 'FIELD:sheet_1.flexField_1:!=:foo',
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

            'remove flex section container field by flex field value on same flex sheet with specified flex sheet name with dot in flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/section_1/children/1/el/containerField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [
                                                                        'displayCond' => 'FIELD:sheet.1.flexField_1:!=:foo',
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

            'remove flex section container field by flex field value on same flex sheet with specified flex sheet name with dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/section_1/children/1/el/containerField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [
                                                                        'displayCond' => 'FIELD:sheet_1.flexField.1:!=:foo',
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

            'remove flex section container field by flex field value on same flex sheet with specified flex sheet name with dot in flex sheet name and dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/section_1/children/1/el/containerField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [
                                                                        'displayCond' => 'FIELD:sheet.1.flexField.1:!=:foo',
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

            // flex section container field to flex field value of other sheet
            'remove flex section container field by flex field value on other flex sheet' => [
                'columns/field_1/config/ds/sheets/sheet_2/ROOT/el/section_1/children/1/el/containerField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                            'sheet_2' => [
                                'lDEF' => [
                                    'section_1' => [
                                        'el' => [
                                            '1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                ],
                                            ],
                                        ],
                                        'sheet_2' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [
                                                                        'displayCond' => 'FIELD:sheet_1.flexField_1:!=:foo',
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

            'remove flex section container field by flex field value on other flex sheet with dot in flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet_2/ROOT/el/section_1/children/1/el/containerField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                            'sheet_2' => [
                                'lDEF' => [
                                    'section_1' => [
                                        'el' => [
                                            '1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField_1' => [],
                                                ],
                                            ],
                                        ],
                                        'sheet_2' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [
                                                                        'displayCond' => 'FIELD:sheet.1.flexField_1:!=:foo',
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

            'remove flex section container field by flex field value on other flex sheet with dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet_2/ROOT/el/section_1/children/1/el/containerField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                            'sheet_2' => [
                                'lDEF' => [
                                    'section_1' => [
                                        'el' => [
                                            '1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                ],
                                            ],
                                        ],
                                        'sheet_2' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [
                                                                        'displayCond' => 'FIELD:sheet_1.flexField.1:!=:foo',
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

            'remove flex section container field by flex field value on other flex sheet with dot in flex sheet name and dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet_2/ROOT/el/section_1/children/1/el/containerField_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'flexField.1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                            'sheet_2' => [
                                'lDEF' => [
                                    'section_1' => [
                                        'el' => [
                                            '1' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'flexField.1' => [],
                                                ],
                                            ],
                                        ],
                                        'sheet_2' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [
                                                                        'displayCond' => 'FIELD:sheet.1.flexField.1:!=:foo',
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

            // flex section container field to flex field value of same container
            'remove flex section container field by flex container field value of same container' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/section_1/children/1/el/containerField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'containerField_1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [],
                                                                    'containerField_2' => [
                                                                        'displayCond' => 'FIELD:containerField_1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with dot in flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/section_1/children/1/el/containerField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'containerField_1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [],
                                                                    'containerField_2' => [
                                                                        'displayCond' => 'FIELD:containerField_1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with dot in container flex field name' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/section_1/children/1/el/containerField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'containerField.1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField.1' => [],
                                                                    'containerField_2' => [
                                                                        'displayCond' => 'FIELD:containerField.1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with dot in flex sheet name and dot in container flex field name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/section_1/children/1/el/containerField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'containerField.1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField.1' => [],
                                                                    'containerField_2' => [
                                                                        'displayCond' => 'FIELD:containerField.1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with specified flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/section_1/children/1/el/containerField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'containerField_1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [],
                                                                    'containerField_2' => [
                                                                        'displayCond' => 'FIELD:sheet_1.containerField_1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with specified flex sheet name with dot in flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/section_1/children/1/el/containerField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'containerField_1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField_1' => [],
                                                                    'containerField_2' => [
                                                                        'displayCond' => 'FIELD:sheet.1.containerField_1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with specified flex sheet name with dot in container flex field name' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/section_1/children/1/el/containerField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'containerField.1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField.1' => [],
                                                                    'containerField_2' => [
                                                                        'displayCond' => 'FIELD:sheet_1.containerField.1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with specified flex sheet name with dot in flex sheet name and dot in container flex field name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/section_1/children/1/el/containerField_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'containerField.1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'containerField.1' => [],
                                                                    'containerField_2' => [
                                                                        'displayCond' => 'FIELD:sheet.1.containerField.1:!=:foo',
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

            // flex section container field to flex field value of same container with naming clash to flex field value of same sheet
            'remove flex section container field by flex container field value of same container with naming clash' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/section_1/children/1/el/field_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'field_1' => [
                                        'vDEF' => [
                                            0 => 'bar',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'field_1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'field_1' => [],
                                                                    'field_2' => [
                                                                        'displayCond' => 'FIELD:field_1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with naming clash with dot in flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/section_1/children/1/el/field_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'field_1' => [
                                        'vDEF' => [
                                            0 => 'bar',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'field_1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'field_1' => [],
                                                                    'field_2' => [
                                                                        'displayCond' => 'FIELD:field_1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with naming clash with dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/section_1/children/1/el/field_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'field.1' => [
                                        'vDEF' => [
                                            0 => 'bar',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'field.1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'field.1' => [],
                                                                    'field_2' => [
                                                                        'displayCond' => 'FIELD:field.1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with naming clash with dot in flex sheet name and dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/section_1/children/1/el/field_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'field.1' => [
                                        'vDEF' => [
                                            0 => 'bar',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'field.1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'field.1' => [],
                                                                    'field_2' => [
                                                                        'displayCond' => 'FIELD:field.1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with naming clash with specified flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/section_1/children/1/el/field_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'field_1' => [
                                        'vDEF' => [
                                            0 => 'bar',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'field_1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'field_1' => [],
                                                                    'field_2' => [
                                                                        'displayCond' => 'FIELD:sheet_1.field_1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with naming clash with specified flex sheet name with dot in flex sheet name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/section_1/children/1/el/field_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'field_1' => [
                                        'vDEF' => [
                                            0 => 'bar',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'field_1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'field_1' => [],
                                                                    'field_2' => [
                                                                        'displayCond' => 'FIELD:sheet.1.field_1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with naming clash with specified flex sheet name with dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/section_1/children/1/el/field_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'field.1' => [
                                        'vDEF' => [
                                            0 => 'bar',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'field.1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'field.1' => [],
                                                                    'field_2' => [
                                                                        'displayCond' => 'FIELD:sheet_1.field.1:!=:foo',
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

            'remove flex section container field by flex container field value of same container with naming clash with specified flex sheet name with dot in flex sheet name and dot in flex field name' => [
                'columns/field_1/config/ds/sheets/sheet.1/ROOT/el/section_1/children/1/el/field_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet.1' => [
                                'lDEF' => [
                                    'field.1' => [
                                        'vDEF' => [
                                            0 => 'bar',
                                        ],
                                    ],
                                    'section_1' => [
                                        'el' => [
                                            '1' => [
                                                'container_1' => [
                                                    'el' => [
                                                        'field.1' => [
                                                            'vDEF' => 'foo',
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
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet.1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'children' => [
                                                            '1' => [
                                                                'el' => [
                                                                    'field.1' => [],
                                                                    'field_2' => [
                                                                        'displayCond' => 'FIELD:sheet.1.field.1:!=:foo',
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

            // Some special scenarios
            'remove flex sheet by nested OR condition' => [
                'columns/field_1/config/ds/sheets/sheet_2',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'field_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'type' => 'array',
                                                'el' => [
                                                    'field_1' => [],
                                                ],
                                            ],
                                        ],
                                        'sheet_2' => [
                                            'ROOT' => [
                                                'type' => 'array',
                                                'el' => [],
                                                'displayCond' => [
                                                    'OR' => [
                                                        'FIELD:sheet_1.field_1:=:LIST',
                                                        'FIELD:sheet_1.field_1:!=:foo',
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

            // flex section container has a display condition
            'remove flex section container' => [
                'columns/field_1/config/ds/sheets/sheet_1/ROOT/el/section_1',
                [
                    'field_1' => [
                        'data' => [
                            'sheet_1' => [
                                'lDEF' => [
                                    'field_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'sheet_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'field_1' => [],
                                                    'section_1' => [
                                                        'type' => 'array',
                                                        'section' => 1,
                                                        'displayCond' => 'FIELD:field_1:!=:foo',
                                                        'children' => [],
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

            // field name to sheet name overlap
            'remove flex field even if sheet name and field name overlap' => [
                'columns/field_1/config/ds/sheets/field_1/ROOT/el/field_2',
                [
                    'field_1' => [
                        'data' => [
                            'field_1' => [
                                'lDEF' => [
                                    'field_1' => [
                                        'vDEF' => [
                                            0 => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'columns' => [
                        'field_1' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => [
                                    'sheets' => [
                                        'field_1' => [
                                            'ROOT' => [
                                                'el' => [
                                                    'field_1' => [],
                                                    'field_2' => [
                                                        'displayCond' => 'FIELD:field_1.field_1:!=:foo',
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
    }

    /**
     * @test
     * @dataProvider addDataRemovesTcaReferencingOtherFieldsInDisplayConditionDataProvider
     * @param $processedTcaFieldToBeRemovedPath
     * @param array $databaseRow
     * @param array $processedTca
     */
    public function addDataRemovesTcaReferencingOtherFieldsInDisplayCondition($processedTcaFieldToBeRemovedPath, array $databaseRow, array $processedTca)
    {
        $input = [
            'databaseRow' => $databaseRow,
            'processedTca' => $processedTca,
        ];
        $expected = ArrayUtility::removeByPath($input, 'processedTca/' . $processedTcaFieldToBeRemovedPath);
        self::assertSame($expected, (new EvaluateDisplayConditions())->addData($input));
    }

    /**
     * Returns data sets for the test matchConditionStrings
     * Each data set is an array with the following elements:
     * - the condition string
     * - the current record
     * - the expected result
     *
     * @return array
     */
    public function conditionStringDataProvider()
    {
        return [
            'Field is not greater zero if not given' => [
                'FIELD:uid:>:0',
                [],
                false,
            ],
            'Field is not equal 0 if not given' => [
                'FIELD:uid:=:0',
                [],
                false,
            ],
            'Field is not greater zero if empty array given' => [
                'FIELD:foo:>:0',
                ['foo' => []],
                false,
            ],
            'Field is not greater than or equal to zero if empty array given' => [
                'FIELD:foo:>=:0',
                ['foo' => []],
                false,
            ],
            'Field is less than 1 if empty array given' => [
                'FIELD:foo:<:1',
                ['foo' => []],
                true,
            ],
            'Field is less than or equal to 1 if empty array given' => [
                'FIELD:foo:<=:1',
                ['foo' => []],
                true,
            ],
            'Field does not equal 0 if empty array given' => [
                'FIELD:foo:=:0',
                ['foo' => []],
                false,
            ],
            'Field value string comparison' => [
                'FIELD:foo:=:bar',
                ['foo' => 'bar'],
                true,
            ],
            'Field value string comparison against list' => [
                'FIELD:foo:IN:bar,baz',
                ['foo' => 'baz'],
                true,
            ],
            'Field value comparison of 1 against multi-value field of 5 returns true' => [
                'FIELD:content:BIT:1',
                ['content' => '5'],
                true,
            ],
            'Field value comparison of 2 against multi-value field of 5 returns false' => [
                'FIELD:content:BIT:2',
                ['content' => '5'],
                false,
            ],
            'Field value of 5 negated comparison against multi-value field of 5 returns false' => [
                'FIELD:content:!BIT:5',
                ['content' => '5'],
                false,
            ],
            'Field value comparison for required value is false for different value' => [
                'FIELD:foo:REQ:FALSE',
                ['foo' => 'bar'],
                false,
            ],
            'Field value string not equal comparison' => [
                'FIELD:foo:!=:baz',
                ['foo' => 'bar'],
                true,
            ],
            'Field value string not equal comparison against list' => [
                'FIELD:foo:!IN:bar,baz',
                ['foo' => 'foo'],
                true,
            ],
            'Field value in range' => [
                'FIELD:uid:-:3-42',
                ['uid' => '23'],
                true,
            ],
            'Field value greater than' => [
                'FIELD:uid:>=:42',
                ['uid' => '23'],
                false,
            ],
            'Field value containing colons' => [
                'FIELD:foo:=:x:y:z',
                ['foo' => 'x:y:z'],
                true,
            ],
            'New is TRUE for new comparison with TRUE' => [
                'REC:NEW:TRUE',
                ['uid' => null],
                true,
            ],
            'New is FALSE for new comparison with FALSE' => [
                'REC:NEW:FALSE',
                ['uid' => null],
                false,
            ],
            'New is FALSE for not new element' => [
                'REC:NEW:TRUE',
                ['uid' => 42],
                false,
            ],
            'New is TRUE for not new element compared to FALSE' => [
                'REC:NEW:FALSE',
                ['uid' => 42],
                true,
            ],
            'Version is TRUE for versioned row' => [
                'VERSION:IS:TRUE',
                [
                    'uid' => 42,
                    't3ver_oid' => 12,
                ],
                true,
            ],
            'Version is TRUE for not versioned row compared with FALSE' => [
                'VERSION:IS:FALSE',
                [
                    'uid' => 42,
                    't3ver_oid' => 0,
                ],
                true,
            ],
            'Version is TRUE for NULL row compared with TRUE' => [
                'VERSION:IS:TRUE',
                [
                    'uid' => null,
                    'pid' => null,
                ],
                false,
            ],
            'Single condition with AND compares to TRUE if the one is OK' => [
                [
                    'AND' => [
                        'FIELD:testField:>:9',
                    ],
                ],
                [
                    'testField' => 10,
                ],
                true,
            ],
            'Multiple conditions with AND compare to TRUE if all are OK' => [
                [
                    'AND' => [
                        'FIELD:testField:>:9',
                        'FIELD:testField:<:11',
                    ],
                ],
                [
                    'testField' => 10,
                ],
                true,
            ],
            'Multiple conditions with AND compare to FALSE if one fails' => [
                [
                    'AND' => [
                        'FIELD:testField:>:9',
                        'FIELD:testField:<:11',
                    ],
                ],
                [
                    'testField' => 99,
                ],
                false,
            ],
            'Single condition with OR compares to TRUE if the one is OK' => [
                [
                    'OR' => [
                        'FIELD:testField:>:9',
                    ],
                ],
                [
                    'testField' => 10,
                ],
                true,
            ],
            'Multiple conditions with OR compare to TRUE if one is OK' => [
                [
                    'OR' => [
                        'FIELD:testField:<:9',
                        'FIELD:testField:<:11',
                    ],
                ],
                [
                    'testField' => 10,
                ],
                true,
            ],
            'Multiple conditions with OR compare to FALSE is all fail' => [
                [
                    'OR' => [
                        'FIELD:testField:<:9',
                        'FIELD:testField:<:11',
                    ],
                ],
                [
                    'testField' => 99,
                ],
                false,
            ],
            'Multiple nested conditions evaluate to TRUE' => [
                [
                    'AND' => [
                        'FIELD:testField:>:9',
                        'OR' => [
                            'FIELD:testField:<:100',
                            'FIELD:testField:>:-100',
                        ],
                    ],
                ],
                [
                    'testField' => 10,
                ],
                true,
            ],
            'Multiple nested conditions evaluate to FALSE' => [
                [
                    'AND' => [
                        'FIELD:testField:>:9',
                        'OR' => [
                            'FIELD:testField:<:100',
                            'FIELD:testField:>:-100',
                        ],
                    ],
                ],
                [
                    'testField' => -999,
                ],
                false,
            ],
        ];
    }

    /**
     * @param string $condition
     * @param array $record
     * @param string $expectedResult
     * @dataProvider conditionStringDataProvider
     * @test
     */
    public function matchConditionStrings($condition, array $record, $expectedResult)
    {
        $input = [
            'databaseRow' => $record,
            'processedTca' => [
                'columns' => [
                    'testField' => [
                        'displayCond' => $condition,
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $backendUserAuthenticationProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserAuthenticationProphecy->reveal();

        $expected = $input;
        if ($expectedResult) {
            // displayCond vanished from result array after this data provider is done
            unset($expected['processedTca']['columns']['testField']['displayCond']);
        } else {
            unset($expected['processedTca']['columns']['testField']);
        }
        self::assertSame($expected, (new EvaluateDisplayConditions())->addData($input));
    }

    /**
     * @param string $condition
     * @param array $record
     * @param string $expectedResult
     * @dataProvider conditionStringDataProvider
     * @test
     */
    public function matchConditionStringsWithRecordTestFieldBeingArray($condition, array $record, $expectedResult)
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'testField' => [
                        'displayCond' => $condition,
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $input['databaseRow'] = $record;
        if (!empty($record['testField'])) {
            $input['databaseRow'] = [
                'testField' => [
                    'key' => $record['testField'],
                ],
            ];
        }

        $backendUserAuthenticationProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserAuthenticationProphecy->reveal();

        $expected = $input;
        if ($expectedResult) {
            // displayCond vanished from result array after this data provider is done
            unset($expected['processedTca']['columns']['testField']['displayCond']);
        } else {
            unset($expected['processedTca']['columns']['testField']);
        }
        self::assertSame($expected, (new EvaluateDisplayConditions())->addData($input));
    }

    /**
     * @test
     */
    public function matchHideForNonAdminsReturnsTrueIfBackendUserIsAdmin()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'displayCond' => 'HIDE_FOR_NON_ADMINS',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        /** @var BackendUserAuthentication|ObjectProphecy backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(true);

        $expected = $input;
        unset($expected['processedTca']['columns']['aField']['displayCond']);

        self::assertSame($expected, (new EvaluateDisplayConditions())->addData($input));
    }

    /**
     * @test
     */
    public function matchHideForNonAdminsReturnsFalseIfBackendUserIsNotAdmin()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'displayCond' => 'HIDE_FOR_NON_ADMINS',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        /** @var BackendUserAuthentication|ObjectProphecy backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(false);

        $expected = $input;
        unset($expected['processedTca']['columns']['aField']);
        self::assertSame($expected, (new EvaluateDisplayConditions())->addData($input));
    }
}
