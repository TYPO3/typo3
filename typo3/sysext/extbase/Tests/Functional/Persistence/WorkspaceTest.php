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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class WorkspaceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected BlogRepository $blogRepository;

    protected PostRepository $postRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceTestImport.csv');
    }

    /**
     * Minimal frontend environment to satisfy Extbase Typo3DbBackend
     */
    private function setupSubjectInFrontend(int $workspaceId = 1): void
    {
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect($workspaceId));
        GeneralUtility::setSingletonInstance(Context::class, $context);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $this->blogRepository = $this->get(BlogRepository::class);
        $this->postRepository = $this->get(PostRepository::class);
        // ConfigurationManager is used by PersistenceManager to retrieve configuration.
        // We set a proper extensionName and pluginName for the ConfigurationManager singleton
        // here, to not run into warnings due to incomplete test setup.
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration([
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $configurationManager->setRequest($request);
    }

    /**
     * Minimal backend user configuration to satisfy Extbase Typo3DbBackend
     */
    private function setupSubjectInBackend(int $workspaceId = 1): void
    {
        $backendUser = new BackendUserAuthentication();
        $backendUser->workspace = $workspaceId;
        $GLOBALS['BE_USER'] = $backendUser;
        $context = new Context();
        $context->setAspect('backend.user', new UserAspect($backendUser));
        $context->setAspect('workspace', new WorkspaceAspect($workspaceId));
        GeneralUtility::setSingletonInstance(Context::class, $context);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $this->blogRepository = $this->get(BlogRepository::class);
        // ConfigurationManager is used by PersistenceManager to retrieve configuration.
        // We set a proper extensionName and pluginName for the ConfigurationManager singleton
        // here, to not run into warnings due to incomplete test setup.
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration([
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $configurationManager->setRequest($request);
    }

    public static function contextDataProvider(): array
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

    #[DataProvider('contextDataProvider')]
    #[Test]
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
        self::assertCount(6, $query->execute());
    }

    #[DataProvider('contextDataProvider')]
    #[Test]
    public function fetchingHiddenBlogInWorkspace(string $context): void
    {
        // Set up Context for Workspace=1
        if ($context === 'FE') {
            $this->setupSubjectInFrontend();
        } else {
            $this->setupSubjectInBackend();
        }

        $query = $this->blogRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([0]);
        $query->matching(
            $query->logicalOr(
                $query->like('title', '%Blog2%'),
                $query->like('title', '%Blog4%'),
                $query->like('title', '%Blog6%')
            )
        );
        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);

        // Respect hidden flags, only show the item that was hidden in live, but is now visible in workspace
        $querySettings->setIgnoreEnableFields(false);
        $items = $query->execute();
        $foundItems = [];
        foreach ($items as $item) {
            $foundItems[] = $item->getTitle();
        }
        self::assertEquals(['WorkspaceOverlay Blog6Enabled'], $foundItems);

        // Allow hidden records to show up (resulting in 3 blog items)
        $querySettings->setIgnoreEnableFields(true);
        $items = $query->execute();
        $foundItems = [];
        foreach ($items as $item) {
            $foundItems[] = $item->getTitle();
        }
        self::assertEquals([
            'WorkspaceOverlay Blog2HiddenInWorkspace',
            'WorkspaceOverlay Blog4HiddenInLiveAndWorkspace',
            'WorkspaceOverlay Blog6Enabled',
        ], $foundItems);
    }

    #[DataProvider('contextDataProvider')]
    #[Test]
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
        // Check second-last blog was enabled in workspace preview
        array_pop($blogs);
        $lastBlog = array_pop($blogs);
        self::assertSame(6, $lastBlog->getUid());
        self::assertSame('WorkspaceOverlay Blog6Enabled', $lastBlog->getTitle());
    }

    #[DataProvider('contextDataProvider')]
    #[Test]
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

    #[Test]
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

    #[Test]
    public function fetchingBlogReturnsOverlaidWorkspaceVersionForManyToManyRelations(): void
    {
        $this->setupSubjectInFrontend();
        $query = $this->blogRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $query->matching($query->equals('uid', 1));
        /** @var Blog $blog */
        $blog = $query->execute()->getFirst();
        self::assertEquals('WorkspaceOverlay Blog1', $blog->getTitle());
        self::assertCount(2, $blog->getCategories());
    }

    #[Test]
    public function limitConstraintReturnsRelevantPosts(): void
    {
        $this->setupSubjectInFrontend();
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $query->setOrderings(['uid' => QueryInterface::ORDER_DESCENDING]);
        // Ensure the limit is applied after the overlay of draft records
        $query->setLimit(3);
        $posts = $query->execute()->toArray();
        self::assertCount(3, $posts);
    }
}
