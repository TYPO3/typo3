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

class InTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	/**
	 * @var \ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository
	 */
	protected $blogRepository;

	/**
	 * @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository
	 */
	protected $postRepository;

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
		$this->blogRepository = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Repository\\BlogRepository');
		$this->postRepository = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Repository\\PostRepository');

	}

	/**
	 * @test
	 */
	public function inConditionWorksWithArrayOfObjects() {
		$blog1 = $this->blogRepository->findByUid(1);
		$blog2 = $this->blogRepository->findByUid(2);

		$inQuery = $this->postRepository->createQuery();

		$inQuery->matching(
			$inQuery->in('blog', array($blog1, $blog2))
		);

		$this->assertSame(11, $inQuery->count());
	}

	/**
	 * @test
	 */
	public function inConditionWorksWithArrayOfObjectsOnSecondCall() {
		$blog1 = $this->blogRepository->findByUid(1);
		$blog2 = $this->blogRepository->findByUid(2);

		$inQuery = $this->postRepository->createQuery();

		$inQuery->matching(
			$inQuery->in('blog', array($blog1, $blog2))
		);

		$this->assertSame(11, $inQuery->count());

		$newInQuery = $this->postRepository->createQuery();

		$newInQuery->matching(
			$newInQuery->in('blog', array($blog1))
		);

		$this->assertSame(10, $newInQuery->count());
	}

	/**
	 * @test
	 */
	public function inConditionWorksWithObjectStorage() {
		$blog1 = $this->blogRepository->findByUid(1);
		$blog2 = $this->blogRepository->findByUid(2);

		$objectStorage = $this->objectManager->create('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
		$objectStorage->attach($blog1);
		$objectStorage->attach($blog2);

		$inQuery = $this->postRepository->createQuery();

		$inQuery->matching(
			$inQuery->in('blog', $objectStorage)
		);

		$this->assertSame(11, $inQuery->count());
	}

	/**
	 * @test
	 */
	public function inConditionWorksWithObjectStorageOnSecondCall() {
		$blog1 = $this->blogRepository->findByUid(1);
		$blog2 = $this->blogRepository->findByUid(2);

		$objectStorage = $this->objectManager->create('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
		$objectStorage->attach($blog1);
		$objectStorage->attach($blog2);

		$inQuery = $this->postRepository->createQuery();

		$inQuery->matching(
			$inQuery->in('blog', $objectStorage)
		);

		$this->assertSame(11, $inQuery->count());

		$newObjectStorage = $this->objectManager->create('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
		$newObjectStorage->attach($blog1);

		$newInQuery = $this->postRepository->createQuery();

		$newInQuery->matching(
			$newInQuery->in('blog', $newObjectStorage)
		);

		$this->assertSame(10, $newInQuery->count());
	}

	/**
	 * @test
	 */
	public function inConditionWorksWithQueryResult() {
		$queryResult = $this->blogRepository->findAll();

		$inQuery = $this->postRepository->createQuery();

		$inQuery->matching(
			$inQuery->in('blog', $queryResult)
		);

		$this->assertSame(11, $inQuery->count());
	}


	/**
	 * @test
	 */
	public function inConditionWorksWithQueryResultOnSecondCall() {
		$queryResult = $this->blogRepository->findAll();

		$inQuery = $this->postRepository->createQuery();

		$inQuery->matching(
			$inQuery->in('blog', $queryResult)
		);

		$this->assertSame(11, $inQuery->count());

		$newInQuery = $this->postRepository->createQuery();

		$newInQuery->matching(
			$newInQuery->in('blog', $queryResult)
		);

		$this->assertSame(11, $newInQuery->count());
	}

	/**
	 * @test
	 */
	public function inConditionWorksWithLazyObjectStorage() {
		$blog = $this->blogRepository->findByUid(1);

		$this->assertInstanceOf(
			'\TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage',
			$blog->getPosts()
		);

		$inQuery = $this->postRepository->createQuery();

		$inQuery->matching(
			$inQuery->in('uid', $blog->getPosts())
		);

		$this->assertSame(10, $inQuery->count());
	}

	/**
	 * @test
	 */
	public function inConditionWorksWithLazyObjectStorageOnSecondCall() {
		$blog = $this->blogRepository->findByUid(1);

		$this->assertInstanceOf(
			'\TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage',
			$blog->getPosts()
		);

		$inQuery = $this->postRepository->createQuery();

		$inQuery->matching(
			$inQuery->in('uid', $blog->getPosts())
		);

		$this->assertSame(10, $inQuery->count());


		$newInQuery = $this->postRepository->createQuery();

		$newInQuery->matching(
			$newInQuery->in('uid', $blog->getPosts())
		);

		$this->assertSame(10, $newInQuery->count());
	}
}
