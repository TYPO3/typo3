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

namespace TYPO3\CMS\Core\Tests\Functional\Imaging;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Imaging\Event\ModifyRecordOverlayIconIdentifierEvent;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class IconFactoryTest extends FunctionalTestCase
{
    private IconFactory $subject;
    private string $notRegisteredIconIdentifier = 'my-super-unregistered-identifier';
    private string $registeredIconIdentifier = 'actions-close';
    private string $registeredSpinningIconIdentifier = 'spinning-icon';

    /**
     * Simulate a tt_content record
     */
    private array $mockRecord = [
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->get(IconFactory::class);
    }

    /**
     * DataProvider for icon sizes
     */
    public static function differentSizesDataProvider(): array
    {
        return [
            'size ' . IconSize::DEFAULT->name => [
                'size' => IconSize::DEFAULT,
                'expected' => IconSize::DEFAULT->value,
            ],
            'size ' . IconSize::SMALL->name => [
                'size' => IconSize::SMALL,
                'expected' => IconSize::SMALL->value,
            ],
            'size ' . IconSize::MEDIUM->name => [
                'size' => IconSize::MEDIUM,
                'expected' => IconSize::MEDIUM->value,
            ],
            'size ' . IconSize::LARGE->name => [
                'size' => IconSize::LARGE,
                'expected' => IconSize::LARGE->value,
            ],
        ];
    }

    #[Test]
    public function getIconReturnsIconWithCorrectMarkupWrapperIfRegisteredIconIdentifierIsUsed(): void
    {
        self::assertStringContainsString(
            '<span class="icon-markup">',
            $this->subject->getIcon($this->registeredIconIdentifier)->render()
        );
    }

    #[Test]
    public function getIconByIdentifierReturnsIconWithCorrectMarkupIfRegisteredIconIdentifierIsUsed(): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-medium icon-state-default icon-actions-close" data-identifier="actions-close" aria-hidden="true">',
            $this->subject->getIcon($this->registeredIconIdentifier)->render()
        );
    }

    #[DataProvider('differentSizesDataProvider')]
    #[Test]
    public function getIconByIdentifierAndSizeReturnsIconWithCorrectMarkupIfRegisteredIconIdentifierIsUsed(IconSize $size, string $expected): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-' . $expected . ' icon-state-default icon-actions-close" data-identifier="actions-close" aria-hidden="true">',
            $this->subject->getIcon($this->registeredIconIdentifier, $size)->render()
        );
    }

    #[DataProvider('differentSizesDataProvider')]
    #[Test]
    public function getIconByIdentifierAndSizeAndWithOverlayReturnsIconWithCorrectOverlayMarkupIfRegisteredIconIdentifierIsUsed(IconSize $size, string $expected): void
    {
        self::assertStringContainsString(
            '<span class="icon-overlay icon-overlay-readonly">',
            $this->subject->getIcon($this->registeredIconIdentifier, $size, 'overlay-readonly')->render()
        );
    }

    #[Test]
    public function getIconReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed(): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-medium icon-state-default icon-default-not-found" data-identifier="default-not-found" aria-hidden="true">',
            $this->subject->getIcon($this->notRegisteredIconIdentifier)->render()
        );
    }

    #[DataProvider('differentSizesDataProvider')]
    #[Test]
    public function getIconByIdentifierAndSizeReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed(IconSize $size, string $expected): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-' . $expected . ' icon-state-default icon-default-not-found" data-identifier="default-not-found" aria-hidden="true">',
            $this->subject->getIcon($this->notRegisteredIconIdentifier, $size)->render()
        );
    }

    #[Test]
    public function getIconReturnsCorrectMarkupIfIconIsRegisteredAsSpinningIcon(): void
    {
        $iconRegistry = $this->get(IconRegistry::class);
        $iconRegistry->registerIcon(
            $this->registeredSpinningIconIdentifier,
            SvgIconProvider::class,
            [
                'source' => __DIR__ . '/Fixtures/file.svg',
                'spinning' => true,
            ]
        );
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-medium icon-state-default icon-' . $this->registeredSpinningIconIdentifier . ' icon-spin" data-identifier="spinning-icon" aria-hidden="true">',
            $this->subject->getIcon($this->registeredSpinningIconIdentifier)->render()
        );
    }

    #[DataProvider('differentSizesDataProvider')]
    #[Test]
    public function getIconByIdentifierAndSizeAndOverlayReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed(IconSize $size, string $expected): void
    {
        self::assertStringContainsString(
            '<span class="icon-overlay icon-overlay-readonly">',
            $this->subject->getIcon($this->notRegisteredIconIdentifier, $size, 'overlay-readonly')->render()
        );
    }

    //
    // Tests for getIconForFileExtension
    //
    /**
     * Tests the return of an icon for a file without extension
     */
    #[Test]
    public function getIconForFileWithNoFileTypeReturnsDefaultFileIcon(): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other" aria-hidden="true">',
            $this->subject->getIconForFileExtension('')->render()
        );
    }

    /**
     * Tests the return of an icon for an unknown file type
     */
    #[Test]
    public function getIconForFileWithUnknownFileTypeReturnsDefaultFileIcon(): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other" aria-hidden="true">',
            $this->subject->getIconForFileExtension('foo')->render()
        );
    }

    /**
     * Tests the return of an icon for a file with extension pdf
     */
    #[Test]
    public function getIconForFileWithFileTypePdfReturnsPdfIcon(): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-pdf" data-identifier="mimetypes-pdf" aria-hidden="true">',
            $this->subject->getIconForFileExtension('pdf')->render()
        );
    }

    /**
     * Tests the return of an icon for a file with extension png
     */
    #[Test]
    public function getIconForFileWithFileTypePngReturnsPngIcon(): void
    {
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image" aria-hidden="true">',
            $this->subject->getIconForFileExtension('png')->render()
        );
    }

    #[Test]
    public function getIconForResourceReturnsCorrectMarkupForFileResources(): void
    {
        $resourceMock = $this->createMock(File::class);
        $resourceMock->method('isMissing')->willReturn(false);
        $resourceMock->method('getExtension')->willReturn('pdf');
        $resourceMock->method('getMimeType')->willReturn('');

        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-pdf" data-identifier="mimetypes-pdf" aria-hidden="true">',
            $this->subject->getIconForResource($resourceMock)->render()
        );
    }

    //////////////////////////////////////////////
    // Tests concerning getIconForResource
    //////////////////////////////////////////////
    /**
     * Tests the returns of no file
     */
    #[Test]
    public function getIconForResourceWithFileWithoutExtensionTypeReturnsOtherIcon(): void
    {
        $fileObject = $this->getTestSubjectFileObject('');
        $result = $this->subject->getIconForResource($fileObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other" aria-hidden="true">', $result);
    }

    /**
     * Tests the returns of unknown file
     */
    #[Test]
    public function getIconForResourceWithUnknownFileTypeReturnsOtherIcon(): void
    {
        $fileObject = $this->getTestSubjectFileObject('foo');
        $result = $this->subject->getIconForResource($fileObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other" aria-hidden="true">', $result);
    }

    /**
     * Tests the returns of file pdf
     */
    #[Test]
    public function getIconForResourceWithPdfReturnsPdfIcon(): void
    {
        $fileObject = $this->getTestSubjectFileObject('pdf');
        $result = $this->subject->getIconForResource($fileObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-pdf" data-identifier="mimetypes-pdf" aria-hidden="true">', $result);
    }

    /**
     * Tests the returns of file pdf with known mime-type
     */
    #[Test]
    public function getIconForResourceWithMimeTypeApplicationPdfReturnsPdfIcon(): void
    {
        $fileObject = $this->getTestSubjectFileObject('pdf', 'application/pdf');
        $result = $this->subject->getIconForResource($fileObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-pdf" data-identifier="mimetypes-pdf" aria-hidden="true">', $result);
    }

    /**
     * Tests the returns of file with custom image mime-type
     */
    #[Test]
    public function getIconForResourceWithCustomImageMimeTypeReturnsImageIcon(): void
    {
        $fileObject = $this->getTestSubjectFileObject('custom', 'image/my-custom-extension');
        $result = $this->subject->getIconForResource($fileObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image" aria-hidden="true">', $result);
    }

    /**
     * Tests the returns of file png
     */
    #[Test]
    public function getIconForResourceWithPngFileReturnsIcon(): void
    {
        $fileObject = $this->getTestSubjectFileObject('png', 'image/png');
        $result = $this->subject->getIconForResource($fileObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image" aria-hidden="true">', $result);
    }

    /**
     * Tests the returns of normal folder
     */
    #[Test]
    public function getIconForResourceWithFolderReturnsFolderIcon(): void
    {
        $folderObject = $this->getTestSubjectFolderObject('/test');
        $result = $this->subject->getIconForResource($folderObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-medium icon-state-default icon-apps-filetree-folder-default" data-identifier="apps-filetree-folder-default" aria-hidden="true">', $result);
    }

    /**
     * Tests the returns of open folder
     */
    #[Test]
    public function getIconForResourceWithOpenFolderReturnsOpenFolderIcon(): void
    {
        $folderObject = $this->getTestSubjectFolderObject('/test');
        $result = $this->subject->getIconForResource($folderObject, IconSize::MEDIUM, null, ['folder-open' => true])->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-medium icon-state-default icon-apps-filetree-folder-opened" data-identifier="apps-filetree-folder-opened" aria-hidden="true">', $result);
    }

    /**
     * Tests the returns of root folder
     */
    #[Test]
    public function getIconForResourceWithRootFolderReturnsRootFolderIcon(): void
    {
        $folderObject = $this->getTestSubjectFolderObject('/');
        $result = $this->subject->getIconForResource($folderObject)->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-medium icon-state-default icon-apps-filetree-root" data-identifier="apps-filetree-root" aria-hidden="true">', $result);
    }

    /**
     * Tests the returns of mount root
     */
    #[Test]
    public function getIconForResourceWithMountRootReturnsMountFolderIcon(): void
    {
        $folderObject = $this->getTestSubjectFolderObject('/mount');
        $result = $this->subject->getIconForResource($folderObject, IconSize::MEDIUM, null, ['mount-root' => true])->render();
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-medium icon-state-default icon-apps-filetree-mount" data-identifier="apps-filetree-mount" aria-hidden="true">', $result);
    }

    //
    // Test for getIconForRecord
    //
    /**
     * Tests the returns of NULL table + empty array
     */
    #[Test]
    public function getIconForRecordWithNullTableReturnsMissingIcon(): void
    {
        $GLOBALS['TCA']['']['ctrl'] = [];
        self::assertStringContainsString(
            '<span class="t3js-icon icon icon-size-medium icon-state-default icon-default-not-found" data-identifier="default-not-found" aria-hidden="true">',
            $this->subject->getIconForRecord('', [])->render()
        );
    }

    /**
     * Tests the returns of tt_content + empty record
     */
    #[Test]
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
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text" aria-hidden="true">', $result);
    }

    /**
     * Tests the returns of tt_content + mock record
     */
    #[Test]
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
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text" aria-hidden="true">', $result);
    }

    /**
     * Tests the returns of tt_content + mock record with hidden flag
     */
    #[Test]
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
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-medium icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text" aria-hidden="true">', $result);
        self::assertStringContainsString('<span class="icon-overlay icon-overlay-hidden">', $result);
    }

    public static function getIconForRecordDefaultsToBasePageIconForCustomPageTypesIfTheyDontDefineOwnIconsDataProvider(): iterable
    {
        yield 'Custom page without default icon' => [
            'record' => [
                'doktype' => '1337',
                'hidden' => '0',
                'content_from_pid' => '0',
                'nav_hide' => '0',
                'is_siteroot' => '0',
                'module' => '',
            ],
            'expected' => '<span class="t3js-icon icon icon-size-medium icon-state-default icon-apps-pagetree-page-default" data-identifier="apps-pagetree-page-default" aria-hidden="true">',
        ];

        yield 'nav_hide=1' => [
            'record' => [
                'doktype' => '1337',
                'nav_hide' => '1',
                'is_siteroot' => '0',
                'module' => '',
                'content_from_pid' => '0',
            ],
            'expected' => '<span class="t3js-icon icon icon-size-medium icon-state-default icon-apps-pagetree-page-hideinmenu" data-identifier="apps-pagetree-page-hideinmenu" aria-hidden="true">',
        ];

        yield 'is_siteroot=1' => [
            'record' => [
                'doktype' => '1337',
                'nav_hide' => '0',
                'is_siteroot' => '1',
                'module' => '',
                'content_from_pid' => '0',
            ],
            'expected' => '<span class="t3js-icon icon icon-size-medium icon-state-default icon-apps-pagetree-page-domain" data-identifier="apps-pagetree-page-domain" aria-hidden="true">',
        ];

        yield 'module=fe_users' => [
            'record' => [
                'doktype' => '1337',
                'nav_hide' => '0',
                'is_siteroot' => '0',
                'module' => 'fe_users',
                'content_from_pid' => '0',
            ],
            'expected' => '<span class="t3js-icon icon icon-size-medium icon-state-default icon-apps-pagetree-folder-contains-fe_users" data-identifier="apps-pagetree-folder-contains-fe_users" aria-hidden="true">',
        ];

        yield 'content_from_pid=1' => [
            'record' => [
                'doktype' => '1337',
                'nav_hide' => '0',
                'is_siteroot' => '0',
                'module' => '',
                'content_from_pid' => '1',
            ],
            'expected' => '<span class="t3js-icon icon icon-size-medium icon-state-default icon-apps-pagetree-page-content-from-page" data-identifier="apps-pagetree-page-content-from-page" aria-hidden="true">',
        ];

        yield 'content_from_pid=1, nav_hide=1' => [
            'record' => [
                'doktype' => '1337',
                'nav_hide' => '1',
                'is_siteroot' => '0',
                'module' => '',
                'content_from_pid' => '1',
            ],
            'expected' => '<span class="t3js-icon icon icon-size-medium icon-state-default icon-apps-pagetree-page-content-from-page-hideinmenu" data-identifier="apps-pagetree-page-content-from-page-hideinmenu" aria-hidden="true">',
        ];
    }

    #[DataProvider('getIconForRecordDefaultsToBasePageIconForCustomPageTypesIfTheyDontDefineOwnIconsDataProvider')]
    #[Test]
    public function getIconForRecordDefaultsToBasePageIconForCustomPageTypesIfTheyDontDefineOwnIcons(array $record, string $expected): void
    {
        $result = $this->subject->getIconForRecord('pages', $record)->render();

        self::assertStringContainsString($expected, $result);
    }

    #[Test]
    public function modifyRecordOverlayIconIdentifierEventIsTriggered(): void
    {
        $modifyRecordOverlayIconIdentifierEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'modify-record-overlay-icon-identifier-event-listener',
            static function (ModifyRecordOverlayIconIdentifierEvent $event) use (&$modifyRecordOverlayIconIdentifierEvent) {
                $modifyRecordOverlayIconIdentifierEvent = $event;
                $modifyRecordOverlayIconIdentifierEvent->setOverlayIconIdentifier('overlay-identifier');
            }
        );

        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(ModifyRecordOverlayIconIdentifierEvent::class, 'modify-record-overlay-icon-identifier-event-listener');

        $this->subject->getIconForRecord('pages', [])->render();

        self::assertInstanceOf(ModifyRecordOverlayIconIdentifierEvent::class, $modifyRecordOverlayIconIdentifierEvent);
        self::assertEquals('overlay-identifier', $modifyRecordOverlayIconIdentifierEvent->getOverlayIconIdentifier());
    }

    /**
     * Create file object to use as test subject
     */
    protected function getTestSubjectFileObject(string $extension, string $mimeType = ''): File
    {
        $mockedStorage = $this->createMock(ResourceStorage::class);
        $mockedFile = $this->getMockBuilder(File::class)
            ->setConstructorArgs([['identifier' => '', 'name' => ''], $mockedStorage])
            ->getMock();
        $mockedFile->expects($this->atMost(1))->method('getExtension')->willReturn($extension);
        $mockedFile->expects($this->atLeastOnce())->method('getMimeType')->willReturn($mimeType);
        return $mockedFile;
    }

    /**
     * Create folder object to use as test subject
     */
    protected function getTestSubjectFolderObject(string $identifier): Folder
    {
        $mockedStorage = $this->createMock(ResourceStorage::class);
        $mockedStorage->method('getRootLevelFolder')->willReturn(
            new Folder($mockedStorage, '/', '/')
        );
        $mockedStorage->method('checkFolderActionPermission')->willReturn(true);
        $mockedStorage->method('isBrowsable')->willReturn(true);
        return new Folder($mockedStorage, $identifier, $identifier);
    }
}
