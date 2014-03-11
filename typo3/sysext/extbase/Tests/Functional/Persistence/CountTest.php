<?php
namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Felix Oertel <typo3@foertel.com>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class CountTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	/**
	 * @var int number of all records
	 */
	protected $numberOfRecordsInFixture = 11;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
	 */
	protected $persistentManager;

	/**
	 * @var array
	 */
	protected $testExtensionsToLoad = array('typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example');

	/**
	 * @var array
	 */
	protected $coreExtensionsToLoad = array('extbase', 'fluid');

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Repository
	 */
	protected $blogRepository;

	/**
	 * Sets up this test suite.
	 */
	public function setUp() {
		parent::setUp();

		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.xml');
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags.xml');
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-tag-mm.xml');

		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->persistentManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');
		$this->postRepository = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Repository\\PostRepository');
	}

	/**
	 * @test
	 */
	public function simpleCountTest() {
		$query = $this->postRepository->createQuery();
		$this->assertSame($this->numberOfRecordsInFixture, $query->count());
	}

	/**
	 * @test
	 */
	public function offsetCountTest() {
		$query = $this->postRepository->createQuery();

		$query->setOffset(6);

		$this->assertSame(($this->numberOfRecordsInFixture - 6), $query->count());
	}

	/**
	 * @test
	 */
	public function exceedingOffsetCountTest() {
		$query = $this->postRepository->createQuery();

		$query->setOffset(($this->numberOfRecordsInFixture + 5));

		$this->assertSame(0, $query->count());
	}

	/**
	 * @test
	 */
	public function limitCountTest() {
		$query = $this->postRepository->createQuery();

		$query->setLimit(4);

		$this->assertSame(4, $query->count());
	}

	/**
	 * @test
	 */
	public function limitAndOffsetCountTest() {
		$query = $this->postRepository->createQuery();

		$query
			->setOffset(($this->numberOfRecordsInFixture - 3))
			->setLimit(4);

		$this->assertSame(3, $query->count());
	}

	/**
	 * @test
	 */
	public function inConstraintCountTest() {
		$query = $this->postRepository->createQuery();

		$query->matching(
			$query->in('uid', array(1,2,3))
		);

		$this->assertSame(3, $query->count());
	}

	/**
	 * Test if count works with subproperties in subselects.
	 *
	 * @test
	 */
	public function subpropertyJoinCountTest() {
		$query = $this->postRepository->createQuery();

		$query->matching(
			$query->equals('blog.title', 'Blog1')
		);

		$this->assertSame(10, $query->count());
	}

	/**
	 * Test if count works with subproperties in multiple left join.
	 *
	 * @test
	 */
	public function subpropertyInMultipleLeftJoinCountTest() {
		$query = $this->postRepository->createQuery();

		$query->matching(
			$query->logicalOr(
				$query->equals('tags.uid', 1),
				$query->equals('tags.uid', 2)
			)
		);

		$this->assertSame(10, $query->count());
	}
}
