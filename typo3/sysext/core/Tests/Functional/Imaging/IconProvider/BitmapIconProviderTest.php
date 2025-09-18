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

namespace TYPO3\CMS\Core\Tests\Functional\Imaging\IconProvider;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\DummyFileCreationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider
 */
final class BitmapIconProviderTest extends FunctionalTestCase
{
    protected ?BitmapIconProvider $subject;

    /**
     * @var Icon
     */
    protected $icon;

    private DummyFileCreationService $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = new DummyFileCreationService($this->get(StorageRepository::class));
        $this->subject = new BitmapIconProvider();
        $this->icon = new Icon();
        $this->icon->setIdentifier('foo');
        $this->icon->setSize(IconSize::SMALL);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->file->cleanupCreatedFiles();
    }

    #[Test]
    public function prepareIconMarkupWithRelativeSourceReturnsInstanceOfIconWithCorrectMarkup(): void
    {
        $testFile = $this->file->ensureFilesExistInPublicFolder('/_assets/foo.png');
        $this->subject->prepareIconMarkup($this->icon, ['source' => '_assets/foo.png']);
        self::assertEquals('<img src="/_assets/foo.png?' . filemtime($testFile) . '" width="16" height="16" alt="" />', $this->icon->getMarkup());
    }

    #[Test]
    public function prepareIconMarkupEXTSourceReferenceReturnsInstanceOfIconWithCorrectMarkup(): void
    {
        $this->subject->prepareIconMarkup($this->icon, ['source' => 'EXT:core/Resources/Public/Images/foo.png']);
        self::assertEquals('<img src="/typo3/sysext/core/Resources/Public/Images/foo.png" width="16" height="16" alt="" />', $this->icon->getMarkup());
    }
}
