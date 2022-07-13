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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use ExtbaseTeam\BlogExample\Domain\Model\Administrator;
use ExtbaseTeam\BlogExample\Domain\Repository\AdministratorRepository;
use ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class IsDirtyTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var BlogRepository
     */
    protected $blogRepository;

    /**
     * @var AdministratorRepository
     */
    protected $adminRepository;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/blogs.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/posts.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/post-post-mm.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/tags.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/tags-mm.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/post-tag-mm.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/persons.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/fe_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/fe_groups.csv');

        $this->blogRepository = $this->get(BlogRepository::class);
        $this->adminRepository = $this->get(AdministratorRepository::class);
    }

    /**
     * @test
     */
    public function objectFetchedFromDbIsNotDirty(): void
    {
        $blog = $this->blogRepository->findByUid(3);
        self::assertFalse($blog->_isDirty());
    }

    /**
     * @test
     */
    public function lazyLoadingProxyReplacedByRealInstanceIsNotDirty(): void
    {
        $blog = $this->blogRepository->findByUid(3);
        self::assertInstanceOf(LazyLoadingProxy::class, $blog->getAdministrator()); // precondition

        $admin = $this->adminRepository->findByUid(3);
        self::assertInstanceOf(Administrator::class, $admin); // precondition

        $blog->setAdministrator($admin);
        self::assertFalse($blog->_isDirty());
    }

    /**
     * @test
     */
    public function lazyLoadingProxyReplacedByWrongInstanceIsDirty(): void
    {
        $blog = $this->blogRepository->findByUid(3);
        self::assertInstanceOf(LazyLoadingProxy::class, $blog->getAdministrator()); //precondition

        $blog->setAdministrator(new Administrator());
        self::assertTrue($blog->_isDirty());
    }

    /**
     * @test
     */
    public function realInstanceReplacedByLazyLoadingProxyIsNotDirty(): void
    {
        $blog = $this->blogRepository->findByUid(3);
        $lazyLoadingProxy = $blog->getAdministrator();
        self::assertInstanceOf(LazyLoadingProxy::class, $lazyLoadingProxy); //precondition

        $admin = $this->adminRepository->findByUid(3);
        self::assertInstanceOf(Administrator::class, $admin); // precondition

        $blog->setAdministrator($admin);
        $blog->_memorizeCleanState();

        $blog->_setProperty('administrator', $lazyLoadingProxy);
        self::assertFalse($blog->_isDirty());
    }

    /**
     * @test
     */
    public function lazyLoadingProxyByWrongLazyLoadingProxyIsDirtyAndUpdated(): void
    {
        $blogOne = $this->blogRepository->findByUid(3);
        self::assertInstanceOf(LazyLoadingProxy::class, $blogOne->getAdministrator()); //precondition

        $blogTwo = $this->blogRepository->findByUid(2);
        self::assertInstanceOf(LazyLoadingProxy::class, $blogTwo->getAdministrator()); //precondition

        $blogOne->_setProperty('administrator', $blogTwo->getAdministrator());
        self::assertTrue($blogOne->_isDirty());

        $this->blogRepository->update($blogOne);

        $updatedBlogOne = $this->blogRepository->findByUid(3);
        self::assertSame($updatedBlogOne->getAdministrator()->getUid(), $blogTwo->getAdministrator()->getUid());
    }
}
