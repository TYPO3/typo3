<?php
namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

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

		$objectStorage = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
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

		$objectStorage = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
		$objectStorage->attach($blog1);
		$objectStorage->attach($blog2);

		$inQuery = $this->postRepository->createQuery();

		$inQuery->matching(
			$inQuery->in('blog', $objectStorage)
		);

		$this->assertSame(11, $inQuery->count());

		$newObjectStorage = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
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
