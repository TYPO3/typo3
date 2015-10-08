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
class FrontendUserGroupTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup();
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsEmptyString()
    {
        $this->subject = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup();
        $this->assertSame('', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsGivenTitleFromConstruct()
    {
        $title = 'foo bar';
        $this->subject = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup($title);
        $this->assertSame($title, $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $title = 'foo bar';
        $this->subject->setTitle($title);
        $this->assertSame($title, $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getLockToDomainInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getLockToDomain());
    }

    /**
     * @test
     */
    public function setLockToDomainSetsLockToDomain()
    {
        $lockToDomain = 'foo.bar';
        $this->subject->setLockToDomain($lockToDomain);
        $this->assertSame($lockToDomain, $this->subject->getLockToDomain());
    }

    /**
     * @test
     */
    public function getDescriptionInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getDescription());
    }

    /**
     * @test
     */
    public function setDescriptionSetsDescription()
    {
        $description = 'foo bar';
        $this->subject->setDescription($description);
        $this->assertSame($description, $this->subject->getDescription());
    }

    /**
     * @test
     */
    public function addSubgroupAddsSubgroup()
    {
        $group1 = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup('foo');
        $this->assertEquals(count($this->subject->getSubgroup()), 0);
        $this->subject->addSubgroup($group1);
        $this->assertEquals(count($this->subject->getSubgroup()), 1);
    }

    /**
     * @test
     */
    public function removeSubgroupRemovesSubgroup()
    {
        $group1 = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup('foo');
        $group2 = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup('bar');
        $this->subject->addSubgroup($group1);
        $this->subject->addSubgroup($group2);
        $this->assertEquals(count($this->subject->getSubgroup()), 2);
        $this->subject->removeSubgroup($group1);
        $this->assertEquals(count($this->subject->getSubgroup()), 1);
        $this->subject->removeSubgroup($group2);
        $this->assertEquals(count($this->subject->getSubgroup()), 0);
    }

    /**
     * @test
     */
    public function setSubgroupSetsSubgroups()
    {
        $subgroup = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $group = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup('foo');
        $subgroup->attach($group);
        $this->subject->setSubgroup($subgroup);
        $this->assertSame($subgroup, $this->subject->getSubgroup());
    }
}
