<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE;

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

require_once(dirname(__FILE__). '/AbstractTestCase.php');

/**
 * Test case for 1:n csv relations.
 *
 */
abstract class AbstractOneToNTestCase extends AbstractTestCase {
	const TABLE_Hotel = 'tx_irretutorial_1ncsv_hotel';
	const TABLE_Offer = 'tx_irretutorial_1ncsv_offer';
	const TABLE_Price = 'tx_irretutorial_1ncsv_price';

	const FIELD_Hotel_Offers = 'offers';
	const FIELD_Offers_Prices = 'prices';

	/**
	 * Versionize all children with parent.
	 *
	 * @return array
	 */
	protected function versionizeAllChildrenWithParent() {
		$liveElements = array(
			static::TABLE_Hotel => '1',
			static::TABLE_Offer => '1,2',
			// prices 1,2 are children of offer 2
			// price 3 is child of offer 1
			static::TABLE_Price => '1,2,3',
		);

		$this->simulateEditing($liveElements);

		return $liveElements;
	}

	/****************************************************************
	 * PUBLISH/SWAP/CLEAR Behaviour
	 ****************************************************************/

	/**
	 * @return void
	 * @test
	 */
	public function isChildPublishedSeparatelyIfParentIsVersionized() {
		$this->setExpectedLogEntries(1);

		$this->versionizeAllChildrenWithParent();

		$versionizedOfferId = $this->getWorkspaceVersionId(static::TABLE_Offer, 1);

		$this->simulateVersionCommand(
			array(
				'action' => static::COMMAND_Version_Swap,
				'swapWith' => $versionizedOfferId,
			),
			array(
				static::TABLE_Offer => '1',
			)
		);

		$this->assertContains(
			'cannot be swapped or published independently, because it is related to other new or modified records',
			$this->getLastLogEntryMessage(),
			'Expected error was not reported.'
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function isChildSwappedSeparatelyIfParentIsVersionized() {
		$this->setExpectedLogEntries(2);

		$this->versionizeAllChildrenWithParent();

		$versionizedOfferId = $this->getWorkspaceVersionId(static::TABLE_Offer, 1);
		$versionizedPriceId = $this->getWorkspaceVersionId(static::TABLE_Price, 3);

		$this->simulateCommandByStructure(array(
			static::TABLE_Price => array(
				'3' => array(
					'version' => array(
						'action' => static::COMMAND_Version_Swap,
						'swapWith' => $versionizedPriceId,
						'swapIntoWS' => 1,
						'notificationAlternativeRecipients' => array(),
					)
				)
			),
			static::TABLE_Offer => array(
				'1' => array(
					'version' => array(
						'action' => static::COMMAND_Version_Swap,
						'swapWith' => $versionizedOfferId,
						'swapIntoWS' => 1,
						'notificationAlternativeRecipients' => array(),
					)
				)
			),
		));

		$this->assertContains(
			'cannot be swapped or published independently, because it is related to other new or modified records',
			$this->getLastLogEntryMessage(),
			'Expected error was not reported.'
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function areAllChildrenSwappedAutomaticallyIfParentIsSwapped() {
		$this->setWorkspacesConsiderReferences(TRUE);

		$this->versionizeAllChildrenWithParent();
		$versionizedHotelId = $this->getWorkspaceVersionId(static::TABLE_Hotel, 1);

		$this->getCommandMapAccess(1);

		// Swap to live:
		$this->simulateCommandByStructure(array(
			static::TABLE_Hotel => array(
				'1' => array(
					'version' => array(
						'action' => static::COMMAND_Version_Swap,
						'swapWith' => $versionizedHotelId,
						'swapIntoWS' => 1,
						'notificationAlternativeRecipients' => array(),
					)
				)
			),
		));

		$commandMap = $this->getCommandMap()->get();

		$this->assertTrue(isset($commandMap[static::TABLE_Hotel][1]['version']), static::TABLE_Hotel . ':1 is not set.');
		$this->assertTrue(isset($commandMap[static::TABLE_Offer][1]['version']), static::TABLE_Offer . ':1 is not set.');
		$this->assertTrue(isset($commandMap[static::TABLE_Offer][2]['version']), static::TABLE_Offer . ':2 is not set.');
		$this->assertTrue(isset($commandMap[static::TABLE_Price][1]['version']), static::TABLE_Price . ':1 is not set.');
		$this->assertTrue(isset($commandMap[static::TABLE_Price][2]['version']), static::TABLE_Price . ':2 is not set.');
		$this->assertTrue(isset($commandMap[static::TABLE_Price][3]['version']), static::TABLE_Price . ':3 is not set.');
	}

	/**
	 * @return void
	 * @test
	 */
	public function areAllChildrenDoubleSwappedAutomaticallyIfParentIsSwapped() {
		$this->setWorkspacesConsiderReferences(TRUE);

		$this->versionizeAllChildrenWithParent();
		$versionizedHotelId = $this->getWorkspaceVersionId(static::TABLE_Hotel, 1);

		// Swap to live:
		$this->simulateCommandByStructure(array(
			static::TABLE_Hotel => array(
				'1' => array(
					'version' => array(
						'action' => static::COMMAND_Version_Swap,
						'swapWith' => $versionizedHotelId,
						'swapIntoWS' => 1,
						'notificationAlternativeRecipients' => array(),
					)
				)
			),
		));

		$this->getCommandMapAccess(1);

		// Swap back to workspace:
		$this->simulateCommandByStructure(array(
			static::TABLE_Hotel => array(
				'1' => array(
					'version' => array(
						'action' => static::COMMAND_Version_Swap,
						'swapWith' => $versionizedHotelId,
						'swapIntoWS' => 1,
						'notificationAlternativeRecipients' => array(),
					)
				)
			),
		));

		$commandMap = $this->getCommandMap()->get();

		$this->assertTrue(isset($commandMap[static::TABLE_Hotel][1]['version']), static::TABLE_Hotel . ':1 is not set.');
		$this->assertTrue(isset($commandMap[static::TABLE_Offer][1]['version']), static::TABLE_Offer . ':1 is not set.');
		$this->assertTrue(isset($commandMap[static::TABLE_Offer][2]['version']), static::TABLE_Offer . ':2 is not set.');
		$this->assertTrue(isset($commandMap[static::TABLE_Price][1]['version']), static::TABLE_Price . ':1 is not set.');
		$this->assertTrue(isset($commandMap[static::TABLE_Price][2]['version']), static::TABLE_Price . ':2 is not set.');
		$this->assertTrue(isset($commandMap[static::TABLE_Price][3]['version']), static::TABLE_Price . ':3 is not set.');
	}

	/*
	 * Removing child records
	 */

	/**
	 * Live version will be versionized, but one child branch is removed.
	 *
	 * @return void
	 * @test
	 */
	public function areChildRecordsConsideredToBeRemovedOnEditingParent() {
		$this->simulateByStructure(
			$this->getElementStructureForEditing(array(
				static::TABLE_Hotel => '1',
			)),
			$this->getElementStructureForCommands(static::COMMAND_Delete, 1, array(
				static::TABLE_Offer => '1',
			))
		);

		$this->assertHasDeletePlaceholder(array(
			static::TABLE_Offer => '1',
			static::TABLE_Price => '3',
		));
	}

	/**
	 * Live version will be versionized, but one child branch is removed.
	 *
	 * @return void
	 * @test
	 */
	public function areChildRecordsConsideredToBeRemovedOnEditingParentAndChildren() {
		$this->simulateByStructure(
			$this->getElementStructureForEditing(array(
				static::TABLE_Hotel => '1',
				static::TABLE_Offer => '1',
			)),
			$this->getElementStructureForCommands(static::COMMAND_Delete, 1, array(
				static::TABLE_Offer => '1',
			))
		);

		$this->assertHasDeletePlaceholder(array(
			static::TABLE_Offer => '1',
			static::TABLE_Price => '3',
		));
	}

	/**
	 * Versionized version will be modified and one child branch is removed.
	 *
	 * @return void
	 * @test
	 */
	public function areChildRecordsConsideredToBeRevertedOnEditing() {
		$this->markTestSkipped('Enable this test once http://forge.typo3.org/issues/29278 is merged');

		$this->versionizeAllChildrenWithParent();

		$versionizedOfferId = $this->getWorkspaceVersionId(static::TABLE_Offer, 1);
		$versionizedPriceId = $this->getWorkspaceVersionId(static::TABLE_Price, 3);

		$this->simulateCommand(static::COMMAND_Delete, 1, array(static::TABLE_Offer => $versionizedOfferId));

		$this->assertIsDeleted(array(
			static::TABLE_Offer => $versionizedOfferId,
			static::TABLE_Price => $versionizedPriceId,
		));
	}

	/**
	 * @return void
	 * @test
	 */
	public function areNestedChildRecordsConsideredToBeRemovedOnDirectRemoval() {
		$this->simulateCommand(static::COMMAND_Delete, 1, array(static::TABLE_Offer => 1));

		$this->assertHasDeletePlaceholder(array(
			static::TABLE_Offer => '1',
			static::TABLE_Price => '3',
		));
	}

	/**
	 * Test whether elements that are reverted in the workspace module
	 * also trigger the reverting of child records.
	 *
	 * @return void
	 * @test
	 */
	public function areChildRecordsRevertedOnRevertingTheRelativeRemovedParent() {
		$this->setWorkspacesConsiderReferences(TRUE);

		$this->simulateByStructure(
			$this->getElementStructureForEditing(array(
				static::TABLE_Hotel => '1',
				static::TABLE_Offer => '1',
			)),
			$this->getElementStructureForCommands(static::COMMAND_Delete, 1, array(
				static::TABLE_Offer => '1',
			))
		);

		$versionizedOfferId = $this->getWorkspaceVersionId(static::TABLE_Offer, 1, static::VALUE_WorkspaceId, TRUE);

		$this->simulateCommandByStructure(array(
			static::TABLE_Offer => array(
				$versionizedOfferId => array(
					'version' => array(
						'action' => static::COMMAND_Version_Clear,
					)
				)
			),
		));

		$this->assertWorkspaceVersions(array(
			static::TABLE_Hotel => '1',
			static::TABLE_Offer => '2',
			static::TABLE_Price => '1,2',
		));

		$this->assertFalse($this->getWorkspaceVersionId(static::TABLE_Offer, 1, static::VALUE_WorkspaceId, TRUE));
		$this->assertFalse($this->getWorkspaceVersionId(static::TABLE_Price, 3, static::VALUE_WorkspaceId, TRUE));
	}

	/**
	 * Test whether elements that are reverted in the workspace module
	 * also trigger the reverting of child records.
	 *
	 * @return void
	 * @test
	 */
	public function areChildRecordsRevertedOnRevertingMultipleElements() {
		$this->setWorkspacesConsiderReferences(TRUE);

		$this->simulateByStructure(
			$this->getElementStructureForEditing(array(
				static::TABLE_Hotel => '1',
				static::TABLE_Offer => '1',
			)),
			$this->getElementStructureForCommands(static::COMMAND_Delete, 1, array(
				static::TABLE_Offer => '1',
			))
		);

		$versionizedOfferId = $this->getWorkspaceVersionId(static::TABLE_Offer, 1, static::VALUE_WorkspaceId, TRUE);
		$versionizedPriceId = $this->getWorkspaceVersionId(static::TABLE_Price, 1);

		$this->simulateCommandByStructure(array(
			static::TABLE_Offer => array(
				$versionizedOfferId => array(
					'version' => array(
						'action' => static::COMMAND_Version_Clear,
					)
				)
			),
			static::TABLE_Price => array(
				$versionizedPriceId => array(
					'version' => array(
						'action' => static::COMMAND_Version_Clear,
					)
				)
			),
		));

		$this->assertWorkspaceVersions(array(
			static::TABLE_Hotel => '1',
			static::TABLE_Offer => '2',
			static::TABLE_Price => '2',
		));

		$this->assertFalse($this->getWorkspaceVersionId(static::TABLE_Offer, 1, static::VALUE_WorkspaceId, TRUE));
		$this->assertFalse($this->getWorkspaceVersionId(static::TABLE_Price, 3, static::VALUE_WorkspaceId, TRUE));
		$this->assertFalse($this->getWorkspaceVersionId(static::TABLE_Price, 1, static::VALUE_WorkspaceId, TRUE));
	}

	/**
	 * Tests whether records marked to be deleted in a workspace
	 * are really removed if they are published.
	 *
	 * @return void
	 * @test
	 */
	public function areParentAndChildRecordsRemovedOnPublishingDeleteAction() {
		$this->setWorkspacesConsiderReferences(TRUE);

		$this->simulateByStructure(
			array(),
			$this->getElementStructureForCommands(static::COMMAND_Delete, 1, array(
				static::TABLE_Hotel => '1',
			))
		);

		$versionizedHotelId = $this->getWorkspaceVersionId(static::TABLE_Hotel, 1, static::VALUE_WorkspaceId, TRUE);

		// Swap to live:
		$this->simulateCommandByStructure(array(
			static::TABLE_Hotel => array(
				'1' => array(
					'version' => array(
						'action' => static::COMMAND_Version_Swap,
						'swapWith' => $versionizedHotelId,
						'notificationAlternativeRecipients' => array(),
					)
				)
			),
		));

		$this->assertRecords(
			array(
				static::TABLE_Hotel => array(
					1 => array('deleted' => '1',),
				),
				static::TABLE_Offer => array(
					1 => array('deleted' => '1',),
					2 => array('deleted' => '1',),
				),
				static::TABLE_Price => array(
					1 => array('deleted' => '1',),
					2 => array('deleted' => '1',),
					3 => array('deleted' => '1',),
				),
			),
			static::VALUE_WorkspaceIdIgnore
		);
	}
}
