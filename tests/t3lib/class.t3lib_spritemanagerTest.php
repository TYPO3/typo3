<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Fabien Udriot <fabien.udriot@ecodev.ch>
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

/**
 * Testcase for class t3lib_SpriteManager.
 *
 * @author Fabien Udriot <fabien.udriot@ecodev.ch>
 *
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_SpriteManagerTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');


	//////////////////////////////////////////
	// Tests concerning addTcaTypeIcon
	//////////////////////////////////////////

	/**
	 * @test
	 */
	public function addTcaTypeIconWithEmptyValueSetsArrayKey() {
		t3lib_SpriteManager::addTcaTypeIcon('', '', '');
		$this->assertArrayHasKey('tcarecords--', $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']);
	}

	/**
	 * @test
	 */
	public function addTcaTypeIconWithEmptyValueSetsEmptyArrayValue() {
		t3lib_SpriteManager::addTcaTypeIcon('', '', '');
		$this->assertEquals('', $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['tcarecords--']);
	}

	/**
	 * @test
	 */
	public function addTcaTypeIconWithTableAndTypeSetsArrayKey() {
		$table = 'tt_content';
		$type = 'contains-news';
		t3lib_SpriteManager::addTcaTypeIcon($table, $type, '');
		$this->assertArrayHasKey('tcarecords-' . $table . '-' . $type, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']);
	}

	/**
	 * @test
	 */
	public function addTcaTypeIconWithTableAndTypeAndValueSetsArrayValue() {
		$imagePath = 'path/to/my-icon.png';
		$table = 'tt_content';
		$type = 'contains-news';
		t3lib_SpriteManager::addTcaTypeIcon($table, $type, $imagePath);
		$this->assertEquals($imagePath, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['tcarecords-' . $table . '-' . $type]);
	}


	//////////////////////////////////////////
	// Tests concerning addSingleIcons
	//////////////////////////////////////////

	/**
	 * @test
	 */
	public function addSingleIconsWithEmptyValueSetsArrayKey() {
		$type = '';
		$imagePath = 'path/to/my-icon.png';
		$icons = array($type => $imagePath);
		$extensionKey = 'dummy';
		t3lib_SpriteManager::addSingleIcons($icons, $extensionKey);
		$this->assertArrayHasKey('extensions-' . $extensionKey . '-' . $type, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']);
	}

	/**
	 * @test
	 */
	public function addSingleIconsWithEmptyValueSetsImagePathValue() {
		$type = '';
		$imagePath = 'path/to/my-icon.png';
		$icons = array($type => $imagePath);
		$extensionKey = 'dummy';
		t3lib_SpriteManager::addSingleIcons($icons, $extensionKey);
		$this->assertEquals($imagePath, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['extensions-' . $extensionKey . '-' . $type]);
	}

	/**
	 * @test
	 */
	public function addSingleIconsWithNormalValueSetsArrayKey() {
		$type = 'contains-news';
		$imagePath = 'path/to/my-icon.png';
		$icons = array($type => $imagePath);
		$extensionKey = 'dummy';
		t3lib_SpriteManager::addSingleIcons($icons, $extensionKey);
		$this->assertArrayHasKey('extensions-' . $extensionKey . '-' . $type, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']);
	}

	/**
	 * @test
	 */
	public function addSingleIconsWithNormalValueSetsImagePathValue() {
		$type = 'contains-news';
		$imagePath = 'path/to/my-icon.png';
		$icons = array($type => $imagePath);
		$extensionKey = 'dummy';
		t3lib_SpriteManager::addSingleIcons($icons, $extensionKey);
		$this->assertEquals($imagePath, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['extensions-' . $extensionKey . '-' . $type]);
	}
}
?>
