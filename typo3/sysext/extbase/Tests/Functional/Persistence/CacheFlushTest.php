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
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;

/**
 * Page caches flushed by persisting objects, without a request unless a test
 * sets one up. Fixture Blogs.csv: blog 1 on the storage folder 2, blog 2 on
 * the storage folder 3, whose Page TSconfig flushes page 1 and all caches,
 * and the storage folder 4. Extension movable_blog makes the "pid" of blogs
 * writable.
 */
final class CacheFlushTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
        'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/Extensions/movable_blog',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    private FrontendInterface $pageCache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CacheFlush/Blogs.csv');
        $this->pageCache = $this->get(CacheManager::class)->getCache('pages');
        $this->pageCache->set('root', ['dummy'], ['pageId_1']);
        $this->pageCache->set('storage', ['dummy'], ['pageId_2']);
        $this->pageCache->set('blog-list', ['dummy'], ['tx_blogexample_domain_model_blog_pid_2']);
        $this->pageCache->set('blog-detail', ['dummy'], ['tx_blogexample_domain_model_blog_1']);
    }

    #[Test]
    public function addingAnObjectFlushesTheCachesOfItsStoragePage(): void
    {
        $blog = new Blog();
        $blog->setTitle('New blog');
        $blog->setPid(2);
        $this->get(BlogRepository::class)->add($blog);
        $this->get(PersistenceManager::class)->persistAll();

        self::assertTrue($this->pageCache->has('root'));
        self::assertFalse($this->pageCache->has('storage'));
        self::assertFalse($this->pageCache->has('blog-list'));
        self::assertTrue($this->pageCache->has('blog-detail'));
    }

    #[Test]
    public function updatingAnObjectFlushesTheCachesOfTheRecordAndItsStoragePage(): void
    {
        $blogRepository = $this->get(BlogRepository::class);
        $blog = $blogRepository->findByUid(1);
        $blog->setTitle('Changed blog');
        $blogRepository->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        self::assertTrue($this->pageCache->has('root'));
        self::assertFalse($this->pageCache->has('storage'));
        self::assertFalse($this->pageCache->has('blog-list'));
        self::assertFalse($this->pageCache->has('blog-detail'));
    }

    #[Test]
    public function movingAnObjectFlushesTheCachesOfItsPreviousAndItsNewStoragePage(): void
    {
        $this->pageCache->set('other-storage', ['dummy'], ['pageId_4']);
        $this->pageCache->set('other-blog-list', ['dummy'], ['tx_blogexample_domain_model_blog_pid_4']);
        $blogRepository = $this->get(BlogRepository::class);
        $blog = $blogRepository->findByUid(1);
        $blog->setPid(4);
        $blogRepository->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        self::assertTrue($this->pageCache->has('root'));
        self::assertFalse($this->pageCache->has('storage'));
        self::assertFalse($this->pageCache->has('blog-list'));
        self::assertFalse($this->pageCache->has('other-storage'));
        self::assertFalse($this->pageCache->has('other-blog-list'));
        self::assertFalse($this->pageCache->has('blog-detail'));
    }

    #[Test]
    public function removingAnObjectFlushesTheCachesOfTheRecordAndItsStoragePage(): void
    {
        $blogRepository = $this->get(BlogRepository::class);
        $blogRepository->remove($blogRepository->findByUid(1));
        $this->get(PersistenceManager::class)->persistAll();

        self::assertTrue($this->pageCache->has('root'));
        self::assertFalse($this->pageCache->has('storage'));
        self::assertFalse($this->pageCache->has('blog-list'));
        self::assertFalse($this->pageCache->has('blog-detail'));
    }

    #[Test]
    public function cacheCommandsOfPageTsConfigFlushPagesButNeverAllCaches(): void
    {
        $blogRepository = $this->get(BlogRepository::class);
        $blog = $blogRepository->findByUid(2);
        $blog->setTitle('Changed blog');
        $blogRepository->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        self::assertFalse($this->pageCache->has('root'));
        self::assertTrue($this->pageCache->has('storage'));
        self::assertTrue($this->pageCache->has('blog-list'));
        self::assertTrue($this->pageCache->has('blog-detail'));
    }

    #[Test]
    public function cachesAreKeptIfAutomaticCacheClearingIsDisabled(): void
    {
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['enableAutomaticCacheClearing' => '0'],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
        $blogRepository = $this->get(BlogRepository::class);
        $blog = $blogRepository->findByUid(1);
        $blog->setTitle('Changed blog');
        $blogRepository->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        self::assertTrue($this->pageCache->has('storage'));
        self::assertTrue($this->pageCache->has('blog-list'));
        self::assertTrue($this->pageCache->has('blog-detail'));
    }
}
