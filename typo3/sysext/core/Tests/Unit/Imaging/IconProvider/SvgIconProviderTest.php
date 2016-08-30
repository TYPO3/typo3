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
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider
 */
class SvgIconProviderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider
     */
    protected $subject = null;

    /**
     * @var Icon
     */
    protected $icon = null;

    /**
     * @var string
     */
    protected $testFileName;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider();
        $this->icon = GeneralUtility::makeInstance(Icon::class);
        $this->icon->setIdentifier('foo');
        $this->icon->setSize(Icon::SIZE_SMALL);

        $svgTestFileContent = '<?xml version="1.0" encoding="ISO-8859-1" standalone="no" ?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="#CD201F" d="M11 12l3-2v6H2v-6l3 2 3-2 3 2z"></path><script><![CDATA[ function alertMe() {} ]]></script></svg>';
        $this->testFileName = GeneralUtility::tempnam(uniqid('svg_') . '.svg');
        file_put_contents($this->testFileName, $svgTestFileContent);
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        unlink($this->testFileName);
    }

    /**
     * @test
     */
    public function prepareIconMarkupWithRelativeSourceReturnsInstanceOfIconWithCorrectMarkup()
    {
        $this->subject->prepareIconMarkup($this->icon, ['source' => 'fileadmin/foo.svg']);
        $this->assertEquals('<img src="fileadmin/foo.svg" width="16" height="16" />', $this->icon->getMarkup());
    }

    /**
     * @test
     */
    public function prepareIconMarkupWithAbsoluteSourceReturnsInstanceOfIconWithCorrectMarkup()
    {
        $this->subject->prepareIconMarkup($this->icon, ['source' => '/fileadmin/foo.svg']);
        $this->assertEquals('<img src="/fileadmin/foo.svg" width="16" height="16" />', $this->icon->getMarkup());
    }

    /**
     * @test
     */
    public function getIconWithEXTSourceReferenceReturnsInstanceOfIconWithCorrectMarkup()
    {
        $this->subject->prepareIconMarkup($this->icon, ['source' => 'EXT:core/Resources/Public/Images/foo.svg']);
        $this->assertEquals('<img src="typo3/sysext/core/Resources/Public/Images/foo.svg" width="16" height="16" />', $this->icon->getMarkup());
    }

    /**
     * @test
     */
    public function getIconWithInlineOptionReturnsCleanSvgMarkup()
    {
        $this->subject->prepareIconMarkup($this->icon, ['source' => $this->testFileName]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="#CD201F" d="M11 12l3-2v6H2v-6l3 2 3-2 3 2z"/></svg>', $this->icon->getMarkup(SvgIconProvider::MARKUP_IDENTIFIER_INLINE));
    }
}
