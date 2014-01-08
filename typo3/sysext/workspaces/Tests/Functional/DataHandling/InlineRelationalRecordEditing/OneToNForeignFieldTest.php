<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\InlineRelationalRecordEditing;

/***************************************************************
*  Copyright notice
*
*  (c) 2010 Oliver Hader <oliver@typo3.org>
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

require_once(dirname(__FILE__). '/AbstractOneToNTestCase.php');

/**
 * Test case for 1:n ff relations.
 *
 */
class OneToNForeignFieldTest extends AbstractOneToNTestCase {
	const TABLE_Hotel = 'tx_irretutorial_1nff_hotel';
	const TABLE_Offer = 'tx_irretutorial_1nff_offer';
	const TABLE_Price = 'tx_irretutorial_1nff_price';

	const FIELD_Pages_Hotels = 'tx_irretutorial_hotels';

	const FIELD_Hotels_ParentId = 'parentid';
	const FIELD_Offers_ParentId = 'parentid';
	const FIELD_Prices_ParentId = 'parentid';
	const FIELD_Hotels_ParentTable = 'parenttable';
	const FIELD_Offers_ParentTable = 'parenttable';
	const FIELD_Prices_ParentTable = 'parenttable';

	/**
	 * Sets up this test case.
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->importDataSet(dirname(__FILE__) . '/Fixtures/OneToNForeignField.xml');
	}

	/**
	 * @param boolean $value
	 */
	protected function setLocalizeChildrenAtParentLocalization($value) {
		$this->setTcaFieldConfigurationBehaviour(
			self::TABLE_Hotel,
			self::FIELD_Hotel_Offers,
			self::BEHAVIOUR_LocalizeChildrenAtParentLocalization,
			(bool) $value
		);

		$this->setTcaFieldConfigurationBehaviour(
			self::TABLE_Offer,
			self::FIELD_Offers_Prices,
			self::BEHAVIOUR_LocalizeChildrenAtParentLocalization,
			(bool) $value
		);
	}

	/****************************************************************
	 * CREATE Behaviour
	 ****************************************************************/

	/**
	 * @param boolean $returnIds
	 * @return NULL|array
	 * @test
	 */
	public function versionRecordsAndPlaceholdersAreCreated($returnIds = FALSE) {
		$this->markTestSkipped('This test is failing - the Core needs to be fixed');

		$newHotelId = uniqid('NEW');
		$newOfferId = uniqid('NEW');
		$newPriceId = uniqid('NEW');

		$tceMain = $this->simulateEditingByStructure(
			array(
				self::TABLE_Hotel => array(
					$newHotelId => array(
						'pid' => self::VALUE_Pid,
						'title' => 'HOTEL',
						self::FIELD_Hotel_Offers => $newOfferId,
					),
				),
				self::TABLE_Offer => array(
					$newOfferId => array(
						'pid' => self::VALUE_Pid,
						'title' => 'OFFER',
						self::FIELD_Offers_Prices => $newPriceId,
					),
				),
				self::TABLE_Price => array(
					$newPriceId => array(
						'pid' => self::VALUE_Pid,
						'title' => 'PRICE',
					),
				),
			)
		);

		$placeholderHotelId = $tceMain->substNEWwithIDs[$newHotelId];
		$placeholderOfferId = $tceMain->substNEWwithIDs[$newOfferId];
		$placeholderPriceId = $tceMain->substNEWwithIDs[$newPriceId];

		$versionizedHotelId = $tceMain->getAutoVersionId(self::TABLE_Hotel, $placeholderHotelId);
		$versionizedOfferId = $tceMain->getAutoVersionId(self::TABLE_Offer, $placeholderOfferId);
		$versionizedPriceId = $tceMain->getAutoVersionId(self::TABLE_Price, $placeholderPriceId);

		// Skip assertions if requested
		if ($returnIds === TRUE) {
			return array(
				'placeholderHotelId' => $placeholderHotelId,
				'placeholderOfferId' => $placeholderOfferId,
				'placeholderPriceId' => $placeholderPriceId,
			);
		}

		/**
		 * Placeholder (Live)
		 */

		$this->assertRecords(
			array(
				self::TABLE_Hotel => array(
					$placeholderHotelId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
					),
					$versionizedHotelId => array(
						'pid' => -1,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => -1,
						self::FIELD_Hotel_Offers => 1,
					),
				),
				self::TABLE_Offer => array(
					$placeholderOfferId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
						self::FIELD_Offers_ParentId => $versionizedHotelId,
						self::FIELD_Offers_ParentTable => self::TABLE_Hotel,
					),
				),
				self::TABLE_Price => array(
					$placeholderPriceId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
						self::FIELD_Prices_ParentId => $versionizedOfferId,
						self::FIELD_Prices_ParentTable => self::TABLE_Offer,
					),
				),
			)
		);

		/**
		 * Workspace (Version)
		 */

		$this->assertChildren(
			self::TABLE_Hotel, $versionizedHotelId, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => $versionizedOfferId,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $placeholderOfferId,
					self::FIELD_Offers_ParentId => $versionizedHotelId,
					self::FIELD_Offers_ParentTable => self::TABLE_Hotel,
				),
			)
		);

		$this->assertChildren(
			self::TABLE_Offer, $versionizedOfferId, self::FIELD_Offers_Prices,
			array(
				array(
					'tableName' => self::TABLE_Price,
					'uid' => $versionizedPriceId,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $placeholderPriceId,
					self::FIELD_Prices_ParentId => $versionizedOfferId,
					self::FIELD_Prices_ParentTable => self::TABLE_Offer,
				),
			)
		);

		$this->assertReferenceIndex(
			array(
				$this->combine(self::TABLE_Hotel, $versionizedHotelId, 'offers') => array(
					$this->combine(self::TABLE_Offer, $versionizedOfferId),
				),
				$this->combine(self::TABLE_Offer, $versionizedOfferId, 'prices') => array(
					$this->combine(self::TABLE_Price, $versionizedPriceId),
				),
			)
		);

		return NULL;
	}

	/**
	 * @test
	 */
	public function versionRecordsAndPlaceholdersAreCreatedAndCopied() {
		$this->markTestSkipped('This test is failing - the Core needs to be fixed');

		$originalIds = $this->versionRecordsAndPlaceholdersAreCreated(TRUE);
		$originalPlaceholderHotelId = $originalIds['placeholderHotelId'];

		$tceMain = $this->simulateCommand(
			self::COMMAND_Copy,
			-$originalPlaceholderHotelId,
			array(
				self::TABLE_Hotel => $originalPlaceholderHotelId
			)
		);

		$placeholderHotelId = $tceMain->copyMappingArray_merged[self::TABLE_Hotel][$originalPlaceholderHotelId];
		$versionizedHotelId = $tceMain->getAutoVersionId(self::TABLE_Hotel, $placeholderHotelId);

		$this->assertGreaterThan($placeholderHotelId, $versionizedHotelId);

		$placeholderOfferId = current($tceMain->copyMappingArray_merged[self::TABLE_Offer]);
		$placeholderPriceId = current($tceMain->copyMappingArray_merged[self::TABLE_Price]);

		$this->assertGreaterThan(0, $placeholderOfferId, 'Seems like child reference have not been considered');
		$this->assertGreaterThan(0, $placeholderPriceId, 'Seems like child reference have not been considered');

		$versionizedOfferId = $tceMain->getAutoVersionId(self::TABLE_Offer, $placeholderOfferId);
		$versionizedPriceId = $tceMain->getAutoVersionId(self::TABLE_Price, $placeholderPriceId);

		/**
		 * Placeholder (Live)
		 */

		$this->assertRecords(
			array(
				self::TABLE_Hotel => array(
					$placeholderHotelId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
					),
					$versionizedHotelId => array(
						'pid' => -1,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => -1,
						self::FIELD_Hotel_Offers => 1,
					),
				),
				self::TABLE_Offer => array(
					$placeholderOfferId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
					),
				),
				self::TABLE_Price => array(
					$placeholderPriceId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
					),
				),
			)
		);

		/**
		 * Workspace (Version)
		 */

		$this->assertChildren(
			self::TABLE_Hotel, $versionizedHotelId, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => $versionizedOfferId,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $placeholderOfferId,
					self::FIELD_Offers_ParentId => $versionizedHotelId,
					self::FIELD_Offers_ParentTable => self::TABLE_Hotel,
				),
			)
		);

		$this->assertChildren(
			self::TABLE_Offer, $versionizedOfferId, self::FIELD_Offers_Prices,
			array(
				array(
					'tableName' => self::TABLE_Price,
					'uid' => $versionizedPriceId,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $placeholderPriceId,
					self::FIELD_Prices_ParentId => $versionizedOfferId,
					self::FIELD_Prices_ParentTable => self::TABLE_Offer,
				),
			)
		);

		$this->assertReferenceIndex(
			array(
				$this->combine(self::TABLE_Hotel, $versionizedHotelId, 'offers') => array(
					$this->combine(self::TABLE_Offer, $versionizedOfferId),
				),
				$this->combine(self::TABLE_Offer, $versionizedOfferId, 'prices') => array(
					$this->combine(self::TABLE_Price, $versionizedPriceId),
				),
			)
		);
	}

	/**
	 * @param boolean $returnIds
	 * @return NULL|array
	 * @test
	 */
	public function versionRecordsAndPlaceholdersAreCreatedAndLocalized($returnIds = FALSE) {
		$this->markTestSkipped('This test is failing - the Core needs to be fixed');

		$this->setLocalizeChildrenAtParentLocalization(TRUE);
		$originalIds = $this->versionRecordsAndPlaceholdersAreCreated(TRUE);
		$originalPlaceholderHotelId = $originalIds['placeholderHotelId'];
		$originalPlaceholderOfferId = $originalIds['placeholderOfferId'];
		$originalPlaceholderPriceId = $originalIds['placeholderPriceId'];

		$tceMain = $this->simulateCommand(
			self::COMMAND_Localize,
			self::VALUE_LanguageId,
			array(
				self::TABLE_Hotel => $originalPlaceholderHotelId
			)
		);

		$placeholderHotelId = $tceMain->copyMappingArray_merged[self::TABLE_Hotel][$originalPlaceholderHotelId];
		$versionizedHotelId = $tceMain->getAutoVersionId(self::TABLE_Hotel, $placeholderHotelId);

		$this->assertGreaterThan($placeholderHotelId, $versionizedHotelId);

		$placeholderOfferId = current($tceMain->copyMappingArray_merged[self::TABLE_Offer]);
		$placeholderPriceId = current($tceMain->copyMappingArray_merged[self::TABLE_Price]);

		$this->assertGreaterThan(0, $placeholderOfferId, 'Seems like child reference have not been considered');
		$this->assertGreaterThan(0, $placeholderPriceId, 'Seems like child reference have not been considered');

		$versionizedOfferId = $tceMain->getAutoVersionId(self::TABLE_Offer, $placeholderOfferId);
		$versionizedPriceId = $tceMain->getAutoVersionId(self::TABLE_Price, $placeholderPriceId);

		// Skip assertions if requested
		if ($returnIds === TRUE) {
			return array(
				'originalPlaceholderHotelId' => $originalPlaceholderHotelId,
				'originalPlaceholderOfferId' => $originalPlaceholderOfferId,
				'originalPlaceholderPriceId' => $originalPlaceholderPriceId,
				'placeholderHotelId' => $placeholderHotelId,
				'placeholderOfferId' => $placeholderOfferId,
				'placeholderPriceId' => $placeholderPriceId,
				'versionizedHotelId' => $versionizedHotelId,
				'versionizedOfferId' => $versionizedOfferId,
				'versionizedPriceId' => $versionizedPriceId,
			);
		}

		/**
		 * Placeholder (Live)
		 */

		$this->assertRecords(
			array(
				self::TABLE_Hotel => array(
					$placeholderHotelId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
						'l18n_parent' => $originalPlaceholderHotelId,
						'sys_language_uid' => self::VALUE_LanguageId,
					),
					$versionizedHotelId => array(
						'pid' => -1,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => -1,
						'l18n_parent' => $originalPlaceholderHotelId,
						'sys_language_uid' => self::VALUE_LanguageId,
						self::FIELD_Hotel_Offers => 1,
					),
				),
				self::TABLE_Offer => array(
					$placeholderOfferId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
						'l18n_parent' => $originalPlaceholderOfferId,
						'sys_language_uid' => self::VALUE_LanguageId,
					),
				),
				self::TABLE_Price => array(
					$placeholderPriceId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
						'l18n_parent' => $originalPlaceholderPriceId,
						'sys_language_uid' => self::VALUE_LanguageId,
					),
				),
			)
		);

		/**
		 * Workspace (Version)
		 */

		$this->assertChildren(
			self::TABLE_Hotel, $versionizedHotelId, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => $versionizedOfferId,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $placeholderOfferId,
					'l18n_parent' => $originalPlaceholderOfferId,
					'sys_language_uid' => self::VALUE_LanguageId,
					self::FIELD_Offers_ParentId => $versionizedHotelId,
					self::FIELD_Offers_ParentTable => self::TABLE_Hotel,
				),
			)
		);

		$this->assertChildren(
			self::TABLE_Offer, $versionizedOfferId, self::FIELD_Offers_Prices,
			array(
				array(
					'tableName' => self::TABLE_Price,
					'uid' => $versionizedPriceId,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $placeholderPriceId,
					'l18n_parent' => $originalPlaceholderPriceId,
					'sys_language_uid' => self::VALUE_LanguageId,
					self::FIELD_Prices_ParentId => $versionizedOfferId,
					self::FIELD_Prices_ParentTable => self::TABLE_Offer,
				),
			)
		);

		$this->assertReferenceIndex(
			array(
				$this->combine(self::TABLE_Hotel, $versionizedHotelId, 'offers') => array(
					$this->combine(self::TABLE_Offer, $versionizedOfferId),
				),
				$this->combine(self::TABLE_Offer, $versionizedOfferId, 'prices') => array(
					$this->combine(self::TABLE_Price, $versionizedPriceId),
				),
			)
		);
	}

	/**
	 * @test
	 */
	public function versionRecordsAndPlaceholdersAreCreatedAndLocalizedAndCopiedAfter() {
		$this->markTestSkipped('This test is failing - the Core needs to be fixed');

		$originalIds = $this->versionRecordsAndPlaceholdersAreCreatedAndLocalized(TRUE);

		// Created record placeholders
		$originalOriginalPlaceholderHotelId = $originalIds['originalPlaceholderHotelId'];
		$originalOriginalPlaceholderOfferId = $originalIds['originalPlaceholderOfferId'];
		$originalOriginalPlaceholderPriceId = $originalIds['originalPlaceholderPriceId'];

		// Localized record placeholders
		$originalPlaceholderHotelId = $originalIds['placeholderHotelId'];
		$originalPlaceholderOfferId = $originalIds['placeholderOfferId'];
		$originalPlaceholderPriceId = $originalIds['placeholderPriceId'];

		// Localized versioned placeholders
		$originalVersionizedHotelId = $originalIds['versionizedHotelId'];
		$originalVersionizedOfferId = $originalIds['versionizedOfferId'];
		$originalVersionizedPriceId = $originalIds['versionizedPriceId'];

		$tceMain = $this->simulateCommand(
			self::COMMAND_Copy,
			-$originalOriginalPlaceholderHotelId,
			array(
				self::TABLE_Hotel => $originalOriginalPlaceholderHotelId
			)
		);

		$defaultPlaceholderHotelId = $tceMain->copyMappingArray_merged[self::TABLE_Hotel][$originalOriginalPlaceholderHotelId];
		$defaultVersionizedHotelId = $tceMain->getAutoVersionId(self::TABLE_Hotel, $defaultPlaceholderHotelId);
		$localizedPlaceholderHotelId = $tceMain->copyMappingArray_merged[self::TABLE_Hotel][$originalPlaceholderHotelId];
		$localizedVersionizedHotelId = $tceMain->getAutoVersionId(self::TABLE_Hotel, $localizedPlaceholderHotelId);

		$this->assertGreaterThan($defaultPlaceholderHotelId, $defaultVersionizedHotelId);
		$this->assertGreaterThan($localizedPlaceholderHotelId, $localizedVersionizedHotelId);

		$defaultPlaceholderOfferId = $tceMain->copyMappingArray_merged[self::TABLE_Offer][$originalOriginalPlaceholderOfferId];
		$defaultPlaceholderPriceId = $tceMain->copyMappingArray_merged[self::TABLE_Price][$originalOriginalPlaceholderPriceId];
		$localizedPlaceholderOfferId = $tceMain->copyMappingArray_merged[self::TABLE_Offer][$originalPlaceholderOfferId];
		$localizedPlaceholderPriceId = $tceMain->copyMappingArray_merged[self::TABLE_Price][$originalPlaceholderPriceId];

		$this->assertGreaterThan(0, $defaultPlaceholderOfferId, 'Seems like child reference have not been considered');
		$this->assertGreaterThan(0, $defaultPlaceholderPriceId, 'Seems like child reference have not been considered');
		$this->assertGreaterThan(0, $localizedPlaceholderOfferId, 'Seems like child reference have not been considered');
		$this->assertGreaterThan(0, $localizedPlaceholderPriceId, 'Seems like child reference have not been considered');

		$defaultVersionizedOfferId = $tceMain->getAutoVersionId(self::TABLE_Offer, $defaultPlaceholderOfferId);
		$defaultVersionizedPriceId = $tceMain->getAutoVersionId(self::TABLE_Price, $defaultPlaceholderPriceId);
		$localizedVersionizedOfferId = $tceMain->getAutoVersionId(self::TABLE_Offer, $localizedPlaceholderOfferId);
		$localizedVersionizedPriceId = $tceMain->getAutoVersionId(self::TABLE_Price, $localizedPlaceholderPriceId);

		/**
		 * Placeholder (Live)
		 */

		$this->assertRecords(
			array(
				self::TABLE_Hotel => array(
					$localizedPlaceholderHotelId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
						'l18n_parent' => $defaultPlaceholderHotelId,
						'sys_language_uid' => self::VALUE_LanguageId,
					),
					$localizedVersionizedHotelId => array(
						'pid' => -1,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => -1,
						'l18n_parent' => $defaultPlaceholderHotelId,
						'sys_language_uid' => self::VALUE_LanguageId,
						self::FIELD_Hotel_Offers => 1,
					),
				),
				self::TABLE_Offer => array(
					$localizedPlaceholderOfferId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
						'l18n_parent' => $defaultPlaceholderOfferId,
						'sys_language_uid' => self::VALUE_LanguageId,
					),
				),
				self::TABLE_Price => array(
					$localizedPlaceholderPriceId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
						'l18n_parent' => $defaultPlaceholderPriceId,
						'sys_language_uid' => self::VALUE_LanguageId,
					),
				),
			)
		);

		/**
		 * Workspace (Version)
		 */

		$this->assertChildren(
			self::TABLE_Hotel, $localizedVersionizedHotelId, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => $localizedVersionizedOfferId,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $localizedPlaceholderOfferId,
					'l18n_parent' => $defaultPlaceholderOfferId,
					'sys_language_uid' => self::VALUE_LanguageId,
					self::FIELD_Offers_ParentId => $localizedVersionizedHotelId,
					self::FIELD_Offers_ParentTable => self::TABLE_Hotel,
				),
			)
		);

		$this->assertChildren(
			self::TABLE_Offer, $localizedVersionizedOfferId, self::FIELD_Offers_Prices,
			array(
				array(
					'tableName' => self::TABLE_Price,
					'uid' => $localizedVersionizedPriceId,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $localizedPlaceholderPriceId,
					'l18n_parent' => $defaultPlaceholderPriceId,
					'sys_language_uid' => self::VALUE_LanguageId,
					self::FIELD_Prices_ParentId => $localizedVersionizedOfferId,
					self::FIELD_Prices_ParentTable => self::TABLE_Offer,
				),
			)
		);

		$this->assertReferenceIndex(
			array(
				$this->combine(self::TABLE_Hotel, $localizedVersionizedHotelId, 'offers') => array(
					$this->combine(self::TABLE_Offer, $localizedVersionizedOfferId),
				),
				$this->combine(self::TABLE_Offer, $localizedVersionizedOfferId, 'prices') => array(
					$this->combine(self::TABLE_Price, $localizedVersionizedPriceId),
				),
			)
		);
	}

	/**
	 * @test
	 */
	public function versionRecordsAndPlaceholdersAreCreatedAndLocalizedAndDeleted() {
		$this->markTestSkipped('This test is failing - the Core needs to be fixed');

		$originalIds = $this->versionRecordsAndPlaceholdersAreCreatedAndLocalized(TRUE);
		$originalPlaceholderHotelId = $originalIds['placeholderHotelId'];
		$originalPlaceholderOfferId = $originalIds['placeholderOfferId'];
		$originalPlaceholderPriceId = $originalIds['placeholderPriceId'];
		$originalVersionizedHotelId = $originalIds['versionizedHotelId'];
		$originalVersionizedOfferId = $originalIds['versionizedOfferId'];
		$originalVersionizedPriceId = $originalIds['versionizedPriceId'];

		$tceMain = $this->simulateCommand(
			self::COMMAND_Delete,
			TRUE,
			array(
				self::TABLE_Hotel => $originalPlaceholderHotelId
			)
		);

		/**
		 * Placeholder (Live)
		 */

		$this->assertRecords(
			array(
				self::TABLE_Hotel => array(
					$originalPlaceholderHotelId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => 0,
						't3ver_state' => 1,
						'deleted' => 1,
					),
					$originalVersionizedHotelId => array(
						'pid' => -1,
						't3ver_wsid' => 0,
						't3ver_state' => -1,
						'deleted' => 1,
						self::FIELD_Hotel_Offers => 1,
					),
				),
				self::TABLE_Offer => array(
					$originalPlaceholderOfferId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => 0,
						't3ver_state' => 1,
						'deleted' => 1,
					),
					$originalVersionizedOfferId => array(
						'pid' => -1,
						't3ver_wsid' => 0,
						't3ver_state' => -1,
						'deleted' => 1,
						self::FIELD_Offers_Prices => 1,
					),
				),
				self::TABLE_Price => array(
					$originalPlaceholderPriceId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => 0,
						't3ver_state' => 1,
						'deleted' => 1,
					),
					$originalVersionizedPriceId => array(
						'pid' => -1,
						't3ver_wsid' => 0,
						't3ver_state' => -1,
						'deleted' => 1,
					),
				),
			)
		);

		$this->assertReferenceIndex(
			array(
				$this->combine(self::TABLE_Hotel, $originalVersionizedHotelId, 'offers') => array(
					$this->combine(self::TABLE_Offer, $originalVersionizedOfferId),
				),
				$this->combine(self::TABLE_Offer, $originalVersionizedOfferId, 'prices') => array(
					$this->combine(self::TABLE_Price, $originalVersionizedPriceId),
				),
			),
			FALSE
		);
	}

	/**
	 * @test
	 */
	public function versionRecordsAndPlaceholdersAreCreatedAndLocalizedAndDeletedAndLocalized() {
		$this->markTestSkipped('This test is failing - the Core needs to be fixed');

		$this->setLocalizeChildrenAtParentLocalization(TRUE);
		$originalIds = $this->versionRecordsAndPlaceholdersAreCreated(TRUE);
		$originalPlaceholderHotelId = $originalIds['placeholderHotelId'];
		$originalPlaceholderOfferId = $originalIds['placeholderOfferId'];
		$originalPlaceholderPriceId = $originalIds['placeholderPriceId'];

		$tceMain = $this->simulateCommand(
			self::COMMAND_Localize,
			self::VALUE_LanguageId,
			array(
				self::TABLE_Hotel => $originalPlaceholderHotelId
			)
		);

		$localizedPlaceholderHotelId = $tceMain->copyMappingArray_merged[self::TABLE_Hotel][$originalPlaceholderHotelId];

		$this->simulateCommand(
			self::COMMAND_Delete,
			TRUE,
			array(
				self::TABLE_Hotel => $localizedPlaceholderHotelId
			)
		);

		$tceMain = $this->simulateCommand(
			self::COMMAND_Localize,
			self::VALUE_LanguageId,
			array(
				self::TABLE_Hotel => $originalPlaceholderHotelId
			)
		);

		$placeholderHotelId = $tceMain->copyMappingArray_merged[self::TABLE_Hotel][$originalPlaceholderHotelId];
		$versionizedHotelId = $tceMain->getAutoVersionId(self::TABLE_Hotel, $placeholderHotelId);

		$this->assertGreaterThan($placeholderHotelId, $versionizedHotelId);

		$placeholderOfferId = current($tceMain->copyMappingArray_merged[self::TABLE_Offer]);
		$placeholderPriceId = current($tceMain->copyMappingArray_merged[self::TABLE_Price]);

		$this->assertGreaterThan(0, $placeholderOfferId, 'Seems like child reference have not been considered');
		$this->assertGreaterThan(0, $placeholderPriceId, 'Seems like child reference have not been considered');

		$versionizedOfferId = $tceMain->getAutoVersionId(self::TABLE_Offer, $placeholderOfferId);
		$versionizedPriceId = $tceMain->getAutoVersionId(self::TABLE_Price, $placeholderPriceId);

		/**
		 * Placeholder (Live)
		 */

		$this->assertRecords(
			array(
				self::TABLE_Hotel => array(
					$placeholderHotelId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
						'l18n_parent' => $originalPlaceholderHotelId,
						'sys_language_uid' => self::VALUE_LanguageId,
					),
					$versionizedHotelId => array(
						'pid' => -1,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => -1,
						'l18n_parent' => $originalPlaceholderHotelId,
						'sys_language_uid' => self::VALUE_LanguageId,
						self::FIELD_Hotel_Offers => 1,
					),
				),
				self::TABLE_Offer => array(
					$placeholderOfferId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
						'l18n_parent' => $originalPlaceholderOfferId,
						'sys_language_uid' => self::VALUE_LanguageId,
					),
				),
				self::TABLE_Price => array(
					$placeholderPriceId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
						'l18n_parent' => $originalPlaceholderPriceId,
						'sys_language_uid' => self::VALUE_LanguageId,
					),
				),
			)
		);

		/**
		 * Workspace (Version)
		 */

		$this->assertChildren(
			self::TABLE_Hotel, $versionizedHotelId, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => $versionizedOfferId,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $placeholderOfferId,
					'l18n_parent' => $originalPlaceholderOfferId,
					'sys_language_uid' => self::VALUE_LanguageId,
					self::FIELD_Offers_ParentId => $versionizedHotelId,
					self::FIELD_Offers_ParentTable => self::TABLE_Hotel,
				),
			)
		);

		$this->assertChildren(
			self::TABLE_Offer, $versionizedOfferId, self::FIELD_Offers_Prices,
			array(
				array(
					'tableName' => self::TABLE_Price,
					'uid' => $versionizedPriceId,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $placeholderPriceId,
					'l18n_parent' => $originalPlaceholderPriceId,
					'sys_language_uid' => self::VALUE_LanguageId,
					self::FIELD_Prices_ParentId => $versionizedOfferId,
					self::FIELD_Prices_ParentTable => self::TABLE_Offer,
				),
			)
		);

		$this->assertReferenceIndex(
			array(
				$this->combine(self::TABLE_Hotel, $versionizedHotelId, 'offers') => array(
					$this->combine(self::TABLE_Offer, $versionizedOfferId),
				),
				$this->combine(self::TABLE_Offer, $versionizedOfferId, 'prices') => array(
					$this->combine(self::TABLE_Price, $versionizedPriceId),
				),
			)
		);
	}

	/****************************************************************
	 * EDIT Behaviour
	 ****************************************************************/

	/**
	 * @return void
	 * @test
	 */
	public function areAllChildrenVersionizedWithParent() {
		$liveElements = $this->versionizeAllChildrenWithParent();
		$this->assertWorkspaceVersions($liveElements);

		$versionizedHotelId = $this->getWorkspaceVersionId(self::TABLE_Hotel, 1);

		// Workspace:
		$this->assertChildren(
			self::TABLE_Hotel, $versionizedHotelId, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					't3ver_oid' => 1,
					't3_origuid' => 1,
					self::FIELD_Offers_ParentId => $versionizedHotelId,
					self::FIELD_Offers_ParentTable => self::TABLE_Hotel,
				),
				array(
					'tableName' => self::TABLE_Offer,
					't3ver_oid' => 2,
					't3_origuid' => 2,
					self::FIELD_Offers_ParentId => $versionizedHotelId,
					self::FIELD_Offers_ParentTable => self::TABLE_Hotel,
				),
			)
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function areExistingChildVersionsUsedOnParentVersioning() {
		$childElements = array(
			self::TABLE_Offer => '1',
		);

		$this->simulateEditing($childElements);
		$this->assertWorkspaceVersions($childElements);

			// Live:
		$this->assertChildren(
			self::TABLE_Hotel, 1, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => 1,
					't3ver_id' => 0,
					self::FIELD_Offers_ParentId => 1,
				),
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => 2,
					't3ver_id' => 0,
					self::FIELD_Offers_ParentId => 1,
				),
			)
		);

		$versionizedOfferId = $this->getWorkspaceVersionId(self::TABLE_Offer, 1);
		$versionizedPriceId = $this->getWorkspaceVersionId(self::TABLE_Price, 3);

		$liveElements = array(
			self::TABLE_Hotel => '1',
			self::TABLE_Offer => '2',
			self::TABLE_Price => '1,2',
		);
		$liveElementsToBeVersionized = $liveElements;
		$liveElementsToBeVersionized[self::TABLE_Offer] .= ',' . $versionizedOfferId;
		$liveElementsToBeVersionized[self::TABLE_Price] .= ',' . $versionizedPriceId;

		$this->simulateEditing($liveElementsToBeVersionized);
		$this->assertWorkspaceVersions($liveElements);

		$versionizedHotelId = $this->getWorkspaceVersionId(self::TABLE_Hotel, 1);

			// Workspace:
		$this->assertChildren(
			self::TABLE_Hotel, $versionizedHotelId, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					't3ver_oid' => 1,
					't3_origuid' => 1,
					't3ver_id' => 1,
					self::FIELD_Offers_ParentId => $versionizedHotelId,
				),
				array(
					'tableName' => self::TABLE_Offer,
					't3ver_oid' => 2,
					't3_origuid' => 2,
					't3ver_id' => 1,
					self::FIELD_Offers_ParentId => $versionizedHotelId,
				),
			)
		);
	}

	/****************************************************************
	 * COPY Behaviour
	 ****************************************************************/

	/**
	 * @test
	 */
	public function liveRecordsAreCopied() {
		$this->markTestSkipped('This test is failing - the Core needs to be fixed');

		$tceMain = $this->simulateCommand(
			self::COMMAND_Copy,
			-1,
			array(
				self::TABLE_Hotel => 1
			)
		);

		$placeholderHotelId = $tceMain->copyMappingArray_merged[self::TABLE_Hotel][1];
		$versionizedHotelId = $tceMain->getAutoVersionId(self::TABLE_Hotel, $placeholderHotelId);

		$this->assertGreaterThan($placeholderHotelId, $versionizedHotelId);

		$placeholderOfferId = $tceMain->copyMappingArray_merged[self::TABLE_Offer][1];
		$placeholderPriceId = $tceMain->copyMappingArray_merged[self::TABLE_Price][3];

		$this->assertGreaterThan(0, $placeholderOfferId, 'Seems like child reference have not been considered');
		$this->assertGreaterThan(0, $placeholderPriceId, 'Seems like child reference have not been considered');

		$versionizedOfferId = $tceMain->getAutoVersionId(self::TABLE_Offer, $placeholderOfferId);
		$versionizedPriceId = $tceMain->getAutoVersionId(self::TABLE_Price, $placeholderPriceId);

		/**
		 * Placeholder (Live)
		 */

		$this->assertRecords(
			array(
				self::TABLE_Hotel => array(
					$placeholderHotelId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
					),
					$versionizedHotelId => array(
						'pid' => -1,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => -1,
						self::FIELD_Hotel_Offers => 2,
					),
				),
				self::TABLE_Offer => array(
					$placeholderOfferId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
					),
				),
				self::TABLE_Price => array(
					$placeholderPriceId => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
					),
				),
			)
		);

		/**
		 * Workspace (Version)
		 */

		$this->assertChildren(
			self::TABLE_Hotel, $versionizedHotelId, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => $versionizedOfferId,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $placeholderOfferId,
					self::FIELD_Offers_ParentId => $versionizedHotelId,
					self::FIELD_Offers_ParentTable => self::TABLE_Hotel,
				),
			)
		);

		$this->assertChildren(
			self::TABLE_Offer, $versionizedOfferId, self::FIELD_Offers_Prices,
			array(
				array(
					'tableName' => self::TABLE_Price,
					'uid' => $versionizedPriceId,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $placeholderPriceId,
					self::FIELD_Prices_ParentId => $versionizedOfferId,
					self::FIELD_Prices_ParentTable => self::TABLE_Offer,
				),
			)
		);

		$this->assertReferenceIndex(
			array(
				$this->combine(self::TABLE_Hotel, $versionizedHotelId, 'offers') => array(
					$this->combine(self::TABLE_Offer, $versionizedOfferId),
				),
				$this->combine(self::TABLE_Offer, $versionizedOfferId, 'prices') => array(
					$this->combine(self::TABLE_Price, $versionizedPriceId),
				),
			)
		);
	}

	/**
	 * @test
	 */
	public function liveRecordsAreCopiedToDifferentPage() {
		$this->markTestSkipped('This test is failing - the Core needs to be fixed');

		$tceMain = $this->simulateCommand(
			self::COMMAND_Copy,
			self::VALUE_PidAlternative,
			array(
				self::TABLE_Hotel => 1
			)
		);

		$placeholderHotelId = $tceMain->copyMappingArray_merged[self::TABLE_Hotel][1];
		$versionizedHotelId = $tceMain->getAutoVersionId(self::TABLE_Hotel, $placeholderHotelId);

		$this->assertGreaterThan($placeholderHotelId, $versionizedHotelId);

		$placeholderOfferId = $tceMain->copyMappingArray_merged[self::TABLE_Offer][1];
		$placeholderPriceId = $tceMain->copyMappingArray_merged[self::TABLE_Price][3];

		$this->assertGreaterThan(0, $placeholderOfferId, 'Seems like child reference have not been considered');
		$this->assertGreaterThan(0, $placeholderPriceId, 'Seems like child reference have not been considered');

		/**
		 * Placeholder (Live)
		 */

		$this->assertRecords(
			array(
				self::TABLE_Hotel => array(
					$placeholderHotelId => array(
						'pid' => self::VALUE_PidAlternative,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => 1,
					),
					$versionizedHotelId => array(
						'pid' => -1,
						't3ver_wsid' => self::VALUE_WorkspaceId,
						't3ver_state' => -1,
						self::FIELD_Hotel_Offers => 2,
					),
				),
				self::TABLE_Offer => array(
					$placeholderOfferId => array(
						'pid' => self::VALUE_PidAlternative,
						't3ver_wsid' => self::VALUE_WorkspaceId,
					),
				),
				self::TABLE_Price => array(
					$placeholderPriceId => array(
						'pid' => self::VALUE_PidAlternative,
						't3ver_wsid' => self::VALUE_WorkspaceId,
					),
				),
			)
		);
	}

	/****************************************************************
	 * PUBLISH/SWAP/CLEAR Behaviour
	 ****************************************************************/

	/**
	 * @return void
	 * @test
	 */
	public function isChildPublishedSeparatelyIfParentIsNotVersionized() {
		$childElements = array(
			self::TABLE_Offer => '1',
		);
		$this->simulateEditing($childElements);

		$versionizedOfferId = $this->getWorkspaceVersionId(self::TABLE_Offer, 1);
		$versionizedPriceId = $this->getWorkspaceVersionId(self::TABLE_Price, 3);

		$this->simulateCommandByStructure(array(
			self::TABLE_Price => array(
				'3' => array(
					'version' => array(
						'action' => self::COMMAND_Version_Swap,
						'swapWith' => $versionizedPriceId,
						'notificationAlternativeRecipients' => array(),
					)
				)
			),
			self::TABLE_Offer => array(
				'1' => array(
					'version' => array(
						'action' => self::COMMAND_Version_Swap,
						'swapWith' => $versionizedOfferId,
						'notificationAlternativeRecipients' => array(),
					)
				)
			),
		));

		// Live:
		$this->assertChildren(
			self::TABLE_Hotel, 1, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => 1,
					't3ver_oid' => 0,
					't3_origuid' => 1,
					't3ver_id' => 1, // it was published
					't3ver_label' => 'Auto-created for WS #' . self::VALUE_WorkspaceId,
					self::FIELD_Offers_ParentId => 1,
				),
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => 2,
					't3ver_oid' => 0,
					't3_origuid' => 0,
					't3ver_id' => 0,
					self::FIELD_Offers_ParentId => 1,
				),
			)
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function isChildSwappedSeparatelyIfParentIsNotVersionized() {
		$childElements = array(
			self::TABLE_Offer => '1',
		);
		$this->simulateEditing($childElements);

		$versionizedOfferId = $this->getWorkspaceVersionId(self::TABLE_Offer, 1);
		$versionizedPriceId = $this->getWorkspaceVersionId(self::TABLE_Price, 3);

		$this->simulateCommandByStructure(array(
			self::TABLE_Price => array(
				'3' => array(
					'version' => array(
						'action' => self::COMMAND_Version_Swap,
						'swapWith' => $versionizedPriceId,
						'swapIntoWS' => 1,
						'notificationAlternativeRecipients' => array(),
					)
				)
			),
			self::TABLE_Offer => array(
				'1' => array(
					'version' => array(
						'action' => self::COMMAND_Version_Swap,
						'swapWith' => $versionizedOfferId,
						'swapIntoWS' => 1,
						'notificationAlternativeRecipients' => array(),
					)
				)
			),
		));

		// Live:
		$this->assertChildren(
			self::TABLE_Hotel, 1, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => 1,
					't3ver_oid' => 0,
					't3_origuid' => 1,
					't3ver_id' => 1, // it was published
					't3ver_label' => 'Auto-created for WS #' . self::VALUE_WorkspaceId,
					self::FIELD_Offers_ParentId => 1,
				),
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => 2,
					't3ver_oid' => 0,
					't3_origuid' => 0,
					't3ver_id' => 0,
					self::FIELD_Offers_ParentId => 1,
				),
			)
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function isSortingOrderOfChildRecordsPreservedIfParentIsSwapped() {
		$this->setWorkspacesConsiderReferences(TRUE);

		$this->versionizeAllChildrenWithParent();
		$versionizedHotelId = $this->getWorkspaceVersionId(self::TABLE_Hotel, 1);

		$this->getCommandMapAccess(1);

		// Swap to live:
		$this->simulateCommandByStructure(array(
			self::TABLE_Hotel => array(
				'1' => array(
					'version' => array(
						'action' => self::COMMAND_Version_Swap,
						'swapWith' => $versionizedHotelId,
						'swapIntoWS' => 1,
						'notificationAlternativeRecipients' => array(),
					)
				)
			),
		));

		$this->assertChildren(
			self::TABLE_Hotel, 1, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => 1,
					't3ver_oid' => 0,
					't3_origuid' => 1,
					't3ver_id' => 1, // it was published
					't3ver_label' => 'Auto-created for WS #' . self::VALUE_WorkspaceId,
					'sorting' => 1,
					self::FIELD_Offers_ParentId => 1,
				),
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => 2,
					't3ver_oid' => 0,
					't3_origuid' => 2,
					't3ver_id' => 1, // it was published
					't3ver_label' => 'Auto-created for WS #' . self::VALUE_WorkspaceId,
					'sorting' => 2,
					self::FIELD_Offers_ParentId => 1,
				),
			)
		);

		$this->assertChildren(
			self::TABLE_Offer, 2, self::FIELD_Offers_Prices,
			array(
				array(
					'tableName' => self::TABLE_Price,
					'uid' => 1,
					't3ver_oid' => 0,
					't3_origuid' => 1,
					't3ver_id' => 1, // it was published
					't3ver_label' => 'Auto-created for WS #' . self::VALUE_WorkspaceId,
					'sorting' => 1,
					self::FIELD_Prices_ParentId => 2,
				),
				array(
					'tableName' => self::TABLE_Price,
					'uid' => 2,
					't3ver_oid' => 0,
					't3_origuid' => 2,
					't3ver_id' => 1, // it was published
					't3ver_label' => 'Auto-created for WS #' . self::VALUE_WorkspaceId,
					'sorting' => 2,
					self::FIELD_Prices_ParentId => 2,
				),
			)
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function doChildRecordsHaveCorrectSortingOrderOnCreation() {
		$elements = $this->getElementStructureForEditing(
			array(
				self::TABLE_Hotel => 1,
				self::TABLE_Offer => 'NEW1,NEW2',
			)
		);
		$elements[self::TABLE_Hotel]['1'][self::FIELD_Hotel_Offers] = 'NEW1,NEW2';
		$elements[self::TABLE_Offer]['NEW1']['pid'] = self::VALUE_Pid;
		$elements[self::TABLE_Offer]['NEW2']['pid'] = self::VALUE_Pid;

		$tceMain = $this->simulateEditingByStructure($elements);

		$firstNewId = $tceMain->substNEWwithIDs['NEW1'];
		$secondNewId = $tceMain->substNEWwithIDs['NEW2'];

		$versionizedFirstNewId = $this->getWorkspaceVersionId(self::TABLE_Offer, $firstNewId);
		$versionizedSecondNewId = $this->getWorkspaceVersionId(self::TABLE_Offer, $secondNewId);

		$this->assertSortingOrder(
			self::TABLE_Offer, 'sorting',
			array($firstNewId, $secondNewId),
			'Sorting order of placeholder records is wrong'
		);

		$this->assertSortingOrder(
			self::TABLE_Offer, 'sorting',
			array($versionizedFirstNewId, $versionizedSecondNewId),
			'Sorting order of draft versions is wrong'
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function doNewChildRecordsOfPageHaveCorrectSortingOrderOnCreation() {
		$elements = $this->getElementStructureForEditing(
			array(
				self::TABLE_Pages => self::VALUE_Pid,
				self::TABLE_Hotel => 'NEW1,NEW2',
			)
		);
		$elements[self::TABLE_Pages][self::VALUE_Pid][self::FIELD_Pages_Hotels] = 'NEW1,NEW2';
		$elements[self::TABLE_Hotel]['NEW1']['pid'] = self::VALUE_Pid;
		$elements[self::TABLE_Hotel]['NEW2']['pid'] = self::VALUE_Pid;

		$tceMain = $this->simulateEditingByStructure($elements);

		$firstNewId = $tceMain->substNEWwithIDs['NEW1'];
		$secondNewId = $tceMain->substNEWwithIDs['NEW2'];

		$versionizedFirstNewId = $this->getWorkspaceVersionId(self::TABLE_Hotel, $firstNewId);
		$versionizedSecondNewId = $this->getWorkspaceVersionId(self::TABLE_Hotel, $secondNewId);

		$this->assertSortingOrder(
			self::TABLE_Hotel, 'sorting',
			array($firstNewId, $secondNewId),
			'Sorting order of placeholder records is wrong'
		);

		$this->assertSortingOrder(
			self::TABLE_Hotel, 'sorting',
			array($versionizedFirstNewId, $versionizedSecondNewId),
			'Sorting order of draft versions is wrong'
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function doNewChildRecordsOfPageHaveCorrectSortingOrderAfterPublishing() {
		$this->setWorkspacesConsiderReferences(TRUE);

		$elements = $this->getElementStructureForEditing(
			array(
				self::TABLE_Pages => self::VALUE_Pid,
				self::TABLE_Hotel => 'NEW1,NEW2',
			)
		);
		$elements[self::TABLE_Pages][self::VALUE_Pid][self::FIELD_Pages_Hotels] = 'NEW1,NEW2';
		$elements[self::TABLE_Hotel]['NEW1']['pid'] = self::VALUE_Pid;
		$elements[self::TABLE_Hotel]['NEW2']['pid'] = self::VALUE_Pid;

		$tceMain = $this->simulateEditingByStructure($elements);

		$firstNewId = $tceMain->substNEWwithIDs['NEW1'];
		$secondNewId = $tceMain->substNEWwithIDs['NEW2'];

		$versionizedPageId = $this->getWorkspaceVersionId(self::TABLE_Pages, self::VALUE_Pid);

		// Swap to live:
		$this->simulateCommandByStructure(array(
			self::TABLE_Pages => array(
				self::VALUE_Pid => array(
					'version' => array(
						'action' => self::COMMAND_Version_Swap,
						'swapWith' => $versionizedPageId,
						'notificationAlternativeRecipients' => array(),
					)
				)
			),
		));

		$this->assertSortingOrder(
			self::TABLE_Hotel, 'sorting',
			array($firstNewId, $secondNewId),
			'Sorting order of published records is wrong'
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function doAddedChildRecordsOfPageHaveCorrectSortingOrderOnCreation() {
		$elements = $this->getElementStructureForEditing(
			array(
				self::TABLE_Pages => self::VALUE_Pid,
				self::TABLE_Hotel => 'NEW1,NEW2',
			)
		);
		$elements[self::TABLE_Pages][self::VALUE_Pid][self::FIELD_Pages_Hotels] = 'NEW1,2,NEW2';
		$elements[self::TABLE_Hotel]['NEW1']['pid'] = self::VALUE_Pid;
		$elements[self::TABLE_Hotel]['NEW2']['pid'] = self::VALUE_Pid;

		$tceMain = $this->simulateEditingByStructure($elements);

		$firstNewId = $tceMain->substNEWwithIDs['NEW1'];
		$secondNewId = $tceMain->substNEWwithIDs['NEW2'];

		$versionizedHotel = $this->getWorkspaceVersionId(self::TABLE_Hotel, 2);
		$versionizedFirstNewId = $this->getWorkspaceVersionId(self::TABLE_Hotel, $firstNewId);
		$versionizedSecondNewId = $this->getWorkspaceVersionId(self::TABLE_Hotel, $secondNewId);

		$this->assertSortingOrder(
			self::TABLE_Hotel, 'sorting',
			array($versionizedFirstNewId, $versionizedHotel, $versionizedSecondNewId),
			'Sorting order of draft version is wrong'
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function doAddedChildRecordsOfPageHaveCorrectSortingOrderAfterPublishing() {
		$this->setWorkspacesConsiderReferences(TRUE);

		$elements = $this->getElementStructureForEditing(
			array(
				self::TABLE_Pages => self::VALUE_Pid,
				self::TABLE_Hotel => 'NEW1,NEW2',
			)
		);
		$elements[self::TABLE_Pages][self::VALUE_Pid][self::FIELD_Pages_Hotels] = 'NEW1,2,NEW2';
		$elements[self::TABLE_Hotel]['NEW1']['pid'] = self::VALUE_Pid;
		$elements[self::TABLE_Hotel]['NEW2']['pid'] = self::VALUE_Pid;

		$tceMain = $this->simulateEditingByStructure($elements);

		$firstNewId = $tceMain->substNEWwithIDs['NEW1'];
		$secondNewId = $tceMain->substNEWwithIDs['NEW2'];

		$versionizedPageId = $this->getWorkspaceVersionId(self::TABLE_Pages, self::VALUE_Pid);

		// Swap to live:
		$this->simulateCommandByStructure(array(
			self::TABLE_Pages => array(
				self::VALUE_Pid => array(
					'version' => array(
						'action' => self::COMMAND_Version_Swap,
						'swapWith' => $versionizedPageId,
						'notificationAlternativeRecipients' => array(),
					)
				)
			),
		));

		$this->assertSortingOrder(
			self::TABLE_Hotel, 'sorting',
			array($firstNewId, 2, $secondNewId),
			'Sorting order of published records is wrong'
		);
	}
}
