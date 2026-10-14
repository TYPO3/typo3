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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\BlogWithSetterOnlyPosts;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class CascadeRemoveTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTestImport.csv');
        $request = new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->workspace = 0;
    }

    /**
     * A form submits blog 1 again with all ten posts it already has. The blog model has only `setPosts()` for its
     * posts, so property mapping replaces the loaded storage with a new one that holds the same posts, which Extbase
     * must not treat as removal of the posts: they carry `#[Cascade('remove')]` and stay in the database.
     */
    #[Test]
    public function resubmittingUnchangedObjectStorageViaPropertyMappingKeepsCascadedChildren(): void
    {
        // Given: blog 1 has posts and the form sends every one of them again
        $before = $this->fetchActivePostUidsOfBlog(1);
        self::assertNotEmpty($before);

        $source = [
            '__identity' => 1,
            'title' => 'Edited title',
            'posts' => array_map(static fn(int $uid): array => ['__identity' => $uid], $before),
        ];
        $configuration = new PropertyMappingConfiguration();
        $configuration->allowAllProperties();
        $configuration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);
        $configuration->forProperty('posts')->allowAllProperties();
        $configuration->forProperty('posts.*')->allowAllProperties();

        // When: the mapped blog is persisted
        $blog = $this->get(PropertyMapper::class)->convert($source, BlogWithSetterOnlyPosts::class, $configuration);
        self::assertInstanceOf(BlogWithSetterOnlyPosts::class, $blog);
        $this->get(PersistenceManager::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: no post is deleted
        self::assertSame($before, $this->fetchActivePostUidsOfBlog(1));
    }

    /**
     * Blog 1 gets a new storage that holds the same posts as the loaded one, as a controller does that rebuilds the
     * collection. The posts carry `#[Cascade('remove')]` and must stay in the database.
     */
    #[Test]
    public function replacingObjectStorageWithEqualContentKeepsCascadedChildren(): void
    {
        // Given: blog 1 has posts and a new storage holds the same posts
        $before = $this->fetchActivePostUidsOfBlog(1);
        $blog = $this->get(BlogRepository::class)->findByUid(1);
        $postRepository = $this->get(PostRepository::class);
        /** @var ObjectStorage<Post> $posts */
        $posts = new ObjectStorage();
        foreach ($before as $uid) {
            $posts->attach($postRepository->findByUid($uid));
        }
        // Initializing the lazy storage memorizes its clean state, which the removal detection compares against
        $blog->getPosts()->count();

        // When: the new storage replaces the old one and the blog is persisted
        $blog->setPosts($posts);
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: no post is deleted
        self::assertSame($before, $this->fetchActivePostUidsOfBlog(1));
    }

    /**
     * Blog 1 gets a new storage that lacks its last post. Like Doctrine's orphanRemoval, `#[Cascade('remove')]`
     * deletes exactly the post that is no longer in the collection and keeps all others. The loaded storage is
     * initialized before it is replaced.
     */
    #[Test]
    public function replacingInitializedObjectStorageWithoutOnePostRemovesOnlyThatPost(): void
    {
        // Given: blog 1 has posts, its storage is initialized and a new storage lacks the last post
        $before = $this->fetchActivePostUidsOfBlog(1);
        self::assertNotEmpty($before);
        $blog = $this->get(BlogRepository::class)->findByUid(1);
        $postRepository = $this->get(PostRepository::class);
        $expected = $before;
        $removedUid = array_pop($expected);
        /** @var ObjectStorage<Post> $posts */
        $posts = new ObjectStorage();
        foreach ($expected as $uid) {
            $posts->attach($postRepository->findByUid($uid));
        }
        $blog->getPosts()->count();

        // When: the new storage replaces the old one and the blog is persisted
        $blog->setPosts($posts);
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: only the post missing from the new storage is deleted
        self::assertSame($expected, $this->fetchActivePostUidsOfBlog(1), 'Post ' . $removedUid . ' should be the only deleted post');
    }

    /**
     * @return int[]
     */
    private function fetchActivePostUidsOfBlog(int $blogUid): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder->select('uid')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq('blog', $blogUid),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn();
        return array_map(intval(...), $rows);
    }
}
