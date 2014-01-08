<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\InlineRelationalRecordEditing;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Oliver Hader <oliver@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(dirname(__FILE__). '/AbstractTestCase.php');

/**
 * Test case for m:n MM relations
 *
 */
class MToNMMTest extends AbstractTestCase {
	const TABLE_Hotel = 'tx_irretutorial_mnmmasym_hotel';
	const TABLE_Offer = 'tx_irretutorial_mnmmasym_offer';
	const TABLE_Price = 'tx_irretutorial_mnmmasym_price';
	const TABLE_Relation_Hotel_Offer = 'tx_irretutorial_mnmmasym_hotel_offer_rel';
	const TABLE_Relation_Offer_Price = 'tx_irretutorial_mnmmasym_offer_price_rel';

	const FIELD_Hotel_Offers = 'offers';
	const FIELD_Offer_Hotels = 'hotels';
	const FIELD_Offer_Prices = 'prices';
	const FIELD_Price_Offers = 'offers';

	/**
	 * @var array
	 */
	protected $structure = array(
		self::TABLE_Hotel => array(self::FIELD_Hotel_Offers),
		self::TABLE_Offer => array(self::FIELD_Offer_Hotels, self::FIELD_Offer_Prices),
		self::TABLE_Price => array(self::FIELD_Price_Offers),
	);

	/**
	 * Sets up this test case.
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/DataHandling/InlineRelationalRecordEditing/Fixtures/MToNMMAsymmetric.xml');
	}

	/**
	 * @test
	 */
	public function isManyToManyRelationUpdatedForVersionedRecordsOnLocalSide() {
		$this->markTestSkipped('This test is failing - the Core needs to be fixed');
		$editingElements = array(
			self::TABLE_Hotel => 1,
		);
		$tceMain = $this->simulateEditing($editingElements);
		$versionedHotelId = $tceMain->getAutoVersionId(self::TABLE_Hotel, 1);

		$this->assertGreaterThan(1, $versionedHotelId);
		$this->assertArrayHasValues(
			array(
				$versionedHotelId . '->' . '1',
				$versionedHotelId . '->' . '2',
			),
			$this->getManyToManyRelations(self::TABLE_Relation_Hotel_Offer)
		);
	}

	/**
	 * @test
	 */
	public function isManyToManyRelationUpdatedForVersionedRecordsOnForeignSide() {
		$this->markTestSkipped('This test is failing - the Core needs to be fixed');
		$editingElements = array(
			self::TABLE_Offer => 1,
		);

		$tceMain = $this->simulateEditing($editingElements);
		$versionedOfferId = $tceMain->getAutoVersionId(self::TABLE_Offer, 1);

		$this->assertGreaterThan(1, $versionedOfferId);
		$this->assertArrayHasValues(
			array(
				'1' . '->' . $versionedOfferId,
				'1' . '->' . '2',
			),
			$this->getManyToManyRelations(self::TABLE_Relation_Hotel_Offer)
		);
	}

	/**
	 * @test
	 */
	public function isManyToManyRelationUpdatedForVersionedRecordsOnBothSides() {
		$this->markTestSkipped('This test is failing - the Core needs to be fixed');
		$editingElements = array(
			self::TABLE_Hotel => 1,
			self::TABLE_Offer => 1,
		);

		$tceMain = $this->simulateEditing($editingElements);
		$versionedHotelId = $tceMain->getAutoVersionId(self::TABLE_Hotel, 1);
		$versionedOfferId = $tceMain->getAutoVersionId(self::TABLE_Offer, 1);

		$this->assertGreaterThan(1, $versionedHotelId);
		$this->assertGreaterThan(2, $versionedOfferId);
		$this->assertArrayHasValues(
			array(
				$versionedHotelId . '->' . $versionedOfferId,
				$versionedHotelId . '->' . '2',
			),
			$this->getManyToManyRelations(self::TABLE_Relation_Hotel_Offer)
		);
	}

	/**
	 * @test
	 */
	public function isManyToManyRelationUpdatedForVersionedRecordsOnBothSidesWithDifferentRelations() {
		$this->markTestSkipped('This test is failing - the Core needs to be fixed');

		$editingElements = array(
			self::TABLE_Hotel => 1,
			self::TABLE_Offer => 1,
		);

		$modificationStructure = array(
			self::TABLE_Hotel => array(
				1 => array(
					self::FIELD_Hotel_Offers => '1',
				),
			),
		);

		$tceMain = $this->simulateEditing($editingElements);
		$versionedHotelId = $tceMain->getAutoVersionId(self::TABLE_Hotel, 1);
		$versionedOfferId = $tceMain->getAutoVersionId(self::TABLE_Offer, 1);

		$tceMain = $this->simulateEditingByStructure($modificationStructure);
		$relations = $this->getManyToManyRelations(self::TABLE_Relation_Hotel_Offer);

		$this->assertGreaterThan(1, $versionedHotelId);
		$this->assertGreaterThan(2, $versionedOfferId);
		$this->assertArrayHasValues(
			array(
				$versionedHotelId . '->' . $versionedOfferId,
			),
			$relations
		);

		$this->assertArrayDoesNotHaveValues(
			array(
				$versionedHotelId . '->' . '2',
			),
			$relations
		);
	}

	/**
	 * @param string $tableName
	 * @return array
	 */
	protected function getManyToManyRelations($tableName) {
		$relations = array();

		foreach ($this->getAllRecords($tableName) as $relation) {
			$relations[] = $relation['uid_local'] . '->' . $relation['uid_foreign'];
		}

		return $relations;
	}

	/**
	 * @param array $expected
	 * @param array $actual
	 */
	protected function assertArrayHasValues(array $expected, array $actual) {
		$differences = array_diff($expected, $actual);

		if (count($differences) > 0) {
			$this->fail('Unmatched values: ' . implode(', ', $differences));
		}
	}

	/**
	 * @param array $unexpected
	 * @param array $actual
	 */
	protected function assertArrayDoesNotHaveValues(array $unexpected, array $actual) {
		$intersection = array_intersect($unexpected, $actual);

		if (count($intersection) > 0) {
			$this->fail('Unexpected values: ' . implode(', ', $intersection));
		}
	}
}
