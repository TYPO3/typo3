<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class IconFactoryTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var IconFactory
     */
    protected $subject;

    protected string $notRegisteredIconIdentifier = 'my-super-unregistered-identifier';
    protected string $registeredIconIdentifier = 'actions-close';
    protected string $registeredSpinningIconIdentifier = 'spinning-icon';

    /**
     * @var \TYPO3\CMS\Core\Imaging\IconRegistry
     */
    protected $iconRegistryMock;

    /**
     * Simulate a tt_content record
     */
    protected array $mockRecord = [
        'header' => 'dummy content header',
        'uid' => '1',
        'pid' => '1',
        'image' => '',
        'hidden' => '0',
        'starttime' => '0',
        'endtime' => '0',
        'fe_group' => '',
        'CType' => 'text',
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
    protected function setUp(): void
    {
        parent::setUp();
        $this->iconRegistryMock = $this->prophesize(IconRegistry::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::any())->willReturnArgument(0);
        $container = $this->prophesize(ContainerInterface::class);
        $cacheProphecy = $this->prophesize(FrontendInterface::class);
        $cacheProphecy->get(Argument::any())->willReturn(false);
        $cacheProphecy->set(Argument::any(), Argument::any())->willReturn();

        $container->has(FontawesomeIconProvider::class)->willReturn(true);
        $container->get(FontawesomeIconProvider::class)->willReturn(new FontawesomeIconProvider($cacheProphecy->reveal()));

        $this->subject = new IconFactory($eventDispatcher->reveal(), $this->iconRegistryMock->reveal(), $container->reveal());

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
                'additionalClasses' => 'fa-fw',
            ],
        ]);
    }

    /**
     * DataProvider for icon sizes
     *
     * @return array
     */
    public function differentSizesDataProvider(): array
    {
        return [
            ['size ' . Icon::SIZE_SMALL => ['input' => Icon::SIZE_SMALL, 'expected' => Icon::SIZE_SMALL]],
            ['size ' . Icon::SIZE_DEFAULT => ['input' => Icon::SIZE_DEFAULT, 'expected' => Icon::SIZE_DEFAULT]],
            ['size ' . Icon::SIZE_LARGE => ['input' => Icon::SIZE_LARGE, 'expected' => Icon::SIZE_LARGE]],
        ];
    }

    /**
     * @test
     */
    public function getIconReturnsIconWithCorrectMarkupWrapperIfRegisteredIconIdentifierIsUsed(): void
    {
        self::assertStringContainsString(
            '<span class="icon-markup">',
            $this->subject->getIcon($this->registeredIconIdentifier)->render()
        );
    }

    /**
     * @test
     */
    public function getIconByIdentifierReturnsIconWithCorrectMarkupIfRegisteredIconIdentifierIsUsed(): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-actions-close" data-identifier="actions-close">',
            $this->subject->getIcon($this->registeredIconIdentifier)->render()
        );
    }

    /**
     * @test
     * @dataProvider differentSizesDataProvider
     */
    public function getIconByIdentifierAndSizeReturnsIconWithCorrectMarkupIfRegisteredIconIdentifierIsUsed($size): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-' . $size['expected'] . ' icon-state-default icon-actions-close" data-identifier="actions-close">',
            $this->subject->getIcon($this->registeredIconIdentifier, $size['input'])->render()
        );
    }

    /**
     * @test
     * @dataProvider differentSizesDataProvider
     */
    public function getIconByIdentifierAndSizeAndWithOverlayReturnsIconWithCorrectOverlayMarkupIfRegisteredIconIdentifierIsUsed($size): void
    {
        self::assertStringContainsString(
            '<span class="icon-overlay icon-overlay-readonly">',
            $this->subject->getIcon($this->registeredIconIdentifier, $size['input'], 'overlay-readonly')->render()
        );
    }

    /**
     * @test
     */
    public function getIconReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed(): void
    {
        $this->iconRegistryMock->isRegistered(Argument::any())->willReturn(false);
        $this->iconRegistryMock->getDefaultIconIdentifier(Argument::any())->willReturn('default-not-found');
        $this->iconRegistryMock->getIconConfigurationByIdentifier('default-not-found')->willReturn([
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'times-circle',
                'additionalClasses' => 'fa-fw',
            ],
        ]);
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-default-not-found" data-identifier="default-not-found">',
            $this->subject->getIcon($this->notRegisteredIconIdentifier)->render()
        );
    }

    /**
     * @test
     * @dataProvider differentSizesDataProvider
     */
    public function getIconByIdentifierAndSizeReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed(array $size): void
    {
        $this->iconRegistryMock->isRegistered(Argument::any())->willReturn(false);
        $this->iconRegistryMock->getDefaultIconIdentifier(Argument::any())->willReturn('default-not-found');
        $this->iconRegistryMock->getIconConfigurationByIdentifier('default-not-found')->willReturn([
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'times-circle',
                'additionalClasses' => 'fa-fw',
            ],
        ]);
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-' . $size['expected'] . ' icon-state-default icon-default-not-found" data-identifier="default-not-found">',
            $this->subject->getIcon($this->notRegisteredIconIdentifier, $size['input'])->render()
        );
    }

    /**
     * @test
     */
    public function getIconReturnsCorrectMarkupIfIconIsRegisteredAsSpinningIcon(): void
    {
        $this->iconRegistryMock->getIconConfigurationByIdentifier($this->registeredSpinningIconIdentifier)->willReturn([
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'times-circle',
                'additionalClasses' => 'fa-fw',
                'spinning' => true,
            ],
        ]);
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-' . $this->registeredSpinningIconIdentifier . ' icon-spin" data-identifier="spinning-icon">',
            $this->subject->getIcon($this->registeredSpinningIconIdentifier)->render()
        );
    }

    /**
     * @test
     * @dataProvider differentSizesDataProvider
     */
    public function getIconByIdentifierAndSizeAndOverlayReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed(array $size): void
    {
        self::assertStringContainsString(
            '<span class="icon-overlay icon-overlay-readonly">',
            $this->subject->getIcon($this->notRegisteredIconIdentifier, $size['input'], 'overlay-readonly')->render()
        );
    }

    /**
     * @test
     */
    public function getIconThrowsExceptionIfInvalidSizeIsGiven(): void
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
    public function getIconForFileWithNoFileTypeReturnsDefaultFileIcon(): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other">',
            $this->subject->getIconForFileExtension('')->render()
        );
    }

    /**
     * Tests the return of an icon for an unknown file type
     *
     * @test
     */
    public function getIconForFileWithUnknownFileTypeReturnsDefaultFileIcon(): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other">',
            $this->subject->getIconForFileExtension('foo')->render()
        );
    }

    /**
     * Tests the return of an icon for a file with extension pdf
     *
     * @test
     */
    public function getIconForFileWithFileTypePdfReturnsPdfIcon(): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-pdf" data-identifier="mimetypes-pdf">',
            $this->subject->getIconForFileExtension('pdf')->render()
        );
    }

    /**
     * Tests the return of an icon for a file with extension png
     *
     * @test
     */
    public function getIconForFileWithFileTypePngReturnsPngIcon(): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image">',
            $this->subject->getIconForFileExtension('png')->render()
        );
    }

    /**
     * @test
     */
    public function getIconForResourceReturnsCorrectMarkupForFileResources(): void
    {
        $resourceProphecy = $this->prophesize(File::class);
        $resourceProphecy->isMissing()->willReturn(false);
        $resourceProphecy->getExtension()->willReturn('pdf');
        $resourceProphecy->getMimeType()->willReturn('');

        self::assertStringContainsString(
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
    public function getIconForResourceWithFileWithoutExtensionTypeReturnsOtherIcon(): void
    {
        $fileObject = $this->getTestSubjectFileObject('');
        $result = $this->subject->getIconForResource($fileObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other">', $result);
    }

    /**
     * Tests the returns of unknown file
     *
     * @test
     */
    public function getIconForResourceWithUnknownFileTypeReturnsOtherIcon(): void
    {
        $fileObject = $this->getTestSubjectFileObject('foo');
        $result = $this->subject->getIconForResource($fileObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other">', $result);
    }

    /**
     * Tests the returns of file pdf
     *
     * @test
     */
    public function getIconForResourceWithPdfReturnsPdfIcon(): void
    {
        $fileObject = $this->getTestSubjectFileObject('pdf');
        $result = $this->subject->getIconForResource($fileObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-pdf" data-identifier="mimetypes-pdf">', $result);
    }

    /**
     * Tests the returns of file pdf with known mime-type
     *
     * @test
     */
    public function getIconForResourceWithMimeTypeApplicationPdfReturnsPdfIcon(): void
    {
        $fileObject = $this->getTestSubjectFileObject('pdf', 'application/pdf');
        $result = $this->subject->getIconForResource($fileObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-pdf" data-identifier="mimetypes-pdf">', $result);
    }

    /**
     * Tests the returns of file with custom image mime-type
     *
     * @test
     */
    public function getIconForResourceWithCustomImageMimeTypeReturnsImageIcon(): void
    {
        $fileObject = $this->getTestSubjectFileObject('custom', 'image/my-custom-extension');
        $result = $this->subject->getIconForResource($fileObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image">', $result);
    }

    /**
     * Tests the returns of file png
     *
     * @test
     */
    public function getIconForResourceWithPngFileReturnsIcon(): void
    {
        $fileObject = $this->getTestSubjectFileObject('png', 'image/png');
        $result = $this->subject->getIconForResource($fileObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image">', $result);
    }

    /**
     * Tests the returns of normal folder
     *
     * @test
     */
    public function getIconForResourceWithFolderReturnsFolderIcon(): void
    {
        $folderObject = $this->getTestSubjectFolderObject('/test');
        $result = $this->subject->getIconForResource($folderObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-apps-filetree-folder-default" data-identifier="apps-filetree-folder-default">', $result);
    }

    /**
     * Tests the returns of open folder
     *
     * @test
     */
    public function getIconForResourceWithOpenFolderReturnsOpenFolderIcon(): void
    {
        $folderObject = $this->getTestSubjectFolderObject('/test');
        $result = $this->subject->getIconForResource($folderObject, Icon::SIZE_DEFAULT, null, ['folder-open' => true])->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-apps-filetree-folder-opened" data-identifier="apps-filetree-folder-opened">', $result);
    }

    /**
     * Tests the returns of root folder
     *
     * @test
     */
    public function getIconForResourceWithRootFolderReturnsRootFolderIcon(): void
    {
        $folderObject = $this->getTestSubjectFolderObject('/');
        $result = $this->subject->getIconForResource($folderObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-apps-filetree-root" data-identifier="apps-filetree-root">', $result);
    }

    /**
     * Tests the returns of mount root
     *
     * @test
     */
    public function getIconForResourceWithMountRootReturnsMountFolderIcon(): void
    {
        $folderObject = $this->getTestSubjectFolderObject('/mount');
        $result = $this->subject->getIconForResource($folderObject, Icon::SIZE_DEFAULT, null, ['mount-root' => true])->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-apps-filetree-mount" data-identifier="apps-filetree-mount">', $result);
    }

    //
    // Test for getIconForRecord
    //

    /**
     * Tests the returns of NULL table + empty array
     *
     * @test
     */
    public function getIconForRecordWithNullTableReturnsMissingIcon(): void
    {
        $GLOBALS['TCA']['']['ctrl'] = [];
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-default icon-state-default icon-default-not-found" data-identifier="default-not-found">',
            $this->subject->getIconForRecord('', [])->render()
        );
    }

    /**
     * Tests the returns of tt_content + empty record
     *
     * @test
     */
    public function getIconForRecordWithEmptyRecordReturnsNormalIcon(): void
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
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text">', $result);
    }

    /**
     * Tests the returns of tt_content + mock record
     *
     * @test
     */
    public function getIconForRecordWithMockRecordReturnsNormalIcon(): void
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
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text">', $result);
    }

    /**
     * Tests the returns of tt_content + mock record of type 'list' (aka plugin)
     *
     * @test
     */
    public function getIconForRecordWithMockRecordOfTypePluginReturnsPluginIcon(): void
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
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-x-content-plugin" data-identifier="mimetypes-x-content-plugin">', $result);
    }

    /**
     * Tests the returns of tt_content + mock record with hidden flag
     *
     * @test
     */
    public function getIconForRecordWithMockRecordWithHiddenFlagReturnsNormalIconAndOverlay(): void
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
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-default icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text">', $result);
        self::assertStringContainsString('<span class="icon-overlay icon-overlay-hidden">', $result);
    }

    /**
     * Create file object to use as test subject
     *
     * @param string $extension
     * @param string $mimeType
     * @return \TYPO3\CMS\Core\Resource\File
     */
    protected function getTestSubjectFileObject(string $extension, string $mimeType = ''): File
    {
        $mockedStorage = $this->createMock(ResourceStorage::class);
        $mockedFile = $this->getMockBuilder(File::class)
            ->setConstructorArgs([['identifier' => '', 'name' => ''], $mockedStorage])
            ->getMock();
        $mockedFile->expects(self::atMost(1))->method('getExtension')->willReturn($extension);
        $mockedFile->expects(self::atLeastOnce())->method('getMimeType')->willReturn($mimeType);
        return $mockedFile;
    }

    /**
     * Create folder object to use as test subject
     *
     * @param string $identifier
     * @return \TYPO3\CMS\Core\Resource\Folder
     */
    protected function getTestSubjectFolderObject(string $identifier): Folder
    {
        $mockedStorage = $this->createMock(ResourceStorage::class);
        $mockedStorage->expects(self::any())->method('getRootLevelFolder')->willReturn(
            new Folder($mockedStorage, '/', '/')
        );
        $mockedStorage->expects(self::any())->method('checkFolderActionPermission')->willReturn(true);
        $mockedStorage->expects(self::any())->method('isBrowsable')->willReturn(true);
        return new Folder($mockedStorage, $identifier, $identifier);
    }
}
