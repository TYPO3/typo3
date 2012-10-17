<?php
?> <?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Markus Günther <mail@markus-guenther.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for Tx_Extbase_Domain_Model_FrontendUserGroup.
 *
 * @author Markus Günther <mail@markus-guenther.de>
 * @package Extbase
 * @scope prototype
 * @entity
 * @api
 */
class FrontendUserGroupTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup
	 */
	protected $fixture = NULL;

	public function setUp() {
		$this->fixture = new \Tx_Extbase_Domain_Model_FrontendUserGroup();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function getTitleInitiallyReturnsEmptyString() {
		$this->fixture = new \Tx_Extbase_Domain_Model_FrontendUserGroup();
		$this->assertSame('', $this->fixture->getTitle());
	}

	/**
	 * @test
	 */
	public function getTitleInitiallyReturnsGivenTitleFromConstruct() {
		$title = 'foo bar';
		$this->fixture = new \Tx_Extbase_Domain_Model_FrontendUserGroup($title);
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
		$group1 = new \Tx_Extbase_Domain_Model_FrontendUserGroup('foo');
		$this->assertEquals(count($this->fixture->getSubgroup()), 0);
		$this->fixture->addSubgroup($group1);
		$this->assertEquals(count($this->fixture->getSubgroup()), 1);
	}

	/**
	 * @test
	 */
	public function removeSubgroupRemovesSubgroup() {
		$group1 = new \Tx_Extbase_Domain_Model_FrontendUserGroup('foo');
		$group2 = new \Tx_Extbase_Domain_Model_FrontendUserGroup('bar');
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
		$subgroup = new \Tx_Extbase_Persistence_ObjectStorage();
		$group = new \Tx_Extbase_Domain_Model_FrontendUserGroup('foo');
		$subgroup->attach($group);
		$this->fixture->setSubgroup($subgroup);
		$this->assertSame($subgroup, $this->fixture->getSubgroup());
	}

}


?>