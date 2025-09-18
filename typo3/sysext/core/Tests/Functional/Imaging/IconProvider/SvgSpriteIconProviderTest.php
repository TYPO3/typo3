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
use TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\DummyFileCreationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider
 */
final class SvgSpriteIconProviderTest extends FunctionalTestCase
{
    private const svgTestFileContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="#CD201F" d="M11 12l3-2v6H2v-6l3 2 3-2 3 2z"></path></svg>';

    protected ?SvgSpriteIconProvider $subject;

    /**
     * @var Icon
     */
    protected $icon;

    private DummyFileCreationService $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = new DummyFileCreationService($this->get(StorageRepository::class));
        $this->subject = new SvgSpriteIconProvider();
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
        $testFile = $this->file->ensureFilesExistInPublicFolder('/typo3temp/assets/actions.svg', self::svgTestFileContent);
        $this->subject->prepareIconMarkup($this->icon, [
            'sprite' => 'typo3temp/assets/actions.svg#actions-plus',
            'source' => 'typo3temp/assets/actions-plus.svg',
        ]);
        self::assertEquals('<svg class="icon-color"><use xlink:href="/typo3temp/assets/actions.svg?' . filemtime($testFile) . '#actions-plus" /></svg>', $this->icon->getMarkup());
    }

    #[Test]
    public function prepareIconMarkupWithAbsoluteSourceReturnsInstanceOfIconWithCorrectMarkup(): void
    {
        $testFile = $this->file->ensureFilesExistInPublicFolder('/typo3temp/assets/actions.svg', self::svgTestFileContent);
        $this->subject->prepareIconMarkup($this->icon, [
            'sprite' => '/typo3temp/assets/actions.svg#actions-plus',
            'source' => '/typo3temp/assets/actions-plus.svg',
        ]);
        self::assertEquals('<svg class="icon-color"><use xlink:href="/typo3temp/assets/actions.svg?' . filemtime($testFile) . '#actions-plus" /></svg>', $this->icon->getMarkup());
    }

    #[Test]
    public function prepareIconMarkupWithPkgSourceReturnsInstanceOfIconWithCorrectMarkup(): void
    {
        $testFile = $this->file->ensureFilesExistInPublicFolder('/typo3temp/assets/actions.svg', self::svgTestFileContent);
        $this->subject->prepareIconMarkup($this->icon, [
            'sprite' => 'PKG:typo3/app:typo3temp/assets/actions.svg#actions-plus',
            'source' => 'PKG:typo3/app:typo3temp/assets/actions-plus.svg',
        ]);
        self::assertEquals('<svg class="icon-color"><use xlink:href="/typo3temp/assets/actions.svg?' . filemtime($testFile) . '#actions-plus" /></svg>', $this->icon->getMarkup());
    }

    #[Test]
    public function getIconWithExtSourceReferenceReturnsInstanceOfIconWithCorrectMarkup(): void
    {
        $this->subject->prepareIconMarkup($this->icon, [
            'sprite' => 'EXT:core/Resources/Public/Icons/T3Icons/sprites/action.svg#actions-plus',
            'source' => 'EXT:core/Resources/Public/Icons/T3Icons/svgs/actions/actions-plus.svg',
        ]);
        self::assertEquals('<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/action.svg#actions-plus" /></svg>', $this->icon->getMarkup());
    }

    #[Test]
    public function getIconWithPkgSourceReferenceReturnsInstanceOfIconWithCorrectMarkup(): void
    {
        $this->subject->prepareIconMarkup($this->icon, [
            'sprite' => 'PKG:typo3/cms-core:Resources/Public/Icons/T3Icons/sprites/action.svg#actions-plus',
            'source' => 'PKG:typo3/cms-core:Resources/Public/Icons/T3Icons/svgs/actions/actions-plus.svg',
        ]);
        self::assertEquals('<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/action.svg#actions-plus" /></svg>', $this->icon->getMarkup());
    }

    #[Test]
    public function getIconWithInlineOptionReturnsCleanSvgMarkup(): void
    {
        $svgTestFileContent = '<?xml version="1.0" encoding="ISO-8859-1" standalone="no" ?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="#CD201F" d="M11 12l3-2v6H2v-6l3 2 3-2 3 2z"></path><script><![CDATA[ function alertMe() {} ]]></script></svg>';
        $this->file->ensureFilesExistInPublicFolder('/typo3temp/assets/foo.svg', $svgTestFileContent);
        $this->file->ensureFilesExistInPublicFolder('/typo3temp/assets/foo-bar.svg', $svgTestFileContent);
        $this->subject->prepareIconMarkup($this->icon, [
            'sprite' => '/typo3temp/assets/foo.svg#bar',
            'source' => '/typo3temp/assets/foo-bar.svg',
        ]);
        self::assertEquals('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="#CD201F" d="M11 12l3-2v6H2v-6l3 2 3-2 3 2z"/></svg>', $this->icon->getMarkup(SvgSpriteIconProvider::MARKUP_IDENTIFIER_INLINE));
    }

    #[Test]
    public function getIconWithInlineOptionReturnsCleanSvgMarkupForPkgResource(): void
    {
        $svgTestFileContent = '<?xml version="1.0" encoding="ISO-8859-1" standalone="no" ?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="#CD201F" d="M11 12l3-2v6H2v-6l3 2 3-2 3 2z"></path><script><![CDATA[ function alertMe() {} ]]></script></svg>';
        $this->file->ensureFilesExistInPublicFolder('/typo3temp/assets/foo.svg', $svgTestFileContent);
        $this->file->ensureFilesExistInPublicFolder('/typo3temp/assets/foo-bar.svg', $svgTestFileContent);
        $this->subject->prepareIconMarkup($this->icon, [
            'sprite' => 'PKG:typo3/app:typo3temp/assets/foo.svg#bar',
            'source' => 'PKG:typo3/app:typo3temp/assets/foo-bar.svg',
        ]);
        self::assertEquals('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="#CD201F" d="M11 12l3-2v6H2v-6l3 2 3-2 3 2z"/></svg>', $this->icon->getMarkup(SvgSpriteIconProvider::MARKUP_IDENTIFIER_INLINE));
    }
}
