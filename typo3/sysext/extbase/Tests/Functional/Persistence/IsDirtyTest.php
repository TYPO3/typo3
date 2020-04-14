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

use ExtbaseTeam\BlogExample\Domain\Model\Administrator;
use ExtbaseTeam\BlogExample\Domain\Repository\AdministratorRepository;
use ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class IsDirtyTest extends FunctionalTestCase
{

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
     * @var \ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository
     */
    protected $blogRepository;

    /**
     * @var \ExtbaseTeam\BlogExample\Domain\Repository\AdministratorRepository
     */
    protected $adminRepository;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-post-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-tag-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/persons.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/fe_users.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/fe_groups.xml');

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->blogRepository = $this->objectManager->get(BlogRepository::class);
        $this->adminRepository = $this->objectManager->get(AdministratorRepository::class);
    }

    /**
     * @test
     */
    public function objectFetchedFromDbIsNotDirty()
    {
        $blog = $this->blogRepository->findByUid(3);
        self::assertFalse($blog->_isDirty());
    }

    /**
     * @test
     */
    public function lazyLoadingProxyReplacedByRealInstanceIsNotDirty()
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
    public function lazyLoadingProxyReplacedByWrongInstanceIsDirty()
    {
        $blog = $this->blogRepository->findByUid(3);
        self::assertInstanceOf(LazyLoadingProxy::class, $blog->getAdministrator()); //precondition

        $blog->setAdministrator(new Administrator());
        self::assertTrue($blog->_isDirty());
    }

    /**
     * @test
     */
    public function realInstanceReplacedByLazyLoadingProxyIsNotDirty()
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
    public function lazyLoadingProxyByWrongLazyLoadingProxyIsDirtyAndUpdated()
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
