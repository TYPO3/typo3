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
 * Test case for 1:n csv relations.
 *
 */
class OneToNCSVTest extends AbstractOneToNTestCase {
	const TABLE_Hotel = 'tx_irretutorial_1ncsv_hotel';
	const TABLE_Offer = 'tx_irretutorial_1ncsv_offer';
	const TABLE_Price = 'tx_irretutorial_1ncsv_price';

	/**
	 * Sets up this test case.
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->importDataSet(dirname(__FILE__) . '/Fixtures/OneToNCSV.xml');
	}

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
					't3ver_oid' => 2,
					't3_origuid' => 2,
					self::FIELD_Offers_Prices => $this->getWorkspaceVersionId(self::TABLE_Price, 1) . ',' . $this->getWorkspaceVersionId(self::TABLE_Price, 2),
				),
				array(
					'tableName' => self::TABLE_Offer,
					't3ver_oid' => 1,
					't3_origuid' => 1,
					self::FIELD_Offers_Prices => $this->getWorkspaceVersionId(self::TABLE_Price, 3),
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
					'uid' => 2,
					't3ver_id' => 0,
					self::FIELD_Offers_Prices => '1,2',
				),
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => 1,
					't3ver_id' => 0,
					self::FIELD_Offers_Prices => '3',
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
					't3ver_oid' => 2,
					't3_origuid' => 2,
					't3ver_id' => 1,
					self::FIELD_Offers_Prices => $this->getWorkspaceVersionId(self::TABLE_Price, 1) . ',' . $this->getWorkspaceVersionId(self::TABLE_Price, 2),
				),
				array(
					'tableName' => self::TABLE_Offer,
					't3ver_oid' => 1,
					't3_origuid' => 1,
					't3ver_id' => 1,
					self::FIELD_Offers_Prices => $versionizedPriceId,
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

		$placeholderOfferIdA = $tceMain->copyMappingArray_merged[self::TABLE_Offer][1];
		$placeholderOfferIdB = $tceMain->copyMappingArray_merged[self::TABLE_Offer][2];
		$placeholderPriceId = $tceMain->copyMappingArray_merged[self::TABLE_Price][3];

		$this->assertGreaterThan(0, $placeholderOfferIdA, 'Seems like child reference have not been considered');
		$this->assertGreaterThan(0, $placeholderOfferIdB, 'Seems like child reference have not been considered');
		$this->assertGreaterThan(0, $placeholderPriceId, 'Seems like child reference have not been considered');

		$versionizedOfferIdA = $tceMain->getAutoVersionId(self::TABLE_Offer, $placeholderOfferIdA);
		$versionizedOfferIdB = $tceMain->getAutoVersionId(self::TABLE_Offer, $placeholderOfferIdB);
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
						self::FIELD_Hotel_Offers => $versionizedOfferIdA . ',' . $versionizedOfferIdB,
					),
				),
				self::TABLE_Offer => array(
					$placeholderOfferIdA => array(
						'pid' => self::VALUE_Pid,
						't3ver_wsid' => self::VALUE_WorkspaceId,
					),
					$placeholderOfferIdB => array(
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
					'uid' => $versionizedOfferIdA,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $placeholderOfferIdA,
				),
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => $versionizedOfferIdB,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $placeholderOfferIdB,
				),
			)
		);

		$this->assertChildren(
			self::TABLE_Offer, $versionizedOfferIdB, self::FIELD_Offers_Prices,
			array(
				array(
					'tableName' => self::TABLE_Price,
					'uid' => $versionizedPriceId,
					'pid' => -1,
					't3ver_id' => 1,
					't3ver_oid' => $placeholderPriceId,
				),
			)
		);

		$this->assertReferenceIndex(
			array(
				$this->combine(self::TABLE_Hotel, $versionizedHotelId, 'offers') => array(
					$this->combine(self::TABLE_Offer, $versionizedOfferIdA),
					$this->combine(self::TABLE_Offer, $versionizedOfferIdB),
				),
				$this->combine(self::TABLE_Offer, $versionizedOfferIdB, 'prices') => array(
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

		$placeholderOfferIdA = $tceMain->copyMappingArray_merged[self::TABLE_Offer][1];
		$placeholderOfferIdB = $tceMain->copyMappingArray_merged[self::TABLE_Offer][2];
		$placeholderPriceId = $tceMain->copyMappingArray_merged[self::TABLE_Price][3];

		$this->assertGreaterThan(0, $placeholderOfferIdA, 'Seems like child reference have not been considered');
		$this->assertGreaterThan(0, $placeholderOfferIdB, 'Seems like child reference have not been considered');
		$this->assertGreaterThan(0, $placeholderPriceId, 'Seems like child reference have not been considered');

		$versionizedOfferIdA = $tceMain->getAutoVersionId(self::TABLE_Offer, $placeholderOfferIdA);
		$versionizedOfferIdB = $tceMain->getAutoVersionId(self::TABLE_Offer, $placeholderOfferIdB);

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
						self::FIELD_Hotel_Offers => $versionizedOfferIdA . ',' . $versionizedOfferIdB,
					),
				),
				self::TABLE_Offer => array(
					$placeholderOfferIdA => array(
						'pid' => self::VALUE_PidAlternative,
						't3ver_wsid' => self::VALUE_WorkspaceId,
					),
					$placeholderOfferIdB => array(
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
					'uid' => 2,
					't3ver_oid' => 0,
					't3_origuid' => 0,
					't3ver_id' => 0,
					self::FIELD_Offers_Prices => '1,2',
				),
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => 1,
					't3ver_oid' => 0,
					't3_origuid' => 1,
					't3ver_id' => 1, // it was published
					't3ver_label' => 'Auto-created for WS #' . self::VALUE_WorkspaceId,
					self::FIELD_Offers_Prices => '3',
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
					self::FIELD_Offers_Prices => '3',
				),
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => 2,
					't3ver_oid' => 0,
					't3_origuid' => 0,
					't3ver_id' => 0,
					self::FIELD_Offers_Prices => '1,2',
				),
			)
		);
	}
}
