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

namespace TYPO3\CMS\Core\Tests\Unit\Imaging\IconProvider;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider
 */
class SvgSpriteIconProviderTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider
     */
    protected $subject;

    /**
     * @var Icon
     */
    protected $icon;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SvgSpriteIconProvider();
        $this->icon = GeneralUtility::makeInstance(Icon::class);
        $this->icon->setIdentifier('foo');
        $this->icon->setSize(Icon::SIZE_SMALL);
    }

    /**
     * @test
     */
    public function prepareIconMarkupWithRelativeSourceReturnsInstanceOfIconWithCorrectMarkup(): void
    {
        $this->subject->prepareIconMarkup($this->icon, [
            'sprite' => 'fileadmin/sprites/actions.svg#actions-add',
            'source' => 'fileadmin/svg/actions-add.svg',
        ]);
        self::assertEquals('<svg class="icon-color"><use xlink:href="fileadmin/sprites/actions.svg#actions-add" /></svg>', $this->icon->getMarkup());
    }

    /**
     * @test
     */
    public function prepareIconMarkupWithAbsoluteSourceReturnsInstanceOfIconWithCorrectMarkup(): void
    {
        $this->subject->prepareIconMarkup($this->icon, [
            'sprite' => '/fileadmin/sprites/actions.svg#actions-add',
            'source' => '/fileadmin/svg/actions-add.svg',
        ]);
        self::assertEquals('<svg class="icon-color"><use xlink:href="/fileadmin/sprites/actions.svg#actions-add" /></svg>', $this->icon->getMarkup());
    }

    /**
     * @test
     */
    public function getIconWithEXTSourceReferenceReturnsInstanceOfIconWithCorrectMarkup(): void
    {
        $this->subject->prepareIconMarkup($this->icon, [
            'sprite' => 'EXT:core/Resources/Public/Images/sprites/actions.svg#actions-add',
            'source' => 'EXT:core/Resources/Public/Images/svg/actions-add.svg',
        ]);
        self::assertEquals('<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Images/sprites/actions.svg#actions-add" /></svg>', $this->icon->getMarkup());
    }

    /**
     * @test
     */
    public function getIconWithInlineOptionReturnsCleanSvgMarkup(): void
    {
        $testFile = GeneralUtility::tempnam('svg_', '.svg');
        $this->testFilesToDelete[] = $testFile;
        $svgTestFileContent = '<?xml version="1.0" encoding="ISO-8859-1" standalone="no" ?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="#CD201F" d="M11 12l3-2v6H2v-6l3 2 3-2 3 2z"></path><script><![CDATA[ function alertMe() {} ]]></script></svg>';
        file_put_contents($testFile, $svgTestFileContent);
        $this->testFilesToDelete[] = GeneralUtility::tempnam('svg_', '.svg');
        $this->subject->prepareIconMarkup($this->icon, [
            'sprite' => $testFile,
            'source' => $testFile,
        ]);
        self::assertEquals('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="#CD201F" d="M11 12l3-2v6H2v-6l3 2 3-2 3 2z"/></svg>', $this->icon->getMarkup(SvgSpriteIconProvider::MARKUP_IDENTIFIER_INLINE));
    }
}
