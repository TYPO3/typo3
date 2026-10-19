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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Administrator;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;

/**
 * Assigning the lazy relation value of one object to another object writes the relation, like
 * DataHandler does when a relation is assigned and saved.
 * https://forge.typo3.org/issues/94486
 *
 * Blog::$administrator is a native lazy proxy (#[Lazy]). Blog 3 has administrator 3, blog 1 has none.
 */
final class LazyProxyAssignmentTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/IsDirtyTestImport.csv');
        $request = new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    /**
     * A lazy proxy that is still uninitialized and is assigned to another blog is written as the
     * relation of that blog, like any other assigned object.
     */
    #[Test]
    public function assigningUninitializedLazyProxyToAnotherBlogWritesTheRelation(): void
    {
        // Given: the uninitialized lazy administrator of blog 3
        $blogRepository = $this->get(BlogRepository::class);
        $blog3 = $blogRepository->findByUid(3);
        $blog1 = $blogRepository->findByUid(1);
        $administrator = $blog3->getAdministrator();
        self::assertTrue(new \ReflectionClass(Administrator::class)->isUninitializedLazyObject($administrator));

        // When: it is assigned to blog 1 and blog 1 is updated and persisted
        $blog1->setAdministrator($administrator);
        $blogRepository->update($blog1);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: blog 1 references administrator 3
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_blog');
        self::assertSame(
            3,
            (int)$connection->select(['administrator'], 'tx_blogexample_domain_model_blog', ['uid' => 1])->fetchOne()
        );
    }

    /**
     * An initialized lazy proxy assigned to another blog is written as the relation of that blog.
     */
    #[Test]
    public function assigningInitializedLazyProxyToAnotherBlogWritesTheRelation(): void
    {
        // Given: the lazy administrator of blog 3, initialized by reading a property
        $blogRepository = $this->get(BlogRepository::class);
        $blog3 = $blogRepository->findByUid(3);
        $blog1 = $blogRepository->findByUid(1);
        $administrator = $blog3->getAdministrator();
        $administrator->getUsername();
        self::assertFalse(new \ReflectionClass(Administrator::class)->isUninitializedLazyObject($administrator));

        // When: it is assigned to blog 1 and blog 1 is updated and persisted
        $blog1->setAdministrator($administrator);
        $blogRepository->update($blog1);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: blog 1 references administrator 3
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_blog');
        self::assertSame(
            3,
            (int)$connection->select(['administrator'], 'tx_blogexample_domain_model_blog', ['uid' => 1])->fetchOne()
        );
    }

    /**
     * The blog the proxy was taken from is not changed by the assignment.
     */
    #[Test]
    public function assigningUninitializedLazyProxyToAnotherBlogLeavesTheOriginalBlogUnchanged(): void
    {
        // Given: the uninitialized lazy administrator of blog 3
        $blogRepository = $this->get(BlogRepository::class);
        $blog3 = $blogRepository->findByUid(3);
        $blog1 = $blogRepository->findByUid(1);
        $administrator = $blog3->getAdministrator();

        // When: it is assigned to blog 1 and blog 1 is updated and persisted
        $blog1->setAdministrator($administrator);
        $blogRepository->update($blog1);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: blog 3 still references administrator 3
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_blog');
        self::assertSame(
            3,
            (int)$connection->select(['administrator'], 'tx_blogexample_domain_model_blog', ['uid' => 3])->fetchOne()
        );
    }

    /**
     * An uninitialized lazy proxy assigned to a new blog is written as the relation of that blog
     * when the blog is added and persisted.
     */
    #[Test]
    public function assigningUninitializedLazyProxyToNewBlogWritesTheRelation(): void
    {
        // Given: the uninitialized lazy administrator of blog 3
        $blogRepository = $this->get(BlogRepository::class);
        $administrator = $blogRepository->findByUid(3)->getAdministrator();
        self::assertTrue(new \ReflectionClass(Administrator::class)->isUninitializedLazyObject($administrator));

        // When: it is assigned to a new blog that is added and persisted
        $newBlog = new Blog();
        $newBlog->setTitle('New blog');
        $newBlog->setAdministrator($administrator);
        $blogRepository->add($newBlog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the new blog references administrator 3
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_blog');
        self::assertSame(
            3,
            (int)$connection->select(['administrator'], 'tx_blogexample_domain_model_blog', ['uid' => $newBlog->getUid()])->fetchOne()
        );
    }
}
