<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain\Repository;

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
		$this->mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
		/** @var $fixture \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository|\PHPUnit_Framework_MockObject_MockObject */
		$this->fixture = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository::class, array('findAll'), array($this->mockObjectManager));
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
		$mockModelOne = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Repository::class);
		$mockModelOne
			->expects(($this->once()))
			->method('getTitle')
			->will($this->returnValue('foo'));
		$mockModelTwo = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Repository::class);
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
