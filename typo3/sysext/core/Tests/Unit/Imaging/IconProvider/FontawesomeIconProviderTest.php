<?php
namespace TYPO3\CMS\Core\Tests\Unit\Imaging\IconProvider;

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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider
 */
class FontawesomeIconProviderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider
     */
    protected $subject = null;

    /**
     * @var Icon
     */
    protected $icon = null;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider();
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
        $this->assertEquals('<span class="icon-unify"><i class="fa fa-times"></i></span>', $this->icon->getMarkup());
    }

    /**
     * DataProvider for icon names
     *
     * @return array
     */
    public function wrongNamesDataProvider()
    {
        return [
            'name with spaces' => ['name with spaces'],
            'name with spaces and umlauts' => ['name with spaces äöü'],
            'name umlauts' => ['häuser'],
            'name with underscore' => ['name_with_underscore'],
            'name is empty' => [''],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider wrongNamesDataProvider
     * @param string $name
     * @test
     */
    public function prepareIconMarkupWithInvalidNameThrowsException($name)
    {
        $this->subject->prepareIconMarkup($this->icon, ['name' => $name]);
    }
}
