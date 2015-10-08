<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Model;

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
 * Test case
 */
class CategoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\Category
     */
    protected $fixture = null;

    protected function setUp()
    {
        $this->fixture = new \TYPO3\CMS\Extbase\Domain\Model\Category();
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->fixture->getTitle());
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $this->fixture->setTitle('foo bar');
        $this->assertSame('foo bar', $this->fixture->getTitle());
    }

    /**
     * @test
     */
    public function getDescriptionInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->fixture->getDescription());
    }

    /**
     * @test
     */
    public function setDescriptionSetsDescription()
    {
        $this->fixture->setDescription('foo bar');
        $this->assertSame('foo bar', $this->fixture->getDescription());
    }

    /**
     * @test
     */
    public function getIconInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->fixture->getIcon());
    }

    /**
     * @test
     */
    public function setIconSetsIcon()
    {
        $this->fixture->setIcon('icon.png');
        $this->assertSame('icon.png', $this->fixture->getIcon());
    }

    /**
     * @test
     */
    public function getParentInitiallyReturnsNull()
    {
        $this->assertNull($this->fixture->getParent());
    }

    /**
     * @test
     */
    public function setParentSetsParent()
    {
        $parent = new \TYPO3\CMS\Extbase\Domain\Model\Category();
        $this->fixture->setParent($parent);
        $this->assertSame($parent, $this->fixture->getParent());
    }
}
