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

use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class EvaluateDisplayConditionsTest extends UnitTestCase {

	/**
	 * @var EvaluateDisplayConditions
	 */
	protected $subject;

	protected function setUp() {
		$this->subject = new EvaluateDisplayConditions();
	}

	/**
	 * @test
	 */
	public function addDataRemovesTcaColumnsHiddenByDisplayCondition() {
		$input = [
			'databaseRow' => [
				'aField' => 'aField',
				'bField' => 'bField',
				'cField' => 1,
			],
			'recordTypeValue' => 'aType',
			'processedTca' => [
				'types' => [
					'aType' => [
						'showitem' => '--palette--;aPalette;2,bField,cField'
					],
				],
				'palettes' => [
					'2' => array(
						'showitem' => 'aField',
						'canNotCollapse' => TRUE
					),
				],
				'columns' => [
					'aField' => [
						'displayCond' => 'FIELD:cField:=:0',
						'config' => [
							'type' => 'input',
						]
					],
					'bField' => [
						'displayCond' => 'FIELD:cField:=:1',
						'config' => [
							'type' => 'input',
						]
					],
					'cField' => [
						'config' => [
							'type' => 'input',
						]
					]
				]
			]
		];

		$expected = $input;
		unset($expected['processedTca']['columns']['aField']);

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesFlexformSheetsHiddenByDisplayCondition() {
		$input = [
			'databaseRow' => [
				'aField' => [
					'data' => [
						'sGeneral' => [
							'lDEF' => [
								'mmType' => [
									'vDEF' => [
										0 => 'video',
									],
								],
								'mmUseHTML5' => [
									'vDEF' => '0',
								],
							],
						],
						'sVideo' => [
							'lDEF' => [],
						],
						'sAudio' => [
							'lDEF' => []
						],
					]
				]
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds' => [
								'meta' => [],
								'sheets' => [
									'sGeneral' => [
										'ROOT' => [
											'type' => 'array',
											'el' => [
												'mmType' => [
													'config' => [
														'type' => 'select',
														'items' => [],
													],
												],
												'mmUseHTML5' => [
													'displayCond' => 'FIELD:mmType:!=:audio',
													'config' => [
														'type' => 'check',
														'default' => '0',
														'items' => [],
													],
												],
											],
											'sheetTitle' => 'sGeneral',
										],
									],
									'sVideo' => [
										'ROOT' => [
											'type' => 'array',
											'el' => [],
											'sheetTitle' => 'sVideo',
											'displayCond' => 'FIELD:sGeneral.mmType:!=:audio',
										],
									],
									'sAudio' => [
										'ROOT' => [
											'type' => 'array',
											'el' => [],
											'sheetTitle' => 'sAudio',
											'displayCond' => 'FIELD:sGeneral.mmType:=:audio',
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
		unset($expected['processedTca']['columns']['aField']['config']['ds']['sheets']['sAudio']);
		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesFlexformFieldsHiddenByDisplayCondition() {
		$input = [
			'databaseRow' => [
				'aField' => [
					'data' => [
						'sGeneral' => [
							'lDEF' => [
								'mmType' => [
									'vDEF' => [
										0 => 'audio',
									],
								],
								'mmUseHTML5' => [
									'vDEF' => '0',
								],
							],
						],
					]
				]
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'list_type,CType',
							'ds' => [
								'meta' => [],
								'sheets' => [
									'sGeneral' => [
										'ROOT' => [
											'type' => 'array',
											'el' => [
												'mmType' => [
													'config' => [
														'type' => 'select',
														'items' => [],
													],
												],
												'mmUseHTML5' => [
													'displayCond' => 'FIELD:mmType:!=:audio',
													'config' => [
														'type' => 'check',
														'default' => '0',
														'items' => [],
													],
												],
												'mmUseCurl' => [
													'displayCond' => 'FIELD:mmType:=:audio',
													'config' => [
														'type' => 'check',
														'default' => '0',
														'items' => [],
													],
												],
											],
											'sheetTitle' => 'aTitle',
										],
									],
									'secondSheet' => [
										'ROOT' => [
											'type' => 'array',
											'el' => [
												'foo' => [
													'config' => [
														'type' => 'select',
														'items' => [],
													],
												],
											],
											'sheetTitle' => 'bTitle',
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
		unset($expected['processedTca']['columns']['aField']['config']['ds']['sheets']['sGeneral']['ROOT']['el']['mmUseHTML5']);

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function matchHideForNonAdminsReturnsTrueIfBackendUserIsAdmin() {
		$input = [
			'databaseRow' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'displayCond' => 'HIDE_FOR_NON_ADMINS',
						'config' => [
							'type' => 'input',
						]
					],
				]
			]
		];

		/** @var BackendUserAuthentication|ObjectProphecy backendUserProphecy */
		$backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
		$backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(TRUE);

		$expected = $input;

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function matchHideForNonAdminsReturnsFalseIfBackendUserIsNotAdmin() {
		$input = [
			'databaseRow' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'displayCond' => 'HIDE_FOR_NON_ADMINS',
						'config' => [
							'type' => 'input',
						]
					],
				]
			]
		];

		/** @var BackendUserAuthentication|ObjectProphecy backendUserProphecy */
		$backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
		$backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(FALSE);

		$expected = $input;
		unset($expected['processedTca']['columns']['aField']);
		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * The HIDE_L10N_SIBLINGS condition is deprecated, this test only ensures that it can be successfully parsed
	 *
	 * @test
	 */
	public function matchHideL10NSiblingsReturnsTrue() {
		$input = [
			'databaseRow' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'displayCond' => 'HIDE_L10N_SIBLINGS',
						'config' => [
							'type' => 'input',
						]
					],
				]
			]
		];

		$expected = $input;

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function matchHideL10NSiblingsExceptAdminReturnsTrue() {
		$input = [
			'databaseRow' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'displayCond' => 'HIDE_L10N_SIBLINGS:except_admin',
						'config' => [
							'type' => 'input',
						]
					],
				]
			]
		];

		$expected = $input;

		$this->assertSame($expected, $this->subject->addData($input));
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
	public function conditionStringDataProvider() {
		return [
			'Invalid condition string' => [
				'xINVALIDx:',
				[],
				FALSE,
			],
			'Not loaded extension compares to loaded as FALSE' => [
				'EXT:neverloadedext:LOADED:TRUE',
				[],
				FALSE,
			],
			'Not loaded extension compares to not loaded as TRUE' => [
				'EXT:neverloadedext:LOADED:FALSE',
				[],
				TRUE,
			],
			'Loaded extension compares to TRUE' => [
				'EXT:backend:LOADED:TRUE',
				[],
				TRUE,
			],
			'Loaded extension compares to FALSE' => [
				'EXT:backend:LOADED:FALSE',
				[],
				FALSE,
			],
			'Field is not greater zero if not given' => [
				'FIELD:uid:>:0',
				[],
				FALSE,
			],
			'Field is not equal 0 if not given' => [
				'FIELD:uid:=:0',
				[],
				FALSE,
			],
			'Field value string comparison' => [
				'FIELD:foo:=:bar',
				['foo' => 'bar'],
				TRUE,
			],
			'Field value string comparison against list' => [
				'FIELD:foo:IN:bar,baz',
				['foo' => 'baz'],
				TRUE,
			],
			'Field value comparison of 1 against multi-value field of 5 returns true' => [
				'FIELD:content:BIT:1',
				['content' => '5'],
				TRUE
			],
			'Field value comparison of 2 against multi-value field of 5 returns false' => [
				'FIELD:content:BIT:2',
				['content' => '5'],
				FALSE
			],
			'Field value of 5 negated comparison against multi-value field of 5 returns false' => [
				'FIELD:content:!BIT:5',
				['content' => '5'],
				FALSE
			],
			'Field value comparison for required value is false for different value' => [
				'FIELD:foo:REQ:FALSE',
				['foo' => 'bar'],
				FALSE,
			],
			'Field value string not equal comparison' => [
				'FIELD:foo:!=:baz',
				['foo' => 'bar'],
				TRUE,
			],
			'Field value string not equal comparison against list' => [
				'FIELD:foo:!IN:bar,baz',
				['foo' => 'foo'],
				TRUE,
			],
			'Field value in range' => [
				'FIELD:uid:-:3-42',
				['uid' => '23'],
				TRUE,
			],
			'Field value greater than' => [
				'FIELD:uid:>=:42',
				['uid' => '23'],
				FALSE,
			],
			'Field is value for default language without flexform' => [
				'HIDE_L10N_SIBLINGS',
				[],
				TRUE,
			],
			'New is TRUE for new comparison with TRUE' => [
				'REC:NEW:TRUE',
				['uid' => NULL],
				TRUE,
			],
			'New is FALSE for new comparison with FALSE' => [
				'REC:NEW:FALSE',
				['uid' => NULL],
				FALSE,
			],
			'New is FALSE for not new element' => [
				'REC:NEW:TRUE',
				['uid' => 42],
				FALSE,
			],
			'New is TRUE for not new element compared to FALSE' => [
				'REC:NEW:FALSE',
				['uid' => 42],
				TRUE,
			],
			'Version is TRUE for versioned row' => [
				'VERSION:IS:TRUE',
				[
					'uid' => 42,
					'pid' => -1
				],
				TRUE,
			],
			'Version is TRUE for not versioned row compared with FALSE' => [
				'VERSION:IS:FALSE',
				[
					'uid' => 42,
					'pid' => 1
				],
				TRUE,
			],
			'Version is TRUE for NULL row compared with TRUE' => [
				'VERSION:IS:TRUE',
				[
					'uid' => NULL,
					'pid' => NULL,
				],
				FALSE,
			],
			'Multiple conditions with AND compare to TRUE if all are OK' => [
				[
					'AND' => [
						'FIELD:testField:>:9',
						'FIELD:testField:<:11',
					],
				],
				[
					'testField' => 10
				],
				TRUE,
			],
			'Multiple conditions with AND compare to FALSE if one fails' => [
				[
					'AND' => [
						'FIELD:testField:>:9',
						'FIELD:testField:<:11',
					]
				],
				[
					'testField' => 99
				],
				FALSE,
			],
			'Multiple conditions with OR compare to TRUE if one is OK' => [
				[
					'OR' => [
						'FIELD:testField:<:9',
						'FIELD:testField:<:11',
					],
				],
				[
					'testField' => 10
				],
				TRUE,
			],
			'Multiple conditions with OR compare to FALSE is all fail' => [
				[
					'OR' => [
						'FIELD:testField:<:9',
						'FIELD:testField:<:11',
					],
				],
				[
					'testField' => 99
				],
				FALSE,
			],
			'Multiple conditions without operator due to misconfiguration compare to TRUE' => [
				[
					'' => [
						'FIELD:testField:<:9',
						'FIELD:testField:>:11',
					]
				],
				[
					'testField' => 99
				],
				TRUE,
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
					'testField' => 10
				],
				TRUE,
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
					'testField' => -999
				],
				FALSE,
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
	public function matchConditionStrings($condition, array $record, $expectedResult) {
		$input = [
			'databaseRow' => $record,
			'processedTca' => [
				'columns' => [
					'testField' => [
						'displayCond' => $condition,
						'config' => [
							'type' => 'input',
						]
					],
				]
			]
		];

		$expected = $input;
		if (!$expectedResult) {
			unset($expected['processedTca']['columns']['testField']);
		}
		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @param string $condition
	 * @param array $record
	 * @param string $expectedResult
	 *
	 * @dataProvider conditionStringDataProvider
	 * @test
	 */
	public function matchConditionStringsWithRecordTestFieldBeingArray($condition, array $record, $expectedResult) {
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
		$input['databaseRow'] = $record ?: ['testField' => ['key' => $record['testField']]];

		$expected = $input;
		if (!$expectedResult) {
			unset($expected['processedTca']['columns']['testField']);
		}
		$this->assertSame($expected, $this->subject->addData($input));
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
	public function flexformConditionStringDataProvider() {
		return [
			'Flexform value invalid comparison' => [
				'FIELD:foo:=:bar',
				[
					'foo' => 'bar',
					'testField' => [
						'data' => [
							'sDEF' => [
								'lDEF' => [],
							],
						],
					],
				],
				FALSE,
			],
			'Flexform value valid comparison' => [
				'FIELD:parentRec.foo:=:bar',
				[
					'foo' => 'bar',
					'testField' => [
						'data' => [
							'sDEF' => [
								'lDEF' => [],
							],
						],
					],
				],
				TRUE,
			],
		];
	}

	/**
	 * @param string $condition
	 * @param array $record
	 * @param string $expectedResult
	 * @dataProvider flexformConditionStringDataProvider
	 * @test
	 */
	public function matchFlexformConditionStrings($condition, array $record, $expectedResult) {
		$input = [
			'databaseRow' => $record,
			'processedTca' => [
				'columns' => [
					'testField' => [
						'config' => [
							'type' => 'flex',
							'ds' => [
								'meta' => [],
								'sheets' => [
									'sDEF' => [
										'ROOT' => [
											'type' => 'array',
											'el' => [
												'flexTestField' => [
													'displayCond' => $condition,
													'config' => [
														'type' => 'input',
													],
												],
											],
										],
									],
								]
							],
						],
					],
				],
			],
		];

		$expected = $input;
		if (!$expectedResult) {
			unset($expected['processedTca']['columns']['testField']['config']['ds']['sheets']['sDEF']['ROOT']['el']['flexTestField']);
		}
		$this->assertSame($expected, $this->subject->addData($input));
	}

}
