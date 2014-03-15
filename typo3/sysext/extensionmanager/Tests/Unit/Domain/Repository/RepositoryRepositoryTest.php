<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain\Repository;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Christian Kuhn, <lolli@schwarzbu.ch>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 *
 */
class RepositoryRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository
	 */
	protected $fixture;

	public function setUp() {
		$this->mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		/** @var $fixture \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository|\PHPUnit_Framework_MockObject_MockObject */
		$this->fixture = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\RepositoryRepository', array('findAll'), array($this->mockObjectManager));
	}

	/**
	 * @test
	 */
	public function findOneTypo3OrgRepositoryReturnsNullIfNoRepositoryWithThisTitleExists() {

		$this->fixture
			->expects($this->once())
			->method('findAll')
			->will($this->returnValue(array()));

		$this->assertNull($this->fixture->findOneTypo3OrgRepository());
	}

	/**
	 * @test
	 */
	public function findOneTypo3OrgRepositoryReturnsRepositoryWithCorrectTitle() {
		$mockModelOne = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Repository');
		$mockModelOne
			->expects(($this->once()))
			->method('getTitle')
			->will($this->returnValue('foo'));
		$mockModelTwo = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Repository');
		$mockModelTwo
			->expects(($this->once()))
			->method('getTitle')
			->will($this->returnValue('TYPO3.org Main Repository'));

		$this->fixture
			->expects($this->once())
			->method('findAll')
			->will($this->returnValue(array($mockModelOne, $mockModelTwo)));

		$this->assertSame($mockModelTwo, $this->fixture->findOneTypo3OrgRepository());
	}
}
