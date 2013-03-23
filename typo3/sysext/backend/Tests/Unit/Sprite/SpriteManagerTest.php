<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Sprite;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Fabien Udriot <fabien.udriot@ecodev.ch>
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
 * Testcase for TYPO3\CMS\Backend\Sprite\SpriteManager
 *
 * @author Fabien Udriot <fabien.udriot@ecodev.ch>
 */
class SpriteManagerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	//////////////////////////////////////////
	// Tests concerning addTcaTypeIcon
	//////////////////////////////////////////
	/**
	 * @test
	 */
	public function addTcaTypeIconWithEmptyValueSetsArrayKey() {
		\TYPO3\CMS\Backend\Sprite\SpriteManager::addTcaTypeIcon('', '', '');
		$this->assertArrayHasKey('tcarecords--', $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']);
	}

	/**
	 * @test
	 */
	public function addTcaTypeIconWithEmptyValueSetsEmptyArrayValue() {
		\TYPO3\CMS\Backend\Sprite\SpriteManager::addTcaTypeIcon('', '', '');
		$this->assertEquals('', $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['tcarecords--']);
	}

	/**
	 * @test
	 */
	public function addTcaTypeIconWithTableAndTypeSetsArrayKey() {
		$table = 'tt_content';
		$type = 'contains-news';
		\TYPO3\CMS\Backend\Sprite\SpriteManager::addTcaTypeIcon($table, $type, '');
		$this->assertArrayHasKey('tcarecords-' . $table . '-' . $type, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']);
	}

	/**
	 * @test
	 */
	public function addTcaTypeIconWithTableAndTypeAndValueSetsArrayValue() {
		$imagePath = 'path/to/my-icon.png';
		$table = 'tt_content';
		$type = 'contains-news';
		\TYPO3\CMS\Backend\Sprite\SpriteManager::addTcaTypeIcon($table, $type, $imagePath);
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
		\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons($icons, $extensionKey);
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
		\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons($icons, $extensionKey);
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
		\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons($icons, $extensionKey);
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
		\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons($icons, $extensionKey);
		$this->assertEquals($imagePath, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['extensions-' . $extensionKey . '-' . $type]);
	}

}

?>