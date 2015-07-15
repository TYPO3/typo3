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
class IconUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array Simulate a tt_content record
	 */
	protected $mockRecord = array(
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
	);

	/**
	 * Create folder object to use as test subject
	 *
	 * @param string $identifier
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	protected function getTestSubjectFolderObject($identifier) {
		$mockedStorage = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class, array(), array(), '', FALSE);
		$mockedStorage->expects($this->any())->method('getRootLevelFolder')->will($this->returnValue(
			new \TYPO3\CMS\Core\Resource\Folder($mockedStorage, '/', '/')
		));
		$mockedStorage->expects($this->any())->method('checkFolderActionPermission')->will($this->returnValue(TRUE));
		return new \TYPO3\CMS\Core\Resource\Folder($mockedStorage, $identifier, $identifier);
	}

	/**
	 * Create file object to use as test subject
	 *
	 * @param $extension
	 * @return \TYPO3\CMS\Core\Resource\File
	 */
	protected function getTestSubjectFileObject($extension) {
		$mockedStorage = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class, array(), array(), '', FALSE);
		$mockedFile = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, array(), array(array(), $mockedStorage));
		$mockedFile->expects($this->once())->method('getExtension')->will($this->returnValue($extension));
		return $mockedFile;
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
		// Create image resource, determine target filename, fake target permission, run method and clean up
		$fixtureGifRessource = imagecreatefromgif($fixtureGifFile);
		$targetFilename = PATH_site . 'typo3temp/' . $this->getUniqueId('test_') . '.gif';
		$this->testFilesToDelete[] = $targetFilename;
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0777';
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
	public function getSpriteIconClassesWithEmptyStringReturnsT3Icon() {
		$this->assertEquals('t3-icon', IconUtilityFixture::getSpriteIconClasses(''));
	}

	/**
	 * Tests whether one part returns 't3-icon'
	 *
	 * @test
	 */
	public function getSpriteIconClassesWithOnePartReturnsT3Icon() {
		$this->assertEquals('t3-icon', IconUtilityFixture::getSpriteIconClasses('actions'));
	}

	/**
	 * Tests the return of two parts
	 *
	 * @test
	 */
	public function getSpriteIconClassesWithTwoPartsReturnsT3IconAndCombinedParts() {
		$result = explode(' ', IconUtilityFixture::getSpriteIconClasses('actions-juggle'));
		sort($result);
		$this->assertEquals(array('t3-icon', 't3-icon-actions', 't3-icon-actions-juggle', 't3-icon-juggle'), $result);
	}

	/**
	 * Tests the return of tree parts
	 *
	 * @test
	 */
	public function getSpriteIconClassesWithThreePartsReturnsT3IconAndCombinedParts() {
		$result = explode(' ', IconUtilityFixture::getSpriteIconClasses('actions-juggle-speed'));
		sort($result);
		$this->assertEquals(array('t3-icon', 't3-icon-actions', 't3-icon-actions-juggle', 't3-icon-juggle-speed'), $result);
	}

	/**
	 * Tests the return of four parts
	 *
	 * @test
	 */
	public function getSpriteIconClassesWithFourPartsReturnsT3IconAndCombinedParts() {
		$result = explode(' ', IconUtilityFixture::getSpriteIconClasses('actions-juggle-speed-game'));
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
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
				),
			),
		);
		$this->assertEquals('<span class="t3-icon t3-icon-status t3-icon-status-status t3-icon-status-icon-missing"> </span>', IconUtilityFixture::getSpriteIcon(''));
	}

	/**
	 * Tests whether an non existing icons returns a span with the missing sprite
	 *
	 * @test
	 */
	public function getSpriteIconWithMissingIconReturnsSpanWithIconMissingSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
				),
			),
		);
		$this->assertEquals('<span class="t3-icon t3-icon-status t3-icon-status-status t3-icon-status-icon-missing"> </span>', IconUtilityFixture::getSpriteIcon('actions-juggle-speed'));
	}

	/**
	 * Tests whether an existing icon returns a span with the correct sprite
	 *
	 * @test
	 */
	public function getSpriteIconWithExistingIconReturnsSpanWithIconSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'actions-document-new',
				),
			),
		);
		$this->assertEquals('<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-new"> </span>', IconUtilityFixture::getSpriteIcon('actions-document-new'));
	}

	/**
	 * Tests the returns of an existing icon + an other attribute like title="foo"
	 *
	 * @test
	 */
	public function getSpriteIconWithExistingIconAndAttributeReturnsSpanWithIconSpriteAndAttribute() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'actions-document-new',
				),
			),
		);
		$this->assertEquals('<span title="foo" class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-new"> </span>', IconUtilityFixture::getSpriteIcon('actions-document-new', array('title' => 'foo')));
	}

	/**
	 * Tests the returns of an existing icon + a class attribute
	 *
	 * @test
	 */
	public function getSpriteIconWithExistingIconAndClassAttributeReturnsSpanWithIconSpriteAndClassAttribute() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'actions-document-new',
				),
			),
		);
		$this->assertEquals('<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-new foo"> </span>', IconUtilityFixture::getSpriteIcon('actions-document-new', array('class' => 'foo')));
	}

	/**
	 * Tests the returns of an existing icon + a class attribute
	 *
	 * @test
	 */
	public function getSpriteIconWithExistingIconAndInnerHTMLReturnsSpanWithIconSpriteAndInnerHTML() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'actions-document-new',
				),
			),
		);
		$this->assertEquals('<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-new">foo</span>', IconUtilityFixture::getSpriteIcon('actions-document-new', array('html' => 'foo')));
	}

	/**
	 * Tests the returns of an existing icon + an overlay
	 *
	 * @test
	 */
	public function getSpriteIconWithExistingIconAndOverlayReturnsSpanWithIconSpriteAndOverlay() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'actions-document-new',
					'status-overlay-hidden',
				),
			),
		);
		$result = IconUtilityFixture::getSpriteIcon('actions-document-new', array(), array('status-overlay-hidden' => array()));
		$overlay = '<span class="t3-icon t3-icon-status t3-icon-status-overlay t3-icon-overlay-hidden t3-icon-overlay"> </span>';
		$this->assertEquals('<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-new">' . $overlay . '</span>', $result);
	}

	/**
	 * Tests the returns of an existing icon + an overlay
	 *
	 * @test
	 */
	public function getSpriteIconWithExistingIconAndOverlayAndAttributesReturnsSpanWithIconSpriteAndOverlayAndAttributes() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'actions-document-new',
					'status-overlay-hidden',
				),
			),
		);
		$result = IconUtilityFixture::getSpriteIcon('actions-document-new', array('html' => 'foo1'), array('status-overlay-hidden' => array('class' => 'foo2')));
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
		$result = IconUtilityFixture::getSpriteIconForRecord('', array());
		$this->assertEquals('<span class="t3-icon t3-icon-status t3-icon-status-status t3-icon-status-icon-missing"> </span>', $result);
	}

	/**
	 * Tests the returns of tt_content + empty record
	 *
	 * @test
	 */
	public function getSpriteIconForRecordWithEmptyRecordReturnsNormalSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array('mimetypes-x-content-text'),
			),
		);
		$GLOBALS['TCA'] = array(
			'tt_content' => array(
				'ctrl' => array(
					'typeicon_column' => 'CType',
					'typeicon_classes' => array(
						'default' => 'mimetypes-x-content-text',
					),
				),
			),
		);
		$result = IconUtilityFixture::getSpriteIconForRecord('tt_content', array());
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-x t3-icon-x-content-text"> </span>', $result);
	}

	/**
	 * Tests the returns of tt_content + mock record
	 *
	 * @test
	 */
	public function getSpriteIconForRecordWithMockRecordReturnsNormalSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array('mimetypes-x-content-text'),
			),
		);
		$GLOBALS['TCA'] = array(
			'tt_content' => array(
				'ctrl' => array(
					'typeicon_column' => 'CType',
					'typeicon_classes' => array(
						'text' => 'mimetypes-x-content-text',
					),
				),
			),
		);
		$result = IconUtilityFixture::getSpriteIconForRecord('tt_content', $this->mockRecord);
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-x t3-icon-x-content-text"> </span>', $result);
	}

	/**
	 * Tests the returns of tt_content + mock record + options
	 *
	 * @test
	 */
	public function getSpriteIconForRecordWithMockRecordAndOptionsReturnsNormalSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array('mimetypes-x-content-text'),
			),
		);
		$GLOBALS['TCA'] = array(
			'tt_content' => array(
				'ctrl' => array(
					'typeicon_column' => 'CType',
					'typeicon_classes' => array(
						'text' => 'mimetypes-x-content-text',
					),
				),
			),
		);
		$result = IconUtilityFixture::getSpriteIconForRecord('tt_content', $this->mockRecord, array('class' => 'foo', 'title' => 'bar'));
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-x t3-icon-x-content-text foo" title="bar"> </span>', $result);
	}

	/**
	 * Tests the returns of tt_content + mock record of type 'list' (aka plugin)
	 *
	 * @test
	 */
	public function getSpriteIconForRecordWithMockRecordOfTypePluginReturnsPluginSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array('mimetypes-x-content-plugin'),
			),
		);
		$GLOBALS['TCA'] = array(
			'tt_content' => array(
				'ctrl' => array(
					'typeicon_column' => 'CType',
					'typeicon_classes' => array(
						'list' => 'mimetypes-x-content-plugin',
					),
				),
			),
		);
		$mockRecord = $this->mockRecord;
		$mockRecord['CType'] = 'list';
		$result = IconUtilityFixture::getSpriteIconForRecord('tt_content', $mockRecord);
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-x t3-icon-x-content-plugin"> </span>', $result);
	}

	/**
	 * Tests the returns of tt_content + mock record with hidden flag
	 *
	 * @test
	 */
	public function getSpriteIconForRecordWithMockRecordWithHiddenFlagReturnsNormalSpriteAndOverlay() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'mimetypes-x-content-text',
					'status-overlay-hidden',
				),
				'spriteIconRecordOverlayNames' => array(
					'hidden' => 'status-overlay-hidden',
				),
				'spriteIconRecordOverlayPriorities' => array(
					'hidden'
				),
			),
		);
		$GLOBALS['TCA'] = array(
			'tt_content' => array(
				'ctrl' => array(
					'enablecolumns' => array(
						'disabled' => 'hidden',
					),
					'typeicon_column' => 'CType',
					'typeicon_classes' => array(
						'text' => 'mimetypes-x-content-text',
					),
				),
			),
		);
		$mockRecord = $this->mockRecord;
		$mockRecord['hidden'] = '1';
		$result = IconUtilityFixture::getSpriteIconForRecord('tt_content', $mockRecord);
		$overlay = '<span class="t3-icon t3-icon-status t3-icon-status-overlay t3-icon-overlay-hidden t3-icon-overlay"> </span>';
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
		$result = IconUtilityFixture::getSpriteIconForFile('');
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-other t3-icon-other-other"> </span>', $result);
	}

	/**
	 * Tests the returns of unknown file
	 *
	 * @test
	 */
	public function getSpriteIconForFileWithNoUnknowFileTypeReturnsOtherSprite() {
		$result = IconUtilityFixture::getSpriteIconForFile('foo');
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-other t3-icon-other-other"> </span>', $result);
	}

	/**
	 * Tests the returns of file pdf
	 *
	 * @test
	 */
	public function getSpriteIconForFileWithPdfReturnsPdfSprite() {
		$result = IconUtilityFixture::getSpriteIconForFile('pdf');
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-pdf t3-icon-pdf"> </span>', $result);
	}

	/**
	 * Tests the returns of file png
	 *
	 * @test
	 */
	public function getSpriteIconForFileWithPngReturnsPngSprite() {
		$result = IconUtilityFixture::getSpriteIconForFile('png');
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-media t3-icon-media-image"> </span>', $result);
	}

	/**
	 * Tests the returns of file png + option
	 *
	 * @test
	 */
	public function getSpriteIconForFileWithPngAndOptionsReturnsPngSpriteAndOptions() {
		$result = IconUtilityFixture::getSpriteIconForFile('png', array('title' => 'bar'));
		$this->assertEquals('<span title="bar" class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-media t3-icon-media-image"> </span>', $result);
	}

	/**
	 * Tests whether a overrideIconOverlay hook is called.
	 *
	 * @test
	 */
	public function isOverrideIconOverlayHookCalled() {
		$classReference = $this->getUniqueId('user_overrideIconOverlayHook');
		$hookMock = $this->getMock($classReference, array('overrideIconOverlay'), array());
		$hookMock->expects($this->once())->method('overrideIconOverlay');
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_iconworks.php']['overrideIconOverlay'][$classReference] = $classReference;
		$GLOBALS['T3_VAR']['getUserObj'][$classReference] = $hookMock;
		IconUtilityFixture::mapRecordOverlayToSpriteIconName('tt_content', array());
	}

	/**
	 * Tests whether a faulty overrideIconOverlay hook (the hook object cannot be found) is not called.
	 *
	 * @test
	 */
	public function isFaultyOverrideIconOverlayHookNotCalled() {
		$classReference = $this->getUniqueId('user_overrideIconOverlayHook');
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_iconworks.php']['overrideIconOverlay'][$classReference] = $classReference;
		$GLOBALS['T3_VAR']['getUserObj'][$classReference] = new \stdClass();
		IconUtilityFixture::mapRecordOverlayToSpriteIconName('tt_content', array());
	}

	//////////////////////////////////////////////
	// Tests concerning getSpriteIconForResource
	//////////////////////////////////////////////
	/**
	 * Tests the returns of no file
	 *
	 * @test
	 */
	public function getSpriteIconForResourceWithFileWithoutExtensionTypeReturnsOtherSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'mimetypes-other-other',
				),
			),
		);
		$fileObject = $this->getTestSubjectFileObject('');
		$result = IconUtilityFixture::getSpriteIconForResource($fileObject);
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-other t3-icon-other-other"> </span>', $result);
	}

	/**
	 * Tests the returns of unknown file
	 *
	 * @test
	 */
	public function getSpriteIconForResourceWithUnknownFileTypeReturnsOtherSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'mimetypes-other-other',
				),
			),
		);
		$fileObject = $this->getTestSubjectFileObject('foo');
		$result = IconUtilityFixture::getSpriteIconForResource($fileObject);
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-other t3-icon-other-other"> </span>', $result);
	}

	/**
	 * Tests the returns of file pdf
	 *
	 * @test
	 */
	public function getSpriteIconForResourceWithPdfReturnsPdfSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'mimetypes-pdf',
				),
			),
		);
		$fileObject = $this->getTestSubjectFileObject('pdf');
		$result = IconUtilityFixture::getSpriteIconForResource($fileObject);
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-pdf t3-icon-pdf"> </span>', $result);
	}

	/**
	 * Tests the returns of file png
	 *
	 * @test
	 */
	public function getSpriteIconForResourceWithPngFileReturnsPngSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'mimetypes-media-image',
				),
			),
		);
		$fileObject = $this->getTestSubjectFileObject('png');
		$result = IconUtilityFixture::getSpriteIconForResource($fileObject);
		$this->assertEquals('<span class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-media t3-icon-media-image"> </span>', $result);
	}

	/**
	 * Tests the returns of file png + option
	 *
	 * @test
	 */
	public function getSpriteIconForResourceWithPngFileAndOptionsReturnsPngSpriteAndOptions() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'mimetypes-media-image',
				),
			),
		);
		$fileObject = $this->getTestSubjectFileObject('png');
		$result = IconUtilityFixture::getSpriteIconForResource($fileObject, array('title' => 'bar'));
		$this->assertEquals('<span title="bar" class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-media t3-icon-media-image"> </span>', $result);
	}

	/**
	 * Tests the returns of normal folder
	 *
	 * @test
	 */
	public function getSpriteIconForResourceWithFolderReturnsFolderSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'apps-filetree-folder-default',
				),
			),
		);
		$folderObject = $this->getTestSubjectFolderObject('/test');
		$result = IconUtilityFixture::getSpriteIconForResource($folderObject);
		$this->assertEquals('<span class="t3-icon t3-icon-apps t3-icon-apps-filetree t3-icon-filetree-folder-default"> </span>', $result);
	}

	/**
	 * Tests the returns of open folder
	 *
	 * @test
	 */
	public function getSpriteIconForResourceWithOpenFolderReturnsOpenFolderSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'apps-filetree-folder-opened',
				),
			),
		);
		$folderObject = $this->getTestSubjectFolderObject('/test');
		$result = IconUtilityFixture::getSpriteIconForResource($folderObject, array('folder-open' => TRUE));
		$this->assertEquals('<span class="t3-icon t3-icon-apps t3-icon-apps-filetree t3-icon-filetree-folder-opened"> </span>', $result);
	}

	/**
	 * Tests the returns of root folder
	 *
	 * @test
	 */
	public function getSpriteIconForResourceWithRootFolderReturnsRootFolderSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'apps-filetree-root',
				),
			),
		);
		$folderObject = $this->getTestSubjectFolderObject('/');
		$result = IconUtilityFixture::getSpriteIconForResource($folderObject);
		$this->assertEquals('<span class="t3-icon t3-icon-apps t3-icon-apps-filetree t3-icon-filetree-root"> </span>', $result);
	}

	/**
	 * Tests the returns of mount root
	 *
	 * @test
	 */
	public function getSpriteIconForResourceWithMountRootReturnsMountFolderSprite() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array(
					'apps-filetree-mount',
				),
			),
		);
		$folderObject = $this->getTestSubjectFolderObject('/mount');
		$result = IconUtilityFixture::getSpriteIconForResource($folderObject, array('mount-root' => TRUE));
		$this->assertEquals('<span class="t3-icon t3-icon-apps t3-icon-apps-filetree t3-icon-filetree-mount"> </span>', $result);
	}

	/**
	 * Tests whether a overrideResourceIcon hook is called.
	 *
	 * @test
	 */
	public function isOverrideResourceIconHookCalled() {
		$GLOBALS['TBE_STYLES'] = array(
			'spriteIconApi' => array(
				'iconsAvailable' => array()
			),
		);
		$classReference = $this->getUniqueId('user_overrideResourceIconHook');
		$folderObject = $this->getTestSubjectFolderObject('/test');
		$hookMock = $this->getMock(\TYPO3\CMS\Backend\Utility\IconUtilityOverrideResourceIconHookInterface::class, array('overrideResourceIcon'), array(), $classReference);
		$hookMock->expects($this->once())->method('overrideResourceIcon');
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_iconworks.php']['overrideResourceIcon'][$classReference] = $classReference;
		$GLOBALS['T3_VAR']['getUserObj'][$classReference] = $hookMock;
		IconUtilityFixture::getSpriteIconForResource($folderObject);
	}

	/**
	 * Tests whether a faulty overrideResourceIcon hook (the hook object cannot be found) is not called.
	 *
	 * @test
	 * @expectedException \UnexpectedValueException
	 */
	public function isFaultyResourceIconHookNotCalled() {
		$classReference = $this->getUniqueId('user_overrideResourceIconHook');
		$folderObject = $this->getTestSubjectFolderObject('/test');
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_iconworks.php']['overrideResourceIcon'][$classReference] = $classReference;
		$GLOBALS['T3_VAR']['getUserObj'][$classReference] = new \stdClass();
		IconUtilityFixture::getSpriteIconForResource($folderObject);
	}

}
