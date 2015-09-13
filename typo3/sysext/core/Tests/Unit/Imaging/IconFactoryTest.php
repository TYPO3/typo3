<?php
namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider;
use TYPO3\CMS\Core\Resource\File;

/**
 * TestCase for \TYPO3\CMS\Core\Imaging\IconFactory
 */
class IconFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Imaging\IconFactory
	 */
	protected $subject = NULL;

	/**
	 * @var string
	 */
	protected $notRegisteredIconIdentifier = 'my-super-unregistered-identifier';

	/**
	 * @var string
	 */
	protected $registeredIconIdentifier = 'actions-document-close';

	/**
	 * @var string
	 */
	protected $registeredSpinningIconIdentifier = 'spinning-icon';

	/**
	 * @var \TYPO3\CMS\Core\Imaging\IconRegistry
	 */
	protected $iconRegistryMock;

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
	 * Set up
	 *
	 * @return void
	 */
	protected function setUp() {
		$this->iconRegistryMock = $this->prophesize(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
		$this->subject = new IconFactory($this->iconRegistryMock->reveal());

		$this->iconRegistryMock->isRegistered(Argument::any())->willReturn(TRUE);
		$this->iconRegistryMock->isDeprecated(Argument::any())->willReturn(FALSE);
		$this->iconRegistryMock->getIconConfigurationByIdentifier(Argument::any())->willReturn([
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'times',
				'additionalClasses' => 'fa-fw'
			)
		]);
	}

	/**
	 * DataProvider for icon sizes
	 *
	 * @return array
	 */
	public function differentSizesDataProvider() {
		return [
			['size ' . Icon::SIZE_SMALL => ['input' => Icon::SIZE_SMALL, 'expected' => Icon::SIZE_SMALL]],
			['size ' . Icon::SIZE_DEFAULT => ['input' => Icon::SIZE_DEFAULT, 'expected' => Icon::SIZE_DEFAULT]],
			['size ' . Icon::SIZE_LARGE => ['input' => Icon::SIZE_LARGE, 'expected' => Icon::SIZE_LARGE]]
		];
	}

	/**
	 * @test
	 */
	public function getIconReturnsIconWithCorrectMarkupWrapperIfRegisteredIconIdentifierIsUsed() {
		$this->assertContains('<span class="icon-markup">',
			$this->subject->getIcon($this->registeredIconIdentifier)->render());
	}

	/**
	 * @test
	 */
	public function getIconByIdentifierReturnsIconWithCorrectMarkupIfRegisteredIconIdentifierIsUsed() {
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-actions-document-close">',
			$this->subject->getIcon($this->registeredIconIdentifier)->render());
	}

	/**
	 * @test
	 * @dataProvider differentSizesDataProvider
	 */
	public function getIconByIdentifierAndSizeReturnsIconWithCorrectMarkupIfRegisteredIconIdentifierIsUsed($size) {
		$this->assertContains('<span class="icon icon-size-' . $size['expected'] . ' icon-state-default icon-actions-document-close">',
			$this->subject->getIcon($this->registeredIconIdentifier, $size['input'])->render());
	}

	/**
	 * @test
	 * @dataProvider differentSizesDataProvider
	 */
	public function getIconByIdentifierAndSizeAndWithOverlayReturnsIconWithCorrectOverlayMarkupIfRegisteredIconIdentifierIsUsed($size) {
		$this->assertContains('<span class="icon-overlay icon-overlay-readonly">',
			$this->subject->getIcon($this->registeredIconIdentifier, $size['input'], 'overlay-readonly')->render());
	}

	/**
	 * @test
	 */
	public function getIconReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed() {
		$this->iconRegistryMock->isRegistered(Argument::any())->willReturn(FALSE);
		$this->iconRegistryMock->getDefaultIconIdentifier(Argument::any())->willReturn('default-not-found');
		$this->iconRegistryMock->getIconConfigurationByIdentifier('default-not-found')->willReturn([
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'times-circle',
				'additionalClasses' => 'fa-fw'
			)
		]);
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-default-not-found">',
			$this->subject->getIcon($this->notRegisteredIconIdentifier)->render());
	}

	/**
	 * @test
	 * @dataProvider differentSizesDataProvider
	 */
	public function getIconByIdentifierAndSizeReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed($size) {
		$this->iconRegistryMock->isRegistered(Argument::any())->willReturn(FALSE);
		$this->iconRegistryMock->getDefaultIconIdentifier(Argument::any())->willReturn('default-not-found');
		$this->iconRegistryMock->getIconConfigurationByIdentifier('default-not-found')->willReturn([
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'times-circle',
				'additionalClasses' => 'fa-fw'
			)
		]);
		$this->assertContains('<span class="icon icon-size-' . $size['expected'] . ' icon-state-default icon-default-not-found">',
			$this->subject->getIcon($this->notRegisteredIconIdentifier, $size['input'])->render());
	}

	/**
	 * @test
	 */
	public function getIconReturnsCorrectMarkupIfIconIsRegisteredAsSpinningIcon() {
		$this->iconRegistryMock->getIconConfigurationByIdentifier($this->registeredSpinningIconIdentifier)->willReturn([
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'times-circle',
				'additionalClasses' => 'fa-fw',
				'spinning' => TRUE
			)
		]);
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-' . $this->registeredSpinningIconIdentifier . ' icon-spin">',
			$this->subject->getIcon($this->registeredSpinningIconIdentifier)->render());
	}

	/**
	 * @test
	 * @dataProvider differentSizesDataProvider
	 * @param string $size
	 */
	public function getIconByIdentifierAndSizeAndOverlayReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed($size) {
		$this->assertContains('<span class="icon-overlay icon-overlay-readonly">',
			$this->subject->getIcon($this->notRegisteredIconIdentifier, $size['input'], 'overlay-readonly')->render());
	}

	/**
	 * @test
	 */
	public function getIconThrowsExceptionIfInvalidSizeIsGiven() {
		$this->setExpectedException('InvalidArgumentException');
		$this->subject->getIcon($this->registeredIconIdentifier, 'foo')->render();
	}

	/**
	 * @test
	 * @param $deprecationSettings
	 * @param $expected
	 * @dataProvider getIconReturnsReplacementIconWhenDeprecatedDataProvider
	 */
	public function getIconReturnsReplacementIconWhenDeprecated($deprecationSettings, $expected) {
		$this->iconRegistryMock->isDeprecated($this->registeredIconIdentifier)->willReturn(TRUE);
		$this->iconRegistryMock->getDeprecationSettings($this->registeredIconIdentifier)->willReturn($deprecationSettings);

		$this->assertContains(
			$expected,
			$this->subject->getIcon($this->registeredIconIdentifier, Icon::SIZE_SMALL)->render()
		);
	}

	/**
	 * Data provider for getIconReturnsReplacementIconWhenDeprecated
	 *
	 * @return array
	 */
	public function getIconReturnsReplacementIconWhenDeprecatedDataProvider() {
		return array(
			'Deprecated icon returns replacement' => [
				[
					'message' => '%s is deprecated since TYPO3 CMS 7, this icon will be removed in TYPO3 CMS 8',
					'replacement' => 'alternative-icon-identifier' // must be registered
				],
				'<span class="icon icon-size-small icon-state-default icon-alternative-icon-identifier">'
			],
			'Deprecated icon returns default icon' => [
				[
					'message' => '%s is deprecated since TYPO3 CMS 7, this icon will be removed in TYPO3 CMS 8'
				],
				'<span class="icon icon-size-small icon-state-default icon-actions-document-close">'
			],
		);
	}

	//
	// Tests for getIconForFileExtension
	//

	/**
	 * Tests the return of an icon for a file without extension
	 *
	 * @test
	 */
	public function getIconForFileWithNoFileTypeReturnsDefaultFileIcon() {
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-mimetypes-other-other">',
			$this->subject->getIconForFileExtension('')->render());
	}

	/**
	 * Tests the return of an icon for an unknown file type
	 *
	 * @test
	 */
	public function getIconForFileWithUnknownFileTypeReturnsDefaultFileIcon() {
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-mimetypes-other-other">',
			$this->subject->getIconForFileExtension('foo')->render());
	}

	/**
	 * Tests the return of an icon for a file with extension pdf
	 *
	 * @test
	 */
	public function getIconForFileWithFileTypePdfReturnsPdfIcon() {
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-mimetypes-pdf">',
			$this->subject->getIconForFileExtension('pdf')->render());
	}

	/**
	 * Tests the return of an icon for a file with extension png
	 *
	 * @test
	 */
	public function getIconForFileWithFileTypePngReturnsPngIcon() {
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-mimetypes-media-image">',
			$this->subject->getIconForFileExtension('png')->render());
	}

	/**
	 * @test
	 */
	public function getIconForResourceReturnsCorrectMarkupForFileResources() {
		$resourceProphecy = $this->prophesize(File::class);
		$resourceProphecy->isMissing()->willReturn(FALSE);
		$resourceProphecy->getExtension()->willReturn('pdf');

		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-mimetypes-pdf">',
			$this->subject->getIconForResource($resourceProphecy->reveal())->render());
	}

	//////////////////////////////////////////////
	// Tests concerning getIconForResource
	//////////////////////////////////////////////
	/**
	 * Tests the returns of no file
	 *
	 * @test
	 */
	public function getIconForResourceWithFileWithoutExtensionTypeReturnsOtherIcon() {
		$fileObject = $this->getTestSubjectFileObject('');
		$result = $this->subject->getIconForResource($fileObject)->render();
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-mimetypes-other-other">', $result);
	}

	/**
	 * Tests the returns of unknown file
	 *
	 * @test
	 */
	public function getIconForResourceWithUnknownFileTypeReturnsOtherIcon() {
		$fileObject = $this->getTestSubjectFileObject('foo');
		$result = $this->subject->getIconForResource($fileObject)->render();
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-mimetypes-other-other">', $result);
	}

	/**
	 * Tests the returns of file pdf
	 *
	 * @test
	 */
	public function getIconForResourceWithPdfReturnsPdfIcon() {
		$fileObject = $this->getTestSubjectFileObject('pdf');
		$result = $this->subject->getIconForResource($fileObject)->render();
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-mimetypes-pdf">', $result);
	}

	/**
	 * Tests the returns of file png
	 *
	 * @test
	 */
	public function getIconForResourceWithPngFileReturnsIcon() {
		$fileObject = $this->getTestSubjectFileObject('png');
		$result = $this->subject->getIconForResource($fileObject)->render();
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-mimetypes-media-image">', $result);
	}

	/**
	 * Tests the returns of normal folder
	 *
	 * @test
	 */
	public function getIconForResourceWithFolderReturnsFolderIcon() {
		$folderObject = $this->getTestSubjectFolderObject('/test');
		$result = $this->subject->getIconForResource($folderObject)->render();
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-apps-filetree-folder-default">', $result);
	}

	/**
	 * Tests the returns of open folder
	 *
	 * @test
	 */
	public function getIconForResourceWithOpenFolderReturnsOpenFolderIcon() {
		$folderObject = $this->getTestSubjectFolderObject('/test');
		$result = $this->subject->getIconForResource($folderObject, Icon::SIZE_DEFAULT, NULL,  array('folder-open' => TRUE))->render();
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-apps-filetree-folder-opened">', $result);
	}

	/**
	 * Tests the returns of root folder
	 *
	 * @test
	 */
	public function getIconForResourceWithRootFolderReturnsRootFolderIcon() {
		$folderObject = $this->getTestSubjectFolderObject('/');
		$result = $this->subject->getIconForResource($folderObject)->render();
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-apps-filetree-root">', $result);
	}

	/**
	 * Tests the returns of mount root
	 *
	 * @test
	 */
	public function getIconForResourceWithMountRootReturnsMountFolderIcon() {
		$folderObject = $this->getTestSubjectFolderObject('/mount');
		$result = $this->subject->getIconForResource($folderObject, Icon::SIZE_DEFAULT, NULL, array('mount-root' => TRUE))->render();
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-apps-filetree-mount">', $result);
	}

	//
	// Test for getIconForRecord
	//

	/**
	 * Tests the returns of NULL table + empty array
	 *
	 * @test
	 */
	public function getIconForRecordWithNullTableReturnsMissingIcon() {
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-default-not-found">',
			$this->subject->getIconForRecord('', array())->render());
	}

	/**
	 * Tests the returns of tt_content + empty record
	 *
	 * @test
	 */
	public function getIconForRecordWithEmptyRecordReturnsNormalIcon() {
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
		$result = $this->subject->getIconForRecord('tt_content', array())->render();
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-mimetypes-x-content-text">', $result);
	}

	/**
	 * Tests the returns of tt_content + mock record
	 *
	 * @test
	 */
	public function getIconForRecordWithMockRecordReturnsNormalIcon() {
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
		$result = $this->subject->getIconForRecord('tt_content', $this->mockRecord)->render();
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-mimetypes-x-content-text">', $result);
	}

	/**
	 * Tests the returns of tt_content + mock record of type 'list' (aka plugin)
	 *
	 * @test
	 */
	public function getIconForRecordWithMockRecordOfTypePluginReturnsPluginIcon() {
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
		$result = $this->subject->getIconForRecord('tt_content', $mockRecord)->render();
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-mimetypes-x-content-plugin">', $result);
	}

	/**
	 * Tests the returns of tt_content + mock record with hidden flag
	 *
	 * @test
	 */
	public function getIconForRecordWithMockRecordWithHiddenFlagReturnsNormalIconAndOverlay() {
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
		$result = $this->subject->getIconForRecord('tt_content', $mockRecord)->render();
		$this->assertContains('<span class="icon icon-size-default icon-state-default icon-mimetypes-x-content-text">', $result);
		$this->assertContains('<span class="icon-overlay icon-overlay-hidden">', $result);
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
		$mockedStorage->expects($this->any())->method('isBrowsable')->will($this->returnValue(TRUE));
		return new \TYPO3\CMS\Core\Resource\Folder($mockedStorage, $identifier, $identifier);
	}

}
