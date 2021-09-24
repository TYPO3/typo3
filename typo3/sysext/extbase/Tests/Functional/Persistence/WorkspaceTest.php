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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class WorkspaceTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['workspaces'];

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
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet('EXT:extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
        $this->importDataSet('EXT:extbase/Tests/Functional/Persistence/Fixtures/posts.xml');
        $this->importDataSet('EXT:extbase/Tests/Functional/Persistence/Fixtures/categories.xml');
        $this->importDataSet('EXT:extbase/Tests/Functional/Persistence/Fixtures/category-mm.xml');
    }

    public function contextDataProvider(): array
    {
        return [
            'test frontend context' => [
                'context' => 'FE',
            ],
            'test backend context' => [
                'context' => 'BE',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider contextDataProvider
     * @param string $context
     */
    public function countReturnsCorrectNumberOfBlogs(string $context): void
    {
        if ($context === 'FE') {
            $this->setupSubjectInFrontend();
        } else {
            $this->setupSubjectInBackend();
        }

        $query = $this->blogRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);

        // In workspace all records need to be fetched, thus enableFields is ignored
        // This means we select even hidden (but not deleted) records for count()
        self::assertSame(5, $query->execute()->count());
    }

    /**
     * @test
     * @dataProvider contextDataProvider
     * @param string $context
     */
    public function fetchingAllBlogsReturnsCorrectNumberOfBlogs(string $context): void
    {
        if ($context === 'FE') {
            $this->setupSubjectInFrontend();
        } else {
            $this->setupSubjectInBackend();
        }

        $query = $this->blogRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);

        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        $blogs = $query->execute()->toArray();

        self::assertCount(4, $blogs);

        // Check first blog was overlaid with workspace preview
        $firstBlog = array_shift($blogs);
        self::assertSame(1, $firstBlog->getUid());
        self::assertSame('WorkspaceOverlay Blog1', $firstBlog->getTitle());

        // Check last blog was enabled in workspace preview
        $lastBlog = array_pop($blogs);
        self::assertSame(6, $lastBlog->getUid());
        self::assertSame('WorkspaceOverlay Blog6Enabled', $lastBlog->getTitle());
    }

    /**
     * @test
     * @dataProvider contextDataProvider
     * @param string $context
     */
    public function fetchingBlogReturnsOverlaidWorkspaceVersionForRelations(string $context): void
    {
        if ($context === 'FE') {
            $this->setupSubjectInFrontend();
        } else {
            $this->setupSubjectInBackend();
        }

        $query = $this->blogRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([0]);

        $query->matching($query->equals('uid', 1));

        $blog = $query->execute()->getFirst();
        $posts = $blog->getPosts()->toArray();

        self::assertSame('WorkspaceOverlay Blog1', $blog->getTitle());
        self::assertCount(10, (array)$posts);
        self::assertSame('WorkspaceOverlay Post1', $posts[0]->getTitle());
        self::assertSame('WorkspaceOverlay Post2', $posts[1]->getTitle());
        self::assertSame('WorkspaceOverlay Post3', $posts[2]->getTitle());
    }

    /**
     * @test
     */
    public function fetchingBlogReturnsManyToManyRelationsInLiveWorkspace(): void
    {
        // Simulate LIVE workspace -> 3 relations
        $this->setupSubjectInFrontend(0);
        $query = $this->blogRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $query->matching($query->equals('uid', 1));

        /** @var Blog $blog */
        $blog = $query->execute()->getFirst();
        self::assertEquals('Blog1', $blog->getTitle());
        self::assertCount(3, $blog->getCategories());
    }

    /**
     * @test
     */
    public function fetchingBlogReturnsOverlaidWorkspaceVersionForManyToManyRelations(): void
    {
        $this->setupSubjectInFrontend(1);
        $query = $this->blogRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $query->matching($query->equals('uid', 1));

        /** @var Blog $blog */
        $blog = $query->execute()->getFirst();
        self::assertEquals('WorkspaceOverlay Blog1', $blog->getTitle());
        self::assertCount(2, $blog->getCategories());
    }

    /**
     * Minimal frontend environment to satisfy Extbase Typo3DbBackend
     */
    protected function setupSubjectInFrontend(int $workspaceId = 1): void
    {
        $context = new Context(
            [
                'workspace' => new WorkspaceAspect($workspaceId),
            ]
        );
        GeneralUtility::setSingletonInstance(Context::class, $context);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $this->blogRepository = $this->getContainer()->get(BlogRepository::class);
    }

    /**
     * Minimal backend user configuration to satisfy Extbase Typo3DbBackend
     */
    protected function setupSubjectInBackend(int $workspaceId = 1): void
    {
        $backendUser = new BackendUserAuthentication();
        $backendUser->workspace = $workspaceId;
        $GLOBALS['BE_USER'] = $backendUser;
        $context = new Context(
            [
                'backend.user' => new UserAspect($backendUser),
                'workspace' => new WorkspaceAspect($workspaceId),
            ]
        );
        GeneralUtility::setSingletonInstance(Context::class, $context);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $this->blogRepository = $this->getContainer()->get(BlogRepository::class);
    }
}
