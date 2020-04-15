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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository;
use ExtbaseTeam\BlogExample\Domain\Repository\PostRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class InTest extends FunctionalTestCase
{
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
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager
     */
    protected $objectManager;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-tag-mm.xml');

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->blogRepository = $this->objectManager->get(BlogRepository::class);
        $this->postRepository = $this->objectManager->get(PostRepository::class);
    }

    /**
     * @test
     */
    public function inConditionWorksWithArrayOfObjects()
    {
        $blog1 = $this->blogRepository->findByUid(1);
        $blog2 = $this->blogRepository->findByUid(2);

        $inQuery = $this->postRepository->createQuery();

        $inQuery->matching(
            $inQuery->in('blog', [$blog1, $blog2])
        );

        self::assertSame(11, $inQuery->count());
    }

    /**
     * @test
     */
    public function inConditionWorksWithArrayOfObjectsOnSecondCall()
    {
        $blog1 = $this->blogRepository->findByUid(1);
        $blog2 = $this->blogRepository->findByUid(2);

        $inQuery = $this->postRepository->createQuery();

        $inQuery->matching(
            $inQuery->in('blog', [$blog1, $blog2])
        );

        self::assertSame(11, $inQuery->count());

        $newInQuery = $this->postRepository->createQuery();

        $newInQuery->matching(
            $newInQuery->in('blog', [$blog1])
        );

        self::assertSame(10, $newInQuery->count());
    }

    /**
     * @test
     */
    public function inConditionWorksWithObjectStorage()
    {
        $blog1 = $this->blogRepository->findByUid(1);
        $blog2 = $this->blogRepository->findByUid(2);

        $objectStorage = $this->objectManager->get(ObjectStorage::class);
        $objectStorage->attach($blog1);
        $objectStorage->attach($blog2);

        $inQuery = $this->postRepository->createQuery();

        $inQuery->matching(
            $inQuery->in('blog', $objectStorage)
        );

        self::assertSame(11, $inQuery->count());
    }

    /**
     * @test
     */
    public function inConditionWorksWithObjectStorageOnSecondCall()
    {
        $blog1 = $this->blogRepository->findByUid(1);
        $blog2 = $this->blogRepository->findByUid(2);

        $objectStorage = $this->objectManager->get(ObjectStorage::class);
        $objectStorage->attach($blog1);
        $objectStorage->attach($blog2);

        $inQuery = $this->postRepository->createQuery();

        $inQuery->matching(
            $inQuery->in('blog', $objectStorage)
        );

        self::assertSame(11, $inQuery->count());

        $newObjectStorage = $this->objectManager->get(ObjectStorage::class);
        $newObjectStorage->attach($blog1);

        $newInQuery = $this->postRepository->createQuery();

        $newInQuery->matching(
            $newInQuery->in('blog', $newObjectStorage)
        );

        self::assertSame(10, $newInQuery->count());
    }

    /**
     * @test
     */
    public function inConditionWorksWithQueryResult()
    {
        $query = $this->blogRepository->createQuery();
        $query->matching($query->in('uid', [1, 2]));
        $queryResult = $query->execute();

        $inQuery = $this->postRepository->createQuery();

        $inQuery->matching(
            $inQuery->in('blog', $queryResult)
        );

        self::assertSame(11, $inQuery->count());
    }

    /**
     * @test
     */
    public function inConditionWorksWithQueryResultOnSecondCall()
    {
        $query = $this->blogRepository->createQuery();
        $query->matching($query->in('uid', [1, 2]));
        $queryResult = $query->execute();

        $inQuery = $this->postRepository->createQuery();

        $inQuery->matching(
            $inQuery->in('blog', $queryResult)
        );

        self::assertSame(11, $inQuery->count());

        $newInQuery = $this->postRepository->createQuery();

        $newInQuery->matching(
            $newInQuery->in('blog', $queryResult)
        );

        self::assertSame(11, $newInQuery->count());
    }

    /**
     * @test
     */
    public function inConditionWorksWithLazyObjectStorage()
    {
        $blog = $this->blogRepository->findByUid(1);

        self::assertInstanceOf(
            LazyObjectStorage::class,
            $blog->getPosts()
        );

        $inQuery = $this->postRepository->createQuery();

        $inQuery->matching(
            $inQuery->in('uid', $blog->getPosts())
        );

        self::assertSame(10, $inQuery->count());
    }

    /**
     * @test
     */
    public function inConditionWorksWithLazyObjectStorageOnSecondCall()
    {
        $blog = $this->blogRepository->findByUid(1);

        self::assertInstanceOf(
            LazyObjectStorage::class,
            $blog->getPosts()
        );

        $inQuery = $this->postRepository->createQuery();

        $inQuery->matching(
            $inQuery->in('uid', $blog->getPosts())
        );

        self::assertSame(10, $inQuery->count());

        $newInQuery = $this->postRepository->createQuery();

        $newInQuery->matching(
            $newInQuery->in('uid', $blog->getPosts())
        );

        self::assertSame(10, $newInQuery->count());
    }
}
