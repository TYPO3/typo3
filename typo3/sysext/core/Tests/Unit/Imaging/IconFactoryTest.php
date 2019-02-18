<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher as SignalSlotDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class IconFactoryTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var IconFactory
     */
    protected $subject;

    /**
     * @var string
     */
    protected $notRegisteredIconIdentifier = 'my-super-unregistered-identifier';

    /**
     * @var string
     */
    protected $registeredIconIdentifier = 'actions-close';

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
     * Set up
     */
    protected function setUp()
    {
        $this->iconRegistryMock = $this->prophesize(IconRegistry::class);
        $signalSlotDispatcherMock = $this->prophesize(SignalSlotDispatcher::class);
        $signalSlotDispatcherMock->dispatch(Argument::any(), Argument::any(), Argument::type('array'))->willReturnArgument(2);
        GeneralUtility::setSingletonInstance(SignalSlotDispatcher::class, $signalSlotDispatcherMock->reveal());

        $this->subject = new IconFactory($this->iconRegistryMock->reveal());

        $this->iconRegistryMock->isRegistered('tcarecords--default')->willReturn(false);
        $this->iconRegistryMock->isRegistered(Argument::any())->willReturn(true);
        $this->iconRegistryMock->isDeprecated(Argument::any())->willReturn(false);
        $this->iconRegistryMock->getDefaultIconIdentifier()->willReturn('default-not-found');
        $this->iconRegistryMock->getIconIdentifierForMimeType('application/pdf')->willReturn('mimetypes-pdf');
        $this->iconRegistryMock->getIconIdentifierForMimeType('image/*')->willReturn('mimetypes-media-image');
        $this->iconRegistryMock->getIconIdentifierForMimeType(Argument::any())->willReturn(null);
        $this->iconRegistryMock->getIconIdentifierForFileExtension(Argument::any())->willReturn('mimetypes-other-other');
        $this->iconRegistryMock->getIconIdentifierForFileExtension('foo')->willReturn('mimetypes-other-other');
        $this->iconRegistryMock->getIconIdentifierForFileExtension('pdf')->willReturn('mimetypes-pdf');
        $this->iconRegistryMock->getIconIdentifierForFileExtension('png')->willReturn('mimetypes-media-image');
        $this->iconRegistryMock->getIconConfigurationByIdentifier(Argument::any())->willReturn([
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'times',
                'additionalClasses' => 'fa-fw'
            ]
        ]);
    }

    /**
     * DataProvider for icon sizes
     *
     * @return array
     */
    public function differentSizesDataProvider()
    {
        return [
            ['size ' . Icon::SIZE_SMALL => ['input' => Icon::SIZE_SMALL, 'expected' => Icon::SIZE_SMALL]],
            ['size ' . Icon::SIZE_DEFAULT => ['input' => Icon::SIZE_DEFAULT, 'expected' => Icon::SIZE_DEFAULT]],
            ['size ' . Icon::SIZE_LARGE => ['input' => Icon::SIZE_LARGE, 'expected' => Icon::SIZE_LARGE]]
        ];
    }

    /**
     * @test
     */
    public function getIconReturnsIconWithCorrectMarkupWrapperIfRegisteredIconIdentifierIsUsed()
    {
        $this->assertContains(
            '<span class="icon-markup">',
            $this->subject->getIcon($this->registeredIconIdentifier)->render()
        );
    }

    /**
     * @test
     */
    public function getIconByIdentifierReturnsIconWithCorrectMarkupIfRegisteredIconIdentifierIsUsed()
    {
        $this->assertContains(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-actions-close" data-identifier="actions-close">',
            $this->subject->getIcon($this->registeredIconIdentifier)->render()
        );
    }

    /**
     * @test
     * @dataProvider differentSizesDataProvider
     */
    public function getIconByIdentifierAndSizeReturnsIconWithCorrectMarkupIfRegisteredIconIdentifierIsUsed($size)
    {
        $this->assertContains(
            '<span class="t3js-icon icon icon-size-' . $size['expected'] . ' icon-state-default icon-actions-close" data-identifier="actions-close">',
            $this->subject->getIcon($this->registeredIconIdentifier, $size['input'])->render()
        );
    }

    /**
     * @test
     * @dataProvider differentSizesDataProvider
     */
    public function getIconByIdentifierAndSizeAndWithOverlayReturnsIconWithCorrectOverlayMarkupIfRegisteredIconIdentifierIsUsed($size)
    {
        $this->assertContains(
            '<span class="icon-overlay icon-overlay-readonly">',
            $this->subject->getIcon($this->registeredIconIdentifier, $size['input'], 'overlay-readonly')->render()
        );
    }

    /**
     * @test
     */
    public function getIconReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed()
    {
        $this->iconRegistryMock->isRegistered(Argument::any())->willReturn(false);
        $this->iconRegistryMock->getDefaultIconIdentifier(Argument::any())->willReturn('default-not-found');
        $this->iconRegistryMock->getIconConfigurationByIdentifier('default-not-found')->willReturn([
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'times-circle',
                'additionalClasses' => 'fa-fw'
            ]
        ]);
        $this->assertContains(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-default-not-found" data-identifier="default-not-found">',
            $this->subject->getIcon($this->notRegisteredIconIdentifier)->render()
        );
    }

    /**
     * @test
     * @dataProvider differentSizesDataProvider
     */
    public function getIconByIdentifierAndSizeReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed($size)
    {
        $this->iconRegistryMock->isRegistered(Argument::any())->willReturn(false);
        $this->iconRegistryMock->getDefaultIconIdentifier(Argument::any())->willReturn('default-not-found');
        $this->iconRegistryMock->getIconConfigurationByIdentifier('default-not-found')->willReturn([
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'times-circle',
                'additionalClasses' => 'fa-fw'
            ]
        ]);
        $this->assertContains(
            '<span class="t3js-icon icon icon-size-' . $size['expected'] . ' icon-state-default icon-default-not-found" data-identifier="default-not-found">',
            $this->subject->getIcon($this->notRegisteredIconIdentifier, $size['input'])->render()
        );
    }

    /**
     * @test
     */
    public function getIconReturnsCorrectMarkupIfIconIsRegisteredAsSpinningIcon()
    {
        $this->iconRegistryMock->getIconConfigurationByIdentifier($this->registeredSpinningIconIdentifier)->willReturn([
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'times-circle',
                'additionalClasses' => 'fa-fw',
                'spinning' => true
            ]
        ]);
        $this->assertContains(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-' . $this->registeredSpinningIconIdentifier . ' icon-spin" data-identifier="spinning-icon">',
            $this->subject->getIcon($this->registeredSpinningIconIdentifier)->render()
        );
    }

    /**
     * @test
     * @dataProvider differentSizesDataProvider
     * @param string $size
     */
    public function getIconByIdentifierAndSizeAndOverlayReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed($size)
    {
        $this->assertContains(
            '<span class="icon-overlay icon-overlay-readonly">',
            $this->subject->getIcon($this->notRegisteredIconIdentifier, $size['input'], 'overlay-readonly')->render()
        );
    }

    /**
     * @test
     */
    public function getIconThrowsExceptionIfInvalidSizeIsGiven()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->subject->getIcon($this->registeredIconIdentifier, 'foo')->render();
    }

    //
    // Tests for getIconForFileExtension
    //

    /**
     * Tests the return of an icon for a file without extension
     *
     * @test
     */
    public function getIconForFileWithNoFileTypeReturnsDefaultFileIcon()
    {
        $this->assertContains(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other">',
            $this->subject->getIconForFileExtension('')->render()
        );
    }

    /**
     * Tests the return of an icon for an unknown file type
     *
     * @test
     */
    public function getIconForFileWithUnknownFileTypeReturnsDefaultFileIcon()
    {
        $this->assertContains(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other">',
            $this->subject->getIconForFileExtension('foo')->render()
        );
    }

    /**
     * Tests the return of an icon for a file with extension pdf
     *
     * @test
     */
    public function getIconForFileWithFileTypePdfReturnsPdfIcon()
    {
        $this->assertContains(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-pdf" data-identifier="mimetypes-pdf">',
            $this->subject->getIconForFileExtension('pdf')->render()
        );
    }

    /**
     * Tests the return of an icon for a file with extension png
     *
     * @test
     */
    public function getIconForFileWithFileTypePngReturnsPngIcon()
    {
        $this->assertContains(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image">',
            $this->subject->getIconForFileExtension('png')->render()
        );
    }

    /**
     * @test
     */
    public function getIconForResourceReturnsCorrectMarkupForFileResources()
    {
        $resourceProphecy = $this->prophesize(File::class);
        $resourceProphecy->isMissing()->willReturn(false);
        $resourceProphecy->getExtension()->willReturn('pdf');
        $resourceProphecy->getMimeType()->willReturn('');

        $this->assertContains(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-pdf" data-identifier="mimetypes-pdf">',
            $this->subject->getIconForResource($resourceProphecy->reveal())->render()
        );
    }

    //////////////////////////////////////////////
    // Tests concerning getIconForResource
    //////////////////////////////////////////////
    /**
     * Tests the returns of no file
     *
     * @test
     */
    public function getIconForResourceWithFileWithoutExtensionTypeReturnsOtherIcon()
    {
        $fileObject = $this->getTestSubjectFileObject('');
        $result = $this->subject->getIconForResource($fileObject)->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other">', $result);
    }

    /**
     * Tests the returns of unknown file
     *
     * @test
     */
    public function getIconForResourceWithUnknownFileTypeReturnsOtherIcon()
    {
        $fileObject = $this->getTestSubjectFileObject('foo');
        $result = $this->subject->getIconForResource($fileObject)->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other">', $result);
    }

    /**
     * Tests the returns of file pdf
     *
     * @test
     */
    public function getIconForResourceWithPdfReturnsPdfIcon()
    {
        $fileObject = $this->getTestSubjectFileObject('pdf');
        $result = $this->subject->getIconForResource($fileObject)->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-pdf" data-identifier="mimetypes-pdf">', $result);
    }

    /**
     * Tests the returns of file pdf with known mime-type
     *
     * @test
     */
    public function getIconForResourceWithMimeTypeApplicationPdfReturnsPdfIcon()
    {
        $fileObject = $this->getTestSubjectFileObject('pdf', 'application/pdf');
        $result = $this->subject->getIconForResource($fileObject)->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-pdf" data-identifier="mimetypes-pdf">', $result);
    }

    /**
     * Tests the returns of file with custom image mime-type
     *
     * @test
     */
    public function getIconForResourceWithCustomImageMimeTypeReturnsImageIcon()
    {
        $fileObject = $this->getTestSubjectFileObject('custom', 'image/my-custom-extension');
        $result = $this->subject->getIconForResource($fileObject)->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image">', $result);
    }

    /**
     * Tests the returns of file png
     *
     * @test
     */
    public function getIconForResourceWithPngFileReturnsIcon()
    {
        $fileObject = $this->getTestSubjectFileObject('png', 'image/png');
        $result = $this->subject->getIconForResource($fileObject)->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image">', $result);
    }

    /**
     * Tests the returns of normal folder
     *
     * @test
     */
    public function getIconForResourceWithFolderReturnsFolderIcon()
    {
        $folderObject = $this->getTestSubjectFolderObject('/test');
        $result = $this->subject->getIconForResource($folderObject)->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-apps-filetree-folder-default" data-identifier="apps-filetree-folder-default">', $result);
    }

    /**
     * Tests the returns of open folder
     *
     * @test
     */
    public function getIconForResourceWithOpenFolderReturnsOpenFolderIcon()
    {
        $folderObject = $this->getTestSubjectFolderObject('/test');
        $result = $this->subject->getIconForResource($folderObject, Icon::SIZE_DEFAULT, null, ['folder-open' => true])->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-apps-filetree-folder-opened" data-identifier="apps-filetree-folder-opened">', $result);
    }

    /**
     * Tests the returns of root folder
     *
     * @test
     */
    public function getIconForResourceWithRootFolderReturnsRootFolderIcon()
    {
        $folderObject = $this->getTestSubjectFolderObject('/');
        $result = $this->subject->getIconForResource($folderObject)->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-apps-filetree-root" data-identifier="apps-filetree-root">', $result);
    }

    /**
     * Tests the returns of mount root
     *
     * @test
     */
    public function getIconForResourceWithMountRootReturnsMountFolderIcon()
    {
        $folderObject = $this->getTestSubjectFolderObject('/mount');
        $result = $this->subject->getIconForResource($folderObject, Icon::SIZE_DEFAULT, null, ['mount-root' => true])->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-apps-filetree-mount" data-identifier="apps-filetree-mount">', $result);
    }

    //
    // Test for getIconForRecord
    //

    /**
     * Tests the returns of NULL table + empty array
     *
     * @test
     */
    public function getIconForRecordWithNullTableReturnsMissingIcon()
    {
        $GLOBALS['TCA']['']['ctrl'] = [];
        $this->assertContains(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-default-not-found" data-identifier="default-not-found">',
            $this->subject->getIconForRecord('', [])->render()
        );
    }

    /**
     * Tests the returns of tt_content + empty record
     *
     * @test
     */
    public function getIconForRecordWithEmptyRecordReturnsNormalIcon()
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'ctrl' => [
                    'typeicon_column' => 'CType',
                    'typeicon_classes' => [
                        'default' => 'mimetypes-x-content-text',
                    ],
                ],
            ],
        ];
        $result = $this->subject->getIconForRecord('tt_content', [])->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text">', $result);
    }

    /**
     * Tests the returns of tt_content + mock record
     *
     * @test
     */
    public function getIconForRecordWithMockRecordReturnsNormalIcon()
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'ctrl' => [
                    'typeicon_column' => 'CType',
                    'typeicon_classes' => [
                        'default' => '',
                        'text' => 'mimetypes-x-content-text',
                    ],
                ],
            ],
        ];
        $result = $this->subject->getIconForRecord('tt_content', $this->mockRecord)->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text">', $result);
    }

    /**
     * Tests the returns of tt_content + mock record of type 'list' (aka plugin)
     *
     * @test
     */
    public function getIconForRecordWithMockRecordOfTypePluginReturnsPluginIcon()
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'ctrl' => [
                    'typeicon_column' => 'CType',
                    'typeicon_classes' => [
                        'default' => '',
                        'list' => 'mimetypes-x-content-plugin',
                    ],
                ],
            ],
        ];
        $mockRecord = $this->mockRecord;
        $mockRecord['CType'] = 'list';
        $result = $this->subject->getIconForRecord('tt_content', $mockRecord)->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-x-content-plugin" data-identifier="mimetypes-x-content-plugin">', $result);
    }

    /**
     * Tests the returns of tt_content + mock record with hidden flag
     *
     * @test
     */
    public function getIconForRecordWithMockRecordWithHiddenFlagReturnsNormalIconAndOverlay()
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                    ],
                    'typeicon_column' => 'CType',
                    'typeicon_classes' => [
                        'default' => '',
                        'text' => 'mimetypes-x-content-text',
                    ],
                ],
            ],
        ];
        $mockRecord = $this->mockRecord;
        $mockRecord['hidden'] = '1';
        $result = $this->subject->getIconForRecord('tt_content', $mockRecord)->render();
        $this->assertContains('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text">', $result);
        $this->assertContains('<span class="icon-overlay icon-overlay-hidden">', $result);
    }

    /**
     * Create file object to use as test subject
     *
     * @param string $extension
     * @param string $mimeType
     * @return \TYPO3\CMS\Core\Resource\File
     */
    protected function getTestSubjectFileObject($extension, $mimeType = '')
    {
        $mockedStorage = $this->createMock(ResourceStorage::class);
        $mockedFile = $this->getMockBuilder(File::class)
            ->setConstructorArgs([['identifier' => '', 'name' => ''], $mockedStorage])
            ->getMock();
        $mockedFile->expects($this->atMost(1))->method('getExtension')->will($this->returnValue($extension));
        $mockedFile->expects($this->atLeastOnce())->method('getMimeType')->will($this->returnValue($mimeType));
        return $mockedFile;
    }

    /**
     * Create folder object to use as test subject
     *
     * @param string $identifier
     * @return \TYPO3\CMS\Core\Resource\Folder
     */
    protected function getTestSubjectFolderObject($identifier)
    {
        $mockedStorage = $this->createMock(ResourceStorage::class);
        $mockedStorage->expects($this->any())->method('getRootLevelFolder')->will($this->returnValue(
            new Folder($mockedStorage, '/', '/')
        ));
        $mockedStorage->expects($this->any())->method('checkFolderActionPermission')->will($this->returnValue(true));
        $mockedStorage->expects($this->any())->method('isBrowsable')->will($this->returnValue(true));
        return new Folder($mockedStorage, $identifier, $identifier);
    }
}
