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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Domain\Model;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FileMountTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileMount
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new \TYPO3\CMS\Extbase\Domain\Model\FileMount();
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $title = 'foobar mount';
        $this->subject->setTitle($title);
        self::assertSame($title, $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setDescriptionSetsDescription()
    {
        $description = 'This is the foobar mount, used for foo and bar';
        $this->subject->setDescription($description);
        self::assertSame($description, $this->subject->getDescription());
    }

    /**
     * @test
     */
    public function getPathInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getPath());
    }

    /**
     * @test
     */
    public function setPathSetsPath()
    {
        $path = 'foo/bar/';
        $this->subject->setPath($path);
        self::assertSame($path, $this->subject->getPath());
    }

    /**
     * @test
     */
    public function getIsAbsolutePathInitiallyReturnsFalse()
    {
        self::assertFalse($this->subject->getIsAbsolutePath());
    }

    /**
     * @test
     */
    public function setIsAbsolutePathCanSetBaseIsAbsolutePathToTrue()
    {
        $this->subject->setIsAbsolutePath(true);
        self::assertTrue($this->subject->getIsAbsolutePath());
    }
}
