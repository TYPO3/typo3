<?php
namespace TYPO3\CMS\Lang\Tests\Unit\Domain\Model;

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

/**
 * Testcase for Extension
 */
class ExtensionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Lang\Domain\Model\Extension
     */
    protected $subject = null;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Lang\Domain\Model\Extension();
    }

    /**
     * @test
     */
    public function getKeyInitiallyReturnsEmptyString()
    {
        $this->assertSame(
            '',
            $this->subject->getKey()
        );
    }

    /**
     * @test
     */
    public function getKeyInitiallyReturnsGivenKeyFromConstruct()
    {
        $key = 'foo bar';
        $this->subject = new \TYPO3\CMS\Lang\Domain\Model\Extension($key);

        $this->assertSame(
            $key,
            $this->subject->getKey()
        );
    }

    /**
     * @test
     */
    public function setKeySetsKey()
    {
        $key = 'foo bar';
        $this->subject->setKey($key);

        $this->assertSame(
            $key,
            $this->subject->getKey()
        );
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsEmptyString()
    {
        $this->assertSame(
            '',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsGivenTitleFromConstruct()
    {
        $title = 'foo bar';
        $this->subject = new \TYPO3\CMS\Lang\Domain\Model\Extension('', $title);

        $this->assertSame(
            $title,
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $title = 'foo bar';
        $this->subject->setTitle($title);

        $this->assertSame(
            $title,
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function getIconInitiallyReturnsEmptyString()
    {
        $this->assertSame(
            '',
            $this->subject->getIcon()
        );
    }

    /**
     * @test
     */
    public function getIconInitiallyReturnsGivenIconFromConstruct()
    {
        $icon = 'foo bar';
        $this->subject = new \TYPO3\CMS\Lang\Domain\Model\Extension('', '', $icon);

        $this->assertSame(
            $icon,
            $this->subject->getIcon()
        );
    }

    /**
     * @test
     */
    public function setIconSetsIcon()
    {
        $icon = 'foo bar';
        $this->subject->setIcon($icon);

        $this->assertSame(
            $icon,
            $this->subject->getIcon()
        );
    }

    /**
     * @test
     */
    public function getVersionInitiallyReturnsEmptyString()
    {
        $this->assertSame(
            '',
            $this->subject->getVersion()
        );
    }

    /**
     * @test
     */
    public function setVersionSetsVersion()
    {
        $version = 10;
        $this->subject->setVersion($version);

        $this->assertSame(
            $version,
            $this->subject->getVersion()
        );
    }

    /**
     * @test
     */
    public function setVersionSetsVersionFromString()
    {
        $version = 4012003;
        $this->subject->setVersionFromString('4.12.3');

        $this->assertSame(
            $version,
            $this->subject->getVersion()
        );
    }

    /**
     * @test
     */
    public function getUpdateResultInitiallyReturnsEmptyArray()
    {
        $this->assertSame(
            [],
            $this->subject->getUpdateResult()
        );
    }

    /**
     * @test
     */
    public function setUpdateResultSetsUpdateResult()
    {
        $updateResult = [
            'nl' => [
                'icon' => '<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-info">&nbsp;</span>',
                'message' => 'translation_n_a'
            ],
        ];

        $this->subject->setUpdateResult($updateResult);

        $this->assertSame(
            $updateResult,
            $this->subject->getUpdateResult()
        );
    }
}
