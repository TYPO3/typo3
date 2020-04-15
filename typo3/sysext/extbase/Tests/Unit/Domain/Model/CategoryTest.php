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

namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CategoryTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\Category
     */
    protected $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture = new Category();
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->fixture->getTitle());
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $this->fixture->setTitle('foo bar');
        self::assertSame('foo bar', $this->fixture->getTitle());
    }

    /**
     * @test
     */
    public function getDescriptionInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->fixture->getDescription());
    }

    /**
     * @test
     */
    public function setDescriptionSetsDescription()
    {
        $this->fixture->setDescription('foo bar');
        self::assertSame('foo bar', $this->fixture->getDescription());
    }

    /**
     * @test
     */
    public function getParentInitiallyReturnsNull()
    {
        self::assertNull($this->fixture->getParent());
    }

    /**
     * @test
     */
    public function setParentSetsParent()
    {
        $parent = new Category();
        $this->fixture->setParent($parent);
        self::assertSame($parent, $this->fixture->getParent());
    }
}
