<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\IRRE;

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

require_once(dirname(__FILE__). '/AbstractLocalizationTestCase.php');

/**
 * Testcase for m:n MM localizations using localizationMode 'select'.
 *
 */
class MtoNMMAsymetricLocalizationSelectTest extends AbstractLocalizationTestCase {

	/**
	 * Sets up this test case.
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		// Set the localizazionMode to 'select' for all IRRE fields:
		foreach ($this->structure as $tableName => $fields) {
			foreach ($fields as $fieldName) {
				$this->setTcaFieldConfigurationBehaviour(
					$tableName, $fieldName,
					self::BEHAVIOUR_LocalizationMode,
					self::VALUE_LocalizationMode_Select
				);
			}
		}

		$this->importDataSet(dirname(__FILE__) . '/Fixtures/MToNMMAsymmetric.xml');
	}

	/**
	 * @return void
	 * @test
	 */
	public function isOnlyParentLocalized() {
		$this->simulateCommand(
			self::COMMAND_Localize,
			self::VALUE_LanguageId,
			array(self::TABLE_Hotel => '1')
		);

		$localizedHotelId = $this->getLocalizationId(self::TABLE_Hotel, 1);

		$this->assertLocalizations(
			array(
				self::TABLE_Hotel => '1',
			)
		);

		$this->assertLocalizations(
			array(
				self::TABLE_Offer => '1,2',
			),
			self::VALUE_LanguageId,
			FALSE
		);

		$this->assertChildren(
			self::TABLE_Hotel, $localizedHotelId, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => '1',
				),
				array(
					'tableName' => self::TABLE_Offer,
					'uid' => '2',
				),
			),
			self::TABLE_Relation_Hotel_Offer,
			FALSE
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function areChildElementsLocalized() {
		$this->simulateCommand(
			self::COMMAND_Localize,
			self::VALUE_LanguageId,
			array(self::TABLE_Hotel => '1')
		);

		$localizedHotelId = $this->getLocalizationId(self::TABLE_Hotel, 1);

		$this->simulateCommand(
			self::COMMAND_LocalizeSynchronize,
			self::FIELD_Hotel_Offers . ',' . self::COMMAND_LocalizeSynchronize_Localize,
			array(self::TABLE_Hotel => $localizedHotelId)
		);

		$this->assertLocalizations(
			array(
				self::TABLE_Hotel => '1',
				self::TABLE_Offer => '1,2',
			)
		);

		$this->assertLocalizations(
			array(
				self::TABLE_Price => '1,2,3',
			),
			self::VALUE_LanguageId,
			FALSE
		);

		$this->assertChildren(
			self::TABLE_Hotel, $localizedHotelId, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'sys_language_uid' => self::VALUE_LanguageId,
					'l18n_parent' => '1',
				),
				array(
					'tableName' => self::TABLE_Offer,
					'sys_language_uid' => self::VALUE_LanguageId,
					'l18n_parent' => '2',
				),
			),
			self::TABLE_Relation_Hotel_Offer
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function areChildElementsLocalizedWithParent() {
		$this->setTcaFieldConfigurationBehaviour(
			self::TABLE_Offer,
			self::FIELD_Offer_Prices,
			self::BEHAVIOUR_LocalizeChildrenAtParentLocalization,
			TRUE
		);

		$this->simulateCommand(
			self::COMMAND_Localize,
			self::VALUE_LanguageId,
			array(self::TABLE_Hotel => '1')
		);

		$localizedHotelId = $this->getLocalizationId(self::TABLE_Hotel, 1);

		$this->simulateCommand(
			self::COMMAND_LocalizeSynchronize,
			self::FIELD_Hotel_Offers . ',' . self::COMMAND_LocalizeSynchronize_Localize,
			array(self::TABLE_Hotel => $localizedHotelId)
		);

		$this->assertLocalizations(
			array(
				self::TABLE_Hotel => '1',
				self::TABLE_Offer => '1,2',
				self::TABLE_Price => '1,2,3',
			)
		);

		/* Offers */
		$this->assertChildren(
			self::TABLE_Hotel, $localizedHotelId, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'sys_language_uid' => self::VALUE_LanguageId,
					'l18n_parent' => '1',
				),
				array(
					'tableName' => self::TABLE_Offer,
					'sys_language_uid' => self::VALUE_LanguageId,
					'l18n_parent' => '2',
				),
			),
			self::TABLE_Relation_Hotel_Offer
		);

		/* Prices */
		$this->assertChildren(
			self::TABLE_Offer, $this->getLocalizationId(self::TABLE_Offer, 1), self::FIELD_Offer_Prices,
			array(
				array(
					'tableName' => self::TABLE_Price,
					'sys_language_uid' => self::VALUE_LanguageId,
					'l18n_parent' => '1',
				),
			),
			self::TABLE_Relation_Offer_Price
		);

		$this->assertChildren(
			self::TABLE_Offer, $this->getLocalizationId(self::TABLE_Offer, 2), self::FIELD_Offer_Prices,
			array(
				array(
					'tableName' => self::TABLE_Price,
					'sys_language_uid' => self::VALUE_LanguageId,
					'l18n_parent' => '2',
				),
				array(
					'tableName' => self::TABLE_Price,
					'sys_language_uid' => self::VALUE_LanguageId,
					'l18n_parent' => '3',
				),
			),
			self::TABLE_Relation_Offer_Price
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function areDirectChildElementsLocalizedWithParent() {
		$this->setTcaFieldConfigurationBehaviour(
			self::TABLE_Hotel,
			self::FIELD_Hotel_Offers,
			self::BEHAVIOUR_LocalizeChildrenAtParentLocalization,
			TRUE
		);

		$this->simulateCommand(
			self::COMMAND_Localize,
			self::VALUE_LanguageId,
			array(self::TABLE_Hotel => '1')
		);

		$localizedHotelId = $this->getLocalizationId(self::TABLE_Hotel, 1);

		$this->assertLocalizations(
			array(
				self::TABLE_Hotel => '1',
				self::TABLE_Offer => '1,2',
			)
		);

		$this->assertLocalizations(
			array(
				self::TABLE_Price => '1,2,3',
			),
			self::VALUE_LanguageId,
			FALSE
		);

		$this->assertChildren(
			self::TABLE_Hotel, $localizedHotelId, self::FIELD_Hotel_Offers,
			array(
				array(
					'tableName' => self::TABLE_Offer,
					'sys_language_uid' => self::VALUE_LanguageId,
					'l18n_parent' => '1',
				),
				array(
					'tableName' => self::TABLE_Offer,
					'sys_language_uid' => self::VALUE_LanguageId,
					'l18n_parent' => '2',
				),
			),
			self::TABLE_Relation_Hotel_Offer
		);
	}

	/**
	 * @return void
	 * @test
	 */
	public function areAllChildElementsLocalizedWithParent() {
		$this->setTcaFieldConfigurationBehaviour(
			self::TABLE_Hotel,
			self::FIELD_Hotel_Offers,
			self::BEHAVIOUR_LocalizeChildrenAtParentLocalization,
			TRUE
		);

		$this->setTcaFieldConfigurationBehaviour(
			self::TABLE_Offer,
			self::FIELD_Offer_Prices,
			self::BEHAVIOUR_LocalizeChildrenAtParentLocalization,
			TRUE
		);

		$this->simulateCommand(
			self::COMMAND_Localize,
			self::VALUE_LanguageId,
			array(self::TABLE_Hotel => '1')
		);

		$this->assertLocalizations(
			array(
				self::TABLE_Hotel => '1',
				self::TABLE_Offer => '1,2',
				self::TABLE_Price => '1,2,3',
			)
		);
	}
}
