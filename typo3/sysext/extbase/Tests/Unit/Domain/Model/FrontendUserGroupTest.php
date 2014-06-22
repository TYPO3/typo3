<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Model;

/**
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
class FrontendUserGroupTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup
	 */
	protected $fixture = NULL;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup();
	}

	/**
	 * @test
	 */
	public function getTitleInitiallyReturnsEmptyString() {
		$this->fixture = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup();
		$this->assertSame('', $this->fixture->getTitle());
	}

	/**
	 * @test
	 */
	public function getTitleInitiallyReturnsGivenTitleFromConstruct() {
		$title = 'foo bar';
		$this->fixture = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup($title);
		$this->assertSame($title, $this->fixture->getTitle());
	}

	/**
	 * @test
	 */
	public function setTitleSetsTitle() {
		$title = 'foo bar';
		$this->fixture->setTitle($title);
		$this->assertSame($title, $this->fixture->getTitle());
	}

	/**
	 * @test
	 */
	public function getLockToDomainInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getLockToDomain());
	}

	/**
	 * @test
	 */
	public function setLockToDomainSetsLockToDomain() {
		$lockToDomain = 'foo.bar';
		$this->fixture->setLockToDomain($lockToDomain);
		$this->assertSame($lockToDomain, $this->fixture->getLockToDomain());
	}

	/**
	 * @test
	 */
	public function getDescriptionInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getDescription());
	}

	/**
	 * @test
	 */
	public function setDescriptionSetsDescription() {
		$description = 'foo bar';
		$this->fixture->setDescription($description);
		$this->assertSame($description, $this->fixture->getDescription());
	}

	/**
	 * @test
	 */
	public function addSubgroupAddsSubgroup() {
		$group1 = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup('foo');
		$this->assertEquals(count($this->fixture->getSubgroup()), 0);
		$this->fixture->addSubgroup($group1);
		$this->assertEquals(count($this->fixture->getSubgroup()), 1);
	}

	/**
	 * @test
	 */
	public function removeSubgroupRemovesSubgroup() {
		$group1 = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup('foo');
		$group2 = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup('bar');
		$this->fixture->addSubgroup($group1);
		$this->fixture->addSubgroup($group2);
		$this->assertEquals(count($this->fixture->getSubgroup()), 2);
		$this->fixture->removeSubgroup($group1);
		$this->assertEquals(count($this->fixture->getSubgroup()), 1);
		$this->fixture->removeSubgroup($group2);
		$this->assertEquals(count($this->fixture->getSubgroup()), 0);
	}

	/**
	 * @test
	 */
	public function setSubgroupSetsSubgroups() {
		$subgroup = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$group = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup('foo');
		$subgroup->attach($group);
		$this->fixture->setSubgroup($subgroup);
		$this->assertSame($subgroup, $this->fixture->getSubgroup());
	}
}
