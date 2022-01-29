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

use ExtbaseTeam\BlogExample\Domain\Model\Blog;
use ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class AddTest extends FunctionalTestCase
{
    /**
     * @var PersistenceManager
     */
    protected $persistentManager;

    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var BlogRepository
     */
    protected $blogRepository;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->persistentManager = $this->getContainer()->get(PersistenceManager::class);
        $this->blogRepository = $this->getContainer()->get(BlogRepository::class);
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
    }

    /**
     * @test
     */
    public function addSimpleObjectTest(): void
    {
        $newBlogTitle = 'aDi1oogh';
        $newBlog = new Blog();
        $newBlog->setTitle($newBlogTitle);

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_blog');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $newBlogCount = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_blog')
            ->where(
                $queryBuilder->expr()->eq(
                    'title',
                    $queryBuilder->createNamedParameter($newBlogTitle, \PDO::PARAM_STR)
                )
            )
            ->executeQuery()
            ->fetchOne();
        self::assertEquals(1, $newBlogCount);
    }

    /**
     * @test
     */
    public function addObjectSetsDefaultLanguageTest(): void
    {
        $newBlogTitle = 'aDi1oogh';
        $newBlog = new Blog();
        $newBlog->setTitle($newBlogTitle);

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_blog');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $newBlogRecord = $queryBuilder
            ->select('*')
            ->from('tx_blogexample_domain_model_blog')
            ->where(
                $queryBuilder->expr()->eq(
                    'title',
                    $queryBuilder->createNamedParameter($newBlogTitle, \PDO::PARAM_STR)
                )
            )
            ->executeQuery()
            ->fetchAssociative();
        self::assertEquals(0, $newBlogRecord['sys_language_uid']);
    }

    /**
     * @test
     */
    public function addObjectSetsDefinedLanguageTest(): void
    {
        $newBlogTitle = 'aDi1oogh';
        $newBlog = new Blog();
        $newBlog->setTitle($newBlogTitle);
        $newBlog->_setProperty('_languageUid', -1);

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_blog');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $newBlogRecord = $queryBuilder
            ->select('*')
            ->from('tx_blogexample_domain_model_blog')
            ->where(
                $queryBuilder->expr()->eq(
                    'title',
                    $queryBuilder->createNamedParameter($newBlogTitle, \PDO::PARAM_STR)
                )
            )
            ->executeQuery()
            ->fetchAssociative();
        self::assertEquals(-1, $newBlogRecord['sys_language_uid']);
    }

    /**
    * @test
    */
    public function addObjectSetsNullAsNullForSimpleTypes(): void
    {
        $newBlogTitle = 'aDi1oogh';
        $newBlog = new Blog();
        $newBlog->setTitle($newBlogTitle);
        $newBlog->setSubtitle('subtitle');

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        // make sure null can be set explicitly
        $insertedBlog = $this->blogRepository->findByUid(1);
        $insertedBlog->setSubtitle(null);
        $this->blogRepository->update($insertedBlog);
        $this->persistentManager->persistAll();

        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_blog');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $newBlogRecord = $queryBuilder
            ->select('*')
            ->from('tx_blogexample_domain_model_blog')
            ->where(
                $queryBuilder->expr()->eq(
                    'subtitle',
                    $queryBuilder->createNamedParameter($newBlogTitle, \PDO::PARAM_STR)
                )
            )
            ->executeQuery()
            ->fetchAssociative();
        self::assertNull($newBlogRecord['subtitle'] ?? null);
    }
}
