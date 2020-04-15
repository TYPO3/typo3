<?php

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
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider
 */
class BitmapIconProviderTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider
     */
    protected $subject;

    /**
     * @var \TYPO3\CMS\Core\Imaging\Icon
     */
    protected $icon;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new BitmapIconProvider();
        $this->icon = GeneralUtility::makeInstance(Icon::class);
        $this->icon->setIdentifier('foo');
        $this->icon->setSize(Icon::SIZE_SMALL);
    }

    /**
     * @test
     */
    public function prepareIconMarkupWithRelativeSourceReturnsInstanceOfIconWithCorrectMarkup()
    {
        $this->subject->prepareIconMarkup($this->icon, ['source' => 'fileadmin/foo.png']);
        self::assertEquals('<img src="fileadmin/foo.png" width="16" height="16" alt="" />', $this->icon->getMarkup());
    }

    /**
     * @test
     */
    public function prepareIconMarkupWithAbsoluteSourceReturnsInstanceOfIconWithCorrectMarkup()
    {
        $this->subject->prepareIconMarkup($this->icon, ['source' => '/fileadmin/foo.png']);
        self::assertEquals('<img src="/fileadmin/foo.png" width="16" height="16" alt="" />', $this->icon->getMarkup());
    }

    /**
     * @test
     */
    public function prepareIconMarkupEXTSourceReferenceReturnsInstanceOfIconWithCorrectMarkup()
    {
        $this->subject->prepareIconMarkup($this->icon, ['source' => 'EXT:core/Resources/Public/Images/foo.png']);
        self::assertEquals('<img src="typo3/sysext/core/Resources/Public/Images/foo.png" width="16" height="16" alt="" />', $this->icon->getMarkup());
    }
}
