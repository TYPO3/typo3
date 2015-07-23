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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectValues;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class TcaSelectValuesTest extends UnitTestCase {

	/**
	 * @var TcaSelectValues
	 */
	protected $subject;

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	public function setUp() {
		$this->subject = new TcaSelectValues();
	}

	/**
	 * @test
	 */
	public function addDataSetsMmForeignRelationValues() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'uid' => 42,
				// Two connected rows
				'aField' => 2,
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'foreign_table' => 'foreignTable',
							'MM' => 'aTable_foreignTable_mm',
						],
					],
				],
			],
		];
		$fieldConfig = $input['processedTca']['columns']['aField']['config'];
		/** @var RelationHandler|ObjectProphecy $relationHandlerProphecy */
		$relationHandlerProphecy = $this->prophesize(RelationHandler::class);
		GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());

		$relationHandlerUids = [
			23,
			24
		];

		$relationHandlerProphecy->start(2, 'foreignTable', 'aTable_foreignTable_mm', 42, 'aTable', $fieldConfig)->shouldBeCalled();
		$relationHandlerProphecy->getValueArray()->shouldBeCalled()->willReturn($relationHandlerUids);

		$expected = $input;
		$expected['databaseRow']['aField'] = $relationHandlerUids;

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsForeignRelationValues() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'uid' => 42,
				// Two connected rows
				'aField' => '22,23,24,25',
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'foreign_table' => 'foreignTable',
						],
					],
				],
			],
		];
		$fieldConfig = $input['processedTca']['columns']['aField']['config'];
		/** @var RelationHandler|ObjectProphecy $relationHandlerProphecy */
		$relationHandlerProphecy = $this->prophesize(RelationHandler::class);
		GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());

		$relationHandlerUids = [
			23,
			24
		];

		$relationHandlerProphecy->start('22,23,24,25', 'foreignTable', '', 42, 'aTable', $fieldConfig)->shouldBeCalled();
		$relationHandlerProphecy->getValueArray()->shouldBeCalled()->willReturn($relationHandlerUids);

		$expected = $input;
		$expected['databaseRow']['aField'] = $relationHandlerUids;

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsValues() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'uid' => 42,
				// Two connected rows
				'aField' => 'foo,bar',
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['databaseRow']['aField'] = [
			'foo',
			'bar'
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

}
