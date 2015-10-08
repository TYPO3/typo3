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
class FileMountTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileMount
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Extbase\Domain\Model\FileMount();
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $title = 'foobar mount';
        $this->subject->setTitle($title);
        $this->assertSame($title, $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setDescriptionSetsDescription()
    {
        $description = 'This is the foobar mount, used for foo and bar';
        $this->subject->setDescription($description);
        $this->assertSame($description, $this->subject->getDescription());
    }

    /**
     * @test
     */
    public function getPathInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getPath());
    }

    /**
     * @test
     */
    public function setPathSetsPath()
    {
        $path = 'foo/bar/';
        $this->subject->setPath($path);
        $this->assertSame($path, $this->subject->getPath());
    }

    /**
     * @test
     */
    public function getIsAbsolutePathInitiallyReturnsFalse()
    {
        $this->assertFalse($this->subject->getIsAbsolutePath());
    }

    /**
     * @test
     */
    public function setIsAbsolutePathCanSetBaseIsAbsolutePathToTrue()
    {
        $this->subject->setIsAbsolutePath(true);
        $this->assertTrue($this->subject->getIsAbsolutePath());
    }
}
