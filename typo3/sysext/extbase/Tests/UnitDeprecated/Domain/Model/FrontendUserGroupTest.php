<?php

declare(strict_types=1);

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

use TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FrontendUserGroupTest extends UnitTestCase
{
    /**
     * @var FrontendUserGroup
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
    public function getTitleInitiallyReturnsEmptyString(): void
    {
        $this->subject = new FrontendUserGroup();
        self::assertSame('', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsGivenTitleFromConstruct(): void
    {
        $title = 'foo bar';
        $this->subject = new FrontendUserGroup($title);
        self::assertSame($title, $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setTitleSetsTitle(): void
    {
        $title = 'foo bar';
        $this->subject->setTitle($title);
        self::assertSame($title, $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getDescriptionInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getDescription());
    }

    /**
     * @test
     */
    public function setDescriptionSetsDescription(): void
    {
        $description = 'foo bar';
        $this->subject->setDescription($description);
        self::assertSame($description, $this->subject->getDescription());
    }

    /**
     * @test
     */
    public function addSubgroupAddsSubgroup(): void
    {
        $group1 = new FrontendUserGroup('foo');
        self::assertCount(0, $this->subject->getSubgroup());
        $this->subject->addSubgroup($group1);
        self::assertCount(1, $this->subject->getSubgroup());
    }

    /**
     * @test
     */
    public function removeSubgroupRemovesSubgroup(): void
    {
        $group1 = new FrontendUserGroup('foo');
        $group2 = new FrontendUserGroup('bar');
        $this->subject->addSubgroup($group1);
        $this->subject->addSubgroup($group2);
        self::assertCount(2, $this->subject->getSubgroup());
        $this->subject->removeSubgroup($group1);
        self::assertCount(1, $this->subject->getSubgroup());
        $this->subject->removeSubgroup($group2);
        self::assertCount(0, $this->subject->getSubgroup());
    }

    /**
     * @test
     */
    public function setSubgroupSetsSubgroups(): void
    {
        $subgroup = new ObjectStorage();
        $group = new FrontendUserGroup('foo');
        $subgroup->attach($group);
        $this->subject->setSubgroup($subgroup);
        self::assertSame($subgroup, $this->subject->getSubgroup());
    }
}
