<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Sprite;

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

use TYPO3\CMS\Backend\Sprite\SpriteManager;

/**
 * Testcase for TYPO3\CMS\Backend\Sprite\SpriteManager
 */
class SpriteManagerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    //////////////////////////////////////////
    // Tests concerning addTcaTypeIcon
    //////////////////////////////////////////
    /**
     * @test
     */
    public function addTcaTypeIconWithEmptyValueSetsArrayKey()
    {
        SpriteManager::addTcaTypeIcon('', '', '');
        $this->assertArrayHasKey('tcarecords--', $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']);
    }

    /**
     * @test
     */
    public function addTcaTypeIconWithEmptyValueSetsEmptyArrayValue()
    {
        SpriteManager::addTcaTypeIcon('', '', '');
        $this->assertEquals('', $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['tcarecords--']);
    }

    /**
     * @test
     */
    public function addTcaTypeIconWithTableAndTypeSetsArrayKey()
    {
        $table = 'tt_content';
        $type = 'contains-news';
        SpriteManager::addTcaTypeIcon($table, $type, '');
        $this->assertArrayHasKey('tcarecords-' . $table . '-' . $type, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']);
    }

    /**
     * @test
     */
    public function addTcaTypeIconWithTableAndTypeAndValueSetsArrayValue()
    {
        $imagePath = 'path/to/my-icon.png';
        $table = 'tt_content';
        $type = 'contains-news';
        SpriteManager::addTcaTypeIcon($table, $type, $imagePath);
        $this->assertEquals($imagePath, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['tcarecords-' . $table . '-' . $type]);
    }

    //////////////////////////////////////////
    // Tests concerning addSingleIcons
    //////////////////////////////////////////
    /**
     * @test
     */
    public function addSingleIconsWithEmptyValueSetsArrayKey()
    {
        $type = '';
        $imagePath = 'path/to/my-icon.png';
        $icons = [$type => $imagePath];
        $extensionKey = 'dummy';
        SpriteManager::addSingleIcons($icons, $extensionKey);
        $this->assertArrayHasKey('extensions-' . $extensionKey . '-' . $type, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']);
    }

    /**
     * @test
     */
    public function addSingleIconsWithEmptyValueSetsImagePathValue()
    {
        $type = '';
        $imagePath = 'path/to/my-icon.png';
        $icons = [$type => $imagePath];
        $extensionKey = 'dummy';
        SpriteManager::addSingleIcons($icons, $extensionKey);
        $this->assertEquals($imagePath, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['extensions-' . $extensionKey . '-' . $type]);
    }

    /**
     * @test
     */
    public function addSingleIconsWithNormalValueSetsArrayKey()
    {
        $type = 'contains-news';
        $imagePath = 'path/to/my-icon.png';
        $icons = [$type => $imagePath];
        $extensionKey = 'dummy';
        SpriteManager::addSingleIcons($icons, $extensionKey);
        $this->assertArrayHasKey('extensions-' . $extensionKey . '-' . $type, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']);
    }

    /**
     * @test
     */
    public function addSingleIconsWithNormalValueSetsImagePathValue()
    {
        $type = 'contains-news';
        $imagePath = 'path/to/my-icon.png';
        $icons = [$type => $imagePath];
        $extensionKey = 'dummy';
        SpriteManager::addSingleIcons($icons, $extensionKey);
        $this->assertEquals($imagePath, $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['extensions-' . $extensionKey . '-' . $type]);
    }
}
