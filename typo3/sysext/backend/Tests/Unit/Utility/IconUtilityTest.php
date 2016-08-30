<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Utility;

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

use TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures\IconUtilityFixture;

/**
 * Test case
 */
class IconUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var array Simulate a tt_content record
     */
    protected $mockRecord = [
        'header' => 'dummy content header',
        'uid' => '1',
        'pid' => '1',
        'image' => '',
        'hidden' => '0',
        'starttime' => '0',
        'endtime' => '0',
        'fe_group' => '',
        'CType' => 'text',
        't3ver_id' => '0',
        't3ver_state' => '0',
        't3ver_wsid' => '0',
        'sys_language_uid' => '0',
        'l18n_parent' => '0',
        'subheader' => '',
        'bodytext' => '',
    ];

    /**
     * Create folder object to use as test subject
     *
     * @param string $identifier
     * @return \TYPO3\CMS\Core\Resource\Folder
     */
    protected function getTestSubjectFolderObject($identifier)
    {
        $mockedStorage = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class, [], [], '', false);
        $mockedStorage->expects($this->any())->method('getRootLevelFolder')->will($this->returnValue(
            new \TYPO3\CMS\Core\Resource\Folder($mockedStorage, '/', '/')
        ));
        $mockedStorage->expects($this->any())->method('checkFolderActionPermission')->will($this->returnValue(true));
        $mockedStorage->expects($this->any())->method('isBrowsable')->will($this->returnValue(true));
        return new \TYPO3\CMS\Core\Resource\Folder($mockedStorage, $identifier, $identifier);
    }

    /**
     * Create file object to use as test subject
     *
     * @param $extension
     * @return \TYPO3\CMS\Core\Resource\File
     */
    protected function getTestSubjectFileObject($extension)
    {
        $mockedStorage = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class, [], [], '', false);
        $mockedFile = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, [], [[], $mockedStorage]);
        $mockedFile->expects($this->once())->method('getExtension')->will($this->returnValue($extension));
        return $mockedFile;
    }

    //////////////////////////////////////////
    // Tests concerning imagemake
    //////////////////////////////////////////
    /**
     * @test
     */
    public function imagemakeFixesPermissionsOnNewFiles()
    {
        if (TYPO3_OS == 'WIN') {
            $this->markTestSkipped('imagemakeFixesPermissionsOnNewFiles() test not available on Windows.');
        }
        $fixtureGifFile = __DIR__ . '/Fixtures/clear.gif';
        // Create image resource, determine target filename, fake target permission, run method and clean up
        $fixtureGifRessource = imagecreatefromgif($fixtureGifFile);
        $targetFilename = PATH_site . 'typo3temp/' . $this->getUniqueId('test_') . '.gif';
        $this->testFilesToDelete[] = $targetFilename;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = '0777';
        IconUtilityFixture::imagemake($fixtureGifRessource, $targetFilename);
        clearstatcache();
        $resultFilePermissions = substr(decoct(fileperms($targetFilename)), 2);
        $this->assertEquals($resultFilePermissions, '0777');
    }

    //////////////////////////////////////////
    // Tests concerning getSpriteIconClasses
    //////////////////////////////////////////
    /**
     * Tests whether an empty string returns 't3-icon'
     *
     * @test
     */
    public function getSpriteIconClassesWithEmptyStringReturnsT3Icon()
    {
        $this->assertEquals('t3-icon', IconUtilityFixture::getSpriteIconClasses(''));
    }

    /**
     * Tests whether one part returns 't3-icon'
     *
     * @test
     */
    public function getSpriteIconClassesWithOnePartReturnsT3Icon()
    {
        $this->assertEquals('t3-icon', IconUtilityFixture::getSpriteIconClasses('actions'));
    }

    /**
     * Tests the return of two parts
     *
     * @test
     */
    public function getSpriteIconClassesWithTwoPartsReturnsT3IconAndCombinedParts()
    {
        $result = explode(' ', IconUtilityFixture::getSpriteIconClasses('actions-juggle'));
        sort($result);
        $this->assertEquals(['t3-icon', 't3-icon-actions', 't3-icon-actions-juggle', 't3-icon-juggle'], $result);
    }

    /**
     * Tests the return of tree parts
     *
     * @test
     */
    public function getSpriteIconClassesWithThreePartsReturnsT3IconAndCombinedParts()
    {
        $result = explode(' ', IconUtilityFixture::getSpriteIconClasses('actions-juggle-speed'));
        sort($result);
        $this->assertEquals(['t3-icon', 't3-icon-actions', 't3-icon-actions-juggle', 't3-icon-juggle-speed'], $result);
    }

    /**
     * Tests the return of four parts
     *
     * @test
     */
    public function getSpriteIconClassesWithFourPartsReturnsT3IconAndCombinedParts()
    {
        $result = explode(' ', IconUtilityFixture::getSpriteIconClasses('actions-juggle-speed-game'));
        sort($result);
        $this->assertEquals(['t3-icon', 't3-icon-actions', 't3-icon-actions-juggle', 't3-icon-juggle-speed-game'], $result);
    }
}
