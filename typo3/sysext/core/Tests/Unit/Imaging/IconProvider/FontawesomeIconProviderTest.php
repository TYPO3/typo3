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
use TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider
 */
class FontawesomeIconProviderTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider
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
        $this->subject = new FontawesomeIconProvider();
        $this->icon = GeneralUtility::makeInstance(Icon::class);
        $this->icon->setIdentifier('foo');
        $this->icon->setSize(Icon::SIZE_SMALL);
    }

    /**
     * @test
     */
    public function prepareIconMarkupWithNameReturnsInstanceOfIconWithCorrectMarkup()
    {
        $this->subject->prepareIconMarkup($this->icon, ['name' => 'times']);
        self::assertEquals('<span class="icon-unify"><i class="fa fa-times"></i></span>', $this->icon->getMarkup());
    }

    /**
     * DataProvider for icon names
     *
     * @return array
     */
    public function wrongNamesDataProvider()
    {
        return [
            'name with spaces' => ['name with spaces', 1440754979],
            'name with spaces and umlauts' => ['name with spaces äöü', 1440754979],
            'name umlauts' => ['häuser', 1440754979],
            'name with underscore' => ['name_with_underscore', 1440754979],
            'name is empty' => ['', 1440754978],
        ];
    }

    /**
     * @dataProvider wrongNamesDataProvider
     * @param string $name
     * @param int $expectedExceptionCode
     * @test
     */
    public function prepareIconMarkupWithInvalidNameThrowsException($name, $expectedExceptionCode)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedExceptionCode);

        $this->subject->prepareIconMarkup($this->icon, ['name' => $name]);
    }
}
