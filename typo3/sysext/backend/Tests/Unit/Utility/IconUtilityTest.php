<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Fabien Udriot <fabien.udriot@ecodev.ch>
 *  (c) 2010-2011 Oliver Klee <typo3-coding@oliverklee.de>
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
 * Testcase for TYPO3\CMS\Backend\Utility\IconUtility
 *
 * @author Fabien Udriot <fabien.udriot@ecodev.ch>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class IconUtilityTest extends \TYPO3\CMS\Core\Tests\BaseTestCase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB', 'TBE_STYLES');

	/**
	 * @var array
	 */
	protected $mockRecord;

	/**
	 * Set up test case
	 *
	 * @return void
	 */
	public function setUp() {
		if (!\TYPO3\CMS\Backend\Sprite\SpriteManager::isInitialized()) {
			\TYPO3\CMS\Backend\Sprite\SpriteManager::initialize();
		}
		// Simulate a tt_content record
		$this->mockRecord = array();
		$this->mockRecord['header'] = 'dummy content header';
		$this->mockRecord['uid'] = '1';
		$this->mockRecord['pid'] = '1';
		$this->mockRecord['image'] = '';
		$this->mockRecord['hidden'] = '0';
		$this->mockRecord['starttime'] = '0';
		$this->mockRecord['endtime'] = '0';
		$this->mockRecord['fe_group'] = '';
		$this->mockRecord['CType'] = 'text';
		$this->mockRecord['t3ver_id'] = '0';
		$this->mockRecord['t3ver_state'] = '0';
		$this->mockRecord['t3ver_wsid'] = '0';
		$this->mockRecord['sys_TYPO3\\CMS\\Lang\\LanguageService_uid'] = '0';
		$this->mockRecord['l18n_parent'] = '0';
		$this->mockRecord['subheader'] = '';
		$this->mockRecord['bodytext'] = '';
	}

	public function tearDown() {
		unset($this->mockRecord);
	}

	//////////////////////////////////////////
	// Tests concerning imagemake
	//////////////////////////////////////////
	/**
	 * @test
	 */
	public function imagemakeFixesPermissionsOnNewFiles() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('imagemakeFixesPermissionsOnNewFiles() test not available on Windows.');
		}
		$fixtureGifFile = __DIR__ . '/Fixtures/clear.gif';
		// Create image ressource, determine target filename, fake target permission, run method and clean up
		$fixtureGifRessource = imagecreatefromgif($fixtureGifFile);
		$targetFilename = PATH_site . 'typo3temp/' . uniqid('test_') . '.gif';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0777';
		\TYPO3\CMS\Backend\Utility\IconUtility::imagemake($fixtureGifRessource, $targetFilename);
		clearstatcache();
		$resultFilePermissions = substr(decoct(fileperms($targetFilename)), 2);
		\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($targetFilename);
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
	public function getSpriteIconClassesWithEmptyStringReturnsT3Icon() {
		$this->assertEquals('t3-icon', \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses(''));
	}

	/**
	 * Tests whether one part returns 't3-icon'
	 *
	 * @test
	 */
	public function getSpriteIconClassesWithOnePartReturnsT3Icon() {
		$this->assertEquals('t3-icon', \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('actions'));
	}

	/**
	 * Tests the return of two parts
	 *
	 * @test
	 */
	public function getSpriteIconClassesWithTwoPartsReturnsT3IconAndCombinedParts() {
		$result = explode(' ', \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('actions-juggle'));
		sort($result);
		$this->assertEquals(array('t3-icon', 't3-icon-actions', 't3-icon-actions-juggle', 't3-icon-juggle'), $result);
	}

	/**
	 * Tests the return of tree parts
	 *
	 * @test
	 */
	public function getSpriteIconClassesWithThreePartsReturnsT3IconAndCombinedParts() {
		$result = explode(' ', \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('actions-juggle-speed'));
		sort($result);
		$this->assertEquals(array('t3-icon', 't3-icon-actions', 't3-icon-actions-juggle', 't3-icon-juggle-speed'), $result);
	}

	/**
	 * Tests the return of four parts
	 *
	 * @test
	 */
	public function getSpriteIconClassesWithFourPartsReturnsT3IconAndCombinedParts() {
		$result = explode(' ', \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('actions-juggle-speed-game'));
		sort($result);
		$this->assertEquals(array('t3-icon', 't3-icon-actions', 't3-icon-actions-juggle', 't3-icon-juggle-speed-game'), $result);
	}

	//////////////////////////////////////////
	// Tests concerning getSpriteIcon
	//////////////////////////////////////////
	/**
	 * Tests whether an empty string returns a span with the missing sprite
	 *
	 * @test
	 */
	public function getSpriteIconWithEmptyStringReturnsSpanWithIconMissingSprite() {
		$this->assertEquals('<span class="t3-icon t3-icon-status t3-icon-status-status t3-icon-status-icon-missing">&nbsp;</span>', \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(''));
	}

	/**
	 * Tests whether an non existing icons returns a span with the missing sprite
	 *
	 * @test
	 */
	public function getSpriteIconWithMissingIconReturnsSpanWithIconMissingSprite() {
		$this->assertEquals('<span class="t3-icon t3-icon-status t3-icon-status-status t3-icon-status-icon-missing">&nbsp;</span>', \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-juggle-speed'));
	}

	/**
	 * Tests whether an existing icon returns a span with the correct sprite
	 *
	 * @test
	 */
	public function getSpriteIconWithExistingIconReturnsSpanWithIconSprite() {
		$this->assertEquals('<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-new">&nbsp;</span>', \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new'));
	}

	/**
	 * Tests the returns of an existing icon + an other attribute like title="foo"
	 *
	 * @test
	 */
	public function getSpriteIconWithExistingIconAndAttributeReturnsSpanWithIconSpriteAndAttribute() {
		$this->assertEquals('<span title="foo" class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-new">&nbsp;</span>', \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new', array('title' => 'foo')));
	}

	/**
	 * Tests the returns of an existing icon + a class attribute
	 *
	 * @test
	 */
	public function getSpriteIconWithExistingIconAndClassAttributeReturnsSpanWithIconSpriteAndClassAttribute() {
		$this->assertEquals('<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-new foo">&nbsp;</span>', \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new', array('class' => 'foo')));
	}

	/**
	 * Tests the returns of an existing icon + a class attribute
	 *
	 * @test
	 */
	public function getSpriteIconWithExistingIconAndInnerHTMLReturnsSpanWithIconSpriteAndInnerHTML() {
		$this->assertEquals('<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-new">foo</span>', \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new', array('html' => 'foo')));
	}

	/**
	 * Tests the returns of an existing icon + an overlay
	 *
	 * @test
	 */
	public function getSpriteIconWithExistingIconAndOverlayReturnsSpanWithIconSpriteAndOverlay() {
		$result = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new', array(), array('status-overlay-hidden' => array()));
		$overlay = '<span class="t3-icon t3-icon-status t3-icon-status-overlay t3-icon-overlay-hidden t3-icon-overlay">&nbsp;</span>';
		$this->assertEquals('<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-new">' . $overlay . '</span>', $result);
	}

	/**
	 * Tests the returns of an existing icon + an overlay
	 *
	 * @test
	 */
	public function getSpriteIconWithExistingIconAndOverlayAndAttributesReturnsSpanWithIconSpriteAndOverlayAndAttributes() {
		$result = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new', array('html' => 'foo1'), array('status-overlay-hidden' => array('class' => 'foo2')));
		$overlay = '<span class="t3-icon t3-icon-status t3-icon-status-overlay t3-icon-overlay-hidden foo2 t3-icon-overlay">foo1</span>';
		$this->assertEquals('<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-new">' . $overlay . '</span>', $result);
	}

	//////////////////////////////////////////
	// Tests concerning getSpriteIconForRecord
	//////////////////////////////////////////
	/**
	 * Tests the returns of NULL table + empty array
	 *
	 * @test
	 */
	public function getSpriteIconForRecordWithNullTableReturnsMissingIcon() {
		$result = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('', array());
		$this->assertEquals('<span class="t3-icon t3-icon-status t3-icon-status-status t3-icon-status-icon-missing">&nbsp;</span>', $result);
	}

	/**
	 * Tests the returns of tt_content + empty record
	 *
	 * @test
	 */
	public function getSpriteIconForRecordWithEmptyRecordReturnsNormalSprite() {
		$result = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('tt_content', array());
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-x t3-icon-x-content-text">&nbsp;</span>', $result);
	}

	/**
	 * Tests the returns of tt_content + mock record
	 *
	 * @test
	 */
	public function getSpriteIconForRecordWithMockRecordReturnsNormalSprite() {
		$mockRecord = $this->mockRecord;
		$result = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('tt_content', $mockRecord);
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-x t3-icon-x-content-text">&nbsp;</span>', $result);
	}

	/**
	 * Tests the returns of tt_content + mock record + options
	 *
	 * @test
	 */
	public function getSpriteIconForRecordWithMockRecordAndOptionsReturnsNormalSprite() {
		$mockRecord = $this->mockRecord;
		$result = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('tt_content', $mockRecord, array('class' => 'foo', 'title' => 'bar'));
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-x t3-icon-x-content-text foo" title="bar">&nbsp;</span>', $result);
	}

	/**
	 * Tests the returns of tt_content + mock record of type 'list' (aka plugin)
	 *
	 * @test
	 */
	public function getSpriteIconForRecordWithMockRecordOfTypePluginReturnsPluginSprite() {
		$mockRecord = $this->mockRecord;
		$mockRecord['CType'] = 'list';
		$result = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('tt_content', $mockRecord);
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-x t3-icon-x-content-plugin">&nbsp;</span>', $result);
	}

	/**
	 * Tests the returns of tt_content + mock record with hidden flag
	 *
	 * @test
	 */
	public function getSpriteIconForRecordWithMockRecordWithHiddenFlagReturnsNormalSpriteAndOverlay() {
		$mockRecord = $this->mockRecord;
		$mockRecord['hidden'] = '1';
		$result = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('tt_content', $mockRecord);
		$overlay = '<span class="t3-icon t3-icon-status t3-icon-status-overlay t3-icon-overlay-hidden t3-icon-overlay">&nbsp;</span>';
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-x t3-icon-x-content-text">' . $overlay . '</span>', $result);
	}

	//////////////////////////////////////////
	// Tests concerning getSpriteIconForFile
	//////////////////////////////////////////
	/**
	 * Tests the returns of no file
	 *
	 * @test
	 */
	public function getSpriteIconForFileWithNoFileTypeReturnsOtherSprite() {
		$result = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile('');
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-other t3-icon-other-other">&nbsp;</span>', $result);
	}

	/**
	 * Tests the returns of unknown file
	 *
	 * @test
	 */
	public function getSpriteIconForFileWithNoUnknowFileTypeReturnsOtherSprite() {
		$result = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile('foo');
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-other t3-icon-other-other">&nbsp;</span>', $result);
	}

	/**
	 * Tests the returns of file pdf
	 *
	 * @test
	 */
	public function getSpriteIconForFileWithPdfReturnsPdfSprite() {
		$result = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile('pdf');
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-pdf t3-icon-pdf">&nbsp;</span>', $result);
	}

	/**
	 * Tests the returns of file png
	 *
	 * @test
	 */
	public function getSpriteIconForFileWithPngReturnsPngSprite() {
		$result = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile('png');
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-media t3-icon-media-image">&nbsp;</span>', $result);
	}

	/**
	 * Tests the returns of file png + option
	 *
	 * @test
	 */
	public function getSpriteIconForFileWithPngAndOptionsReturnsPngSpriteAndOptions() {
		$result = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile('png', array('title' => 'bar'));
		$this->assertEquals('<span title="bar" class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-media t3-icon-media-image">&nbsp;</span>', $result);
	}

	/**
	 * Tests whether a overrideIconOverlay hook is called.
	 *
	 * @test
	 */
	public function isOverrideIconOverlayHookCalled() {
		$classReference = uniqid('user_overrideIconOverlayHook');
		$hookMock = $this->getMock($classReference, array('overrideIconOverlay'), array());
		$hookMock->expects($this->once())->method('overrideIconOverlay');
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_iconworks.php']['overrideIconOverlay'][$classReference] = $classReference;
		$GLOBALS['T3_VAR']['getUserObj'][$classReference] = $hookMock;
		\TYPO3\CMS\Backend\Utility\IconUtility::mapRecordOverlayToSpriteIconName('tt_content', array());
	}

	/**
	 * Tests whether a faulty overrideIconOverlay hook (the hook object cannot be found) is not called.
	 *
	 * @test
	 */
	public function isFaultyOverrideIconOverlayHookNotCalled() {
		$classReference = uniqid('user_overrideIconOverlayHook');
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_iconworks.php']['overrideIconOverlay'][$classReference] = $classReference;
		$GLOBALS['T3_VAR']['getUserObj'][$classReference] = new \stdClass();
		\TYPO3\CMS\Backend\Utility\IconUtility::mapRecordOverlayToSpriteIconName('tt_content', array());
	}

}

?>