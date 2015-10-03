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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration;

/**
 * Test case
 */
class TcaInlineConfigurationTest extends UnitTestCase {

	/**
	 * @var TcaInlineConfiguration
	 */
	protected $subject;

	protected function setUp() {
		$this->subject = new TcaInlineConfiguration();
	}

	/**
	 * @var array Set of default controls
	 */
	protected $defaultConfig = [
		'type' => 'inline',
		'foreign_table' => 'aForeignTableName',
		'minitems' => 0,
		'maxitems' => 100000,
		'behaviour' => [
			'localizationMode' => 'none',
		],
		'appearance' => [
			'levelLinksPosition' => 'top',
			'showPossibleLocalizationRecords' => FALSE,
			'showRemovedLocalizationRecords' => FALSE,
			'enabledControls' => [
				'info' => TRUE,
				'new' => TRUE,
				'dragdrop' => TRUE,
				'sort' => TRUE,
				'hide' => TRUE,
				'delete' => TRUE,
				'localize' => TRUE,
			],
		],
	];

	/**
	 * @test
	 */
	public function addDataThrowsExceptionForInlineFieldWithoutForeignTableConfig() {
		$input = [
			'databaseRow' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
						],
					],
				],
			],
		];
		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1443793404);
		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataSetsDefaults() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
						],
					],
				],
			],
		];
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataKeepsGivenMinitems() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'minitems' => 23,
						],
					],
				],
			],
		];
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['minitems'] = 23;
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataForcesMinitemsPositive() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'minitems' => '-23',
						],
					],
				],
			],
		];
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['minitems'] = 0;
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataKeepsGivenMaxitems() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'maxitems' => 23,
						],
					],
				],
			],
		];
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['maxitems'] = 23;
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataForcesMaxitemsPositive() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'maxitems' => '-23',
						],
					],
				],
			],
		];
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['maxitems'] = 1;
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfLocalizationModeIsSetButNotToKeepOrSelect() {
		$input = [
			'defaultLanguageRow' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'behaviour' => [
								'localizationMode' => 'foo',
							]
						],
					],
				],
			],
		];
		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1443829370);
		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfLocalizationModeIsSetToSelectAndChildIsNotLocalizable() {
		$input = [
			'defaultLanguageRow' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'behaviour' => [
								'localizationMode' => 'select',
							]
						],
					],
				],
			],
		];
		// not $globals definition for child here -> not localizable
		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1443944274);
		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataKeepsLocalizationModeSelectIfChildIsLocalizable() {
		$input = [
			'defaultLanguageRow' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'behaviour' => [
								'localizationMode' => 'select',
							]
						],
					],
				],
			],
		];
		$GLOBALS['TCA']['aForeignTableName']['ctrl'] = [
			'languageField' => 'theLanguageField',
			'transOrigPointerField' => 'theTransOrigPointerField',
		];
		$expected = $input;
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['behaviour']['localizationMode'] = 'select';
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataKeepsLocalizationModeKeep() {
		$input = [
			'defaultLanguageRow' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'behaviour' => [
								'localizationMode' => 'keep',
							]
						],
					],
				],
			],
		];
		$expected = $input;
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['behaviour']['localizationMode'] = 'keep';
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsLocalizationModeToNoneIfNotSetAndChildIsNotLocalizable() {
		$input = [
			'defaultLanguageRow' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
						],
					],
				],
			],
		];
		$expected = $input;
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['behaviour']['localizationMode'] = 'none';
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsLocalizationModeToSelectIfNotSetAndChildIsLocalizable() {
		$input = [
			'defaultLanguageRow' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
						],
					],
				],
			],
		];
		$GLOBALS['TCA']['aForeignTableName']['ctrl'] = [
			'languageField' => 'theLanguageField',
			'transOrigPointerField' => 'theTransOrigPointerField',
		];
		$expected = $input;
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['behaviour']['localizationMode'] = 'select';
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataMergesWithGivenAppearanceSettings() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'appearance' => [
								'levelLinksPosition' => 'both',
								'enabledControls' => [
									'dragdrop' => FALSE,
								],
							],
						],
					],
				],
			],
		];
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['appearance']['levelLinksPosition'] = 'both';
		$expected['processedTca']['columns']['aField']['config']['appearance']['enabledControls']['dragdrop'] = FALSE;
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataForcesLevelLinksPositionWithForeignSelector() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'foreign_selector' => 'foo',
							'appearance' => [
								'levelLinksPosition' => 'both',
							],
						],
					],
				],
			],
		];
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['foreign_selector'] = 'foo';
		$expected['processedTca']['columns']['aField']['config']['appearance']['levelLinksPosition'] = 'none';
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataKeepsLevelLinksPositionWithForeignSelectorAndUseCombination() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'foreign_selector' => 'foo',
							'appearance' => [
								'useCombination' => TRUE,
								'levelLinksPosition' => 'both',
							],
						],
					],
				],
			],
		];
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['foreign_selector'] = 'foo';
		$expected['processedTca']['columns']['aField']['config']['appearance']['useCombination'] = TRUE;
		$expected['processedTca']['columns']['aField']['config']['appearance']['levelLinksPosition'] = 'both';
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataKeepsShowPossibleLocalizationRecordsButForcesBooleanTrue() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'appearance' => [
								'showPossibleLocalizationRecords' => '1',
							],
						],
					],
				],
			],
		];
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['appearance']['showPossibleLocalizationRecords'] = TRUE;
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataKeepsShowPossibleLocalizationRecordsButForcesBooleanFalse() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'appearance' => [
								'showPossibleLocalizationRecords' => 0,
							],
						],
					],
				],
			],
		];
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['appearance']['showPossibleLocalizationRecords'] = FALSE;
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataKeepshowRemovedLocalizationRecordsButForcesBooleanTrue() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'appearance' => [
								'showRemovedLocalizationRecords' => 1,
							],
						],
					],
				],
			],
		];
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['appearance']['showRemovedLocalizationRecords'] = TRUE;
		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataKeepsShowRemovedLocalizationRecordsButForcesBooleanFalse() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'inline',
							'foreign_table' => 'aForeignTableName',
							'appearance' => [
								'showRemovedLocalizationRecords' => '',
							],
						],
					],
				],
			],
		];
		$expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
		$expected['processedTca']['columns']['aField']['config']['appearance']['showRemovedLocalizationRecords'] = FALSE;
		$this->assertEquals($expected, $this->subject->addData($input));
	}

}
