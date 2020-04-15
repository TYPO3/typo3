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

use TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FrontendUserGroupTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new FrontendUserGroup();
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsEmptyString()
    {
        $this->subject = new FrontendUserGroup();
        self::assertSame('', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsGivenTitleFromConstruct()
    {
        $title = 'foo bar';
        $this->subject = new FrontendUserGroup($title);
        self::assertSame($title, $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $title = 'foo bar';
        $this->subject->setTitle($title);
        self::assertSame($title, $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getLockToDomainInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getLockToDomain());
    }

    /**
     * @test
     */
    public function setLockToDomainSetsLockToDomain()
    {
        $lockToDomain = 'foo.bar';
        $this->subject->setLockToDomain($lockToDomain);
        self::assertSame($lockToDomain, $this->subject->getLockToDomain());
    }

    /**
     * @test
     */
    public function getDescriptionInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getDescription());
    }

    /**
     * @test
     */
    public function setDescriptionSetsDescription()
    {
        $description = 'foo bar';
        $this->subject->setDescription($description);
        self::assertSame($description, $this->subject->getDescription());
    }

    /**
     * @test
     */
    public function addSubgroupAddsSubgroup()
    {
        $group1 = new FrontendUserGroup('foo');
        self::assertEquals(count($this->subject->getSubgroup()), 0);
        $this->subject->addSubgroup($group1);
        self::assertEquals(count($this->subject->getSubgroup()), 1);
    }

    /**
     * @test
     */
    public function removeSubgroupRemovesSubgroup()
    {
        $group1 = new FrontendUserGroup('foo');
        $group2 = new FrontendUserGroup('bar');
        $this->subject->addSubgroup($group1);
        $this->subject->addSubgroup($group2);
        self::assertEquals(count($this->subject->getSubgroup()), 2);
        $this->subject->removeSubgroup($group1);
        self::assertEquals(count($this->subject->getSubgroup()), 1);
        $this->subject->removeSubgroup($group2);
        self::assertEquals(count($this->subject->getSubgroup()), 0);
    }

    /**
     * @test
     */
    public function setSubgroupSetsSubgroups()
    {
        $subgroup = new ObjectStorage();
        $group = new FrontendUserGroup('foo');
        $subgroup->attach($group);
        $this->subject->setSubgroup($subgroup);
        self::assertSame($subgroup, $this->subject->getSubgroup());
    }
}
