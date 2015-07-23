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
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew;

/**
 * Test case
 */
class DatabaseRowInitializeNewTest extends UnitTestCase {

	/**
	 * @var DatabaseRowInitializeNew
	 */
	protected $subject;

	public function setUp() {
		$this->subject = new DatabaseRowInitializeNew();
	}

	/**
	 * @test
	 */
	public function addDataReturnSameDataIfCommandIsEdit() {
		$input = [
			'command' => 'edit',
			'tableName' => 'aTable',
			'vanillaUid' => 23,
			'databaseRow' => [
				'uid' => 42,
			],
			'userTsConfig' => [
				'TCAdefaults.' => [
					'aTable.' => [
						'uid' => 23,
					],
				],
			],
		];
		$this->assertSame($input, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsDefaultDataFormUserTsIfColumnIsDenfinedInTca() {
		$input = [
			'command' => 'new',
			'tableName' => 'aTable',
			'vanillaUid' => 23,
			'databaseRow' => [],
			'userTsConfig' => [
				'TCAdefaults.' => [
					'aTable.' => [
						'aField' => 'userTsValue',
					],
				],
			],
			'vanillaTableTca' => [
				'columns' => [
					'aField' => [],
				],
			]
		];
		$expected = [
			'aField' => 'userTsValue',
			'pid' => 23,
		];
		$result = $this->subject->addData($input);
		$this->assertSame($expected, $result['databaseRow']);
	}

	/**
	 * @test
	 */
	public function addDataDoesNotSetDefaultDataFormUserTsIfColumnIsMissingInTca() {
		$input = [
			'command' => 'new',
			'tableName' => 'aTable',
			'vanillaUid' => 23,
			'databaseRow' => [],
			'userTsConfig' => [
				'TCAdefaults.' => [
					'aTable.' => [
						'aField' => 'userTsValue',
					],
				],
			],
			'vanillaTableTca' => [
				'columns' => [],
			]
		];
		$expected = [
			'pid' => 23,
		];
		$result = $this->subject->addData($input);
		$this->assertSame($expected, $result['databaseRow']);
	}

	/**
	 * @test
	 */
	public function addDataSetsDefaultDataFormPageTsIfColumnIsDenfinedInTca() {
		$input = [
			'command' => 'new',
			'tableName' => 'aTable',
			'vanillaUid' => 23,
			'databaseRow' => [],
			'pageTsConfig' => [
				'TCAdefaults.' => [
					'aTable.' => [
						'aField' => 'pageTsValue',
					],
				],
			],
			'vanillaTableTca' => [
				'columns' => [
					'aField' => [],
				],
			]
		];
		$expected = [
			'aField' => 'pageTsValue',
			'pid' => 23,
		];
		$result = $this->subject->addData($input);
		$this->assertSame($expected, $result['databaseRow']);
	}

	/**
	 * @test
	 */
	public function addDataDoesNotSetDefaultDataFormPageTsIfColumnIsMissingInTca() {
		$input = [
			'command' => 'new',
			'tableName' => 'aTable',
			'vanillaUid' => 23,
			'databaseRow' => [],
			'pageTsConfig' => [
				'TCAdefaults.' => [
					'aTable.' => [
						'aField' => 'pageTsValue',
					],
				],
			],
			'vanillaTableTca' => [
				'columns' => [],
			]
		];
		$expected = [
			'pid' => 23,
		];
		$result = $this->subject->addData($input);
		$this->assertSame($expected, $result['databaseRow']);
	}

	/**
	 * @test
	 */
	public function addDataSetsDefaultDataFormGetIfColumnIsDenfinedInTca() {
		$input = [
			'command' => 'new',
			'tableName' => 'aTable',
			'vanillaUid' => 23,
			'databaseRow' => [],
			'vanillaTableTca' => [
				'columns' => [
					'aField' => [],
				],
			]
		];
		$GLOBALS['_GET'] = [
			'defVals' => [
				'aTable' => [
					'aField' => 'getValue',
				],
			],
		];
		$expected = [
			'aField' => 'getValue',
			'pid' => 23,
		];
		$result = $this->subject->addData($input);
		$this->assertSame($expected, $result['databaseRow']);
	}

	/**
	 * @test
	 */
	public function addDataSetsDefaultDataFormPostIfColumnIsDenfinedInTca() {
		$input = [
			'command' => 'new',
			'tableName' => 'aTable',
			'vanillaUid' => 23,
			'databaseRow' => [],
			'vanillaTableTca' => [
				'columns' => [
					'aField' => [],
				],
			]
		];
		$GLOBALS['_POST'] = [
			'defVals' => [
				'aTable' => [
					'aField' => 'postValue',
				],
			],
		];
		$expected = [
			'aField' => 'postValue',
			'pid' => 23,
		];
		$result = $this->subject->addData($input);
		$this->assertSame($expected, $result['databaseRow']);
	}

	/**
	 * @test
	 */
	public function addDataSetsPrioritizesDefaultPostOverDefaultGet() {
		$input = [
			'command' => 'new',
			'tableName' => 'aTable',
			'vanillaUid' => 23,
			'databaseRow' => [],
			'vanillaTableTca' => [
				'columns' => [
					'aField' => [],
				],
			]
		];
		$GLOBALS['_GET'] = [
			'defVals' => [
				'aTable' => [
					'aField' => 'getValue',
				],
			],
		];
		$GLOBALS['_POST'] = [
			'defVals' => [
				'aTable' => [
					'aField' => 'postValue',
				],
			],
		];
		$expected = [
			'aField' => 'postValue',
			'pid' => 23,
		];
		$result = $this->subject->addData($input);
		$this->assertSame($expected, $result['databaseRow']);
	}

	/**
	 * @test
	 */
	public function addDataDoesNotSetDefaultDataFormGetPostIfColumnIsMissingInTca() {
		$input = [
			'command' => 'new',
			'tableName' => 'aTable',
			'vanillaUid' => 23,
			'databaseRow' => [],
			'userTsConfig' => [
				'TCAdefaults.' => [
					'aTable.' => [
						'aField' => 'pageTsValue',
					],
				],
			],
			'vanillaTableTca' => [
				'columns' => [],
			]
		];
		$GLOBALS['_GET'] = [
			'defVals' => [
				'aTable' => [
					'aField' => 'getValue',
				],
			],
		];
		$GLOBALS['_POST'] = [
			'defVals' => [
				'aTable' => [
					'aField' => 'postValue',
				],
			],
		];
		$expected = [
			'pid' => 23,
		];
		$result = $this->subject->addData($input);
		$this->assertSame($expected, $result['databaseRow']);
	}

	/**
	 * @test
	 */
	public function addDataSetsDefaultFromNeighborRow() {
		$input = [
			'command' => 'new',
			'tableName' => 'aTable',
			'vanillaUid' => 23,
			'databaseRow' => [],
			'neighborRow' => [
				'aField' => 'valueFromNeighbor',
			],
			'vanillaTableTca' => [
				'ctrl' => [
					'useColumnsForDefaultValues' => 'aField',
				],
				'columns' => [
					'aField' => [],
				],
			],
		];
		$expected = [
			'aField' => 'valueFromNeighbor',
			'pid' => 23,
		];
		$result = $this->subject->addData($input);
		$this->assertSame($expected, $result['databaseRow']);
	}

	/**
	 * @test
	 */
	public function addDataSetsDoesNotOverrideDefaultFromNeighborRowIfOtherDefaultHasSetDataAlready() {
		$input = [
			'command' => 'new',
			'tableName' => 'aTable',
			'vanillaUid' => 23,
			'databaseRow' => [],
			'neighborRow' => [
				'aField' => 'valueFromNeighbor',
			],
			'vanillaTableTca' => [
				'ctrl' => [
					'useColumnsForDefaultValues' => 'aField',
				],
				'columns' => [
					'aField' => [],
				],
			],
		];
		$GLOBALS['_POST'] = [
			'defVals' => [
				'aTable' => [
					'aField' => 'postValue',
				],
			],
		];
		$expected = [
			'aField' => 'postValue',
			'pid' => 23,
		];
		$result = $this->subject->addData($input);
		$this->assertSame($expected, $result['databaseRow']);
	}

	/**
	 * @test
	 */
	public function addDataSetsPidToVanillaUid() {
		$input = [
			'command' => 'new',
			'tableName' => 'aTable',
			'vanillaUid' => 23,
			'databaseRow' => [],
		];
		$expected['pid'] = 23;
		$result = $this->subject->addData($input);
		$this->assertSame($expected, $result['databaseRow']);
	}

}
