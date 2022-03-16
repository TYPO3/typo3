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
use ExtbaseTeam\BlogExample\Domain\Model\Post;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class LazyObjectStorageTest extends FunctionalTestCase
{
    protected $coreExtensionsToLoad = ['extbase'];
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/blogs.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/posts.csv');
    }

    /**
     * @test
     */
    public function serializeAndUnserialize(): void
    {
        $blog = new Blog();
        $blog->_setProperty('uid', 1);
        $blog->_setProperty('posts', new LazyObjectStorage($blog, 'posts', 10));

        $serialized = serialize($blog->getPosts());

        self::assertFalse(str_contains($serialized, 'dataMapper'), 'Assert that serialized object string does not contain dataMapper');

        /** @var LazyObjectStorage $postsProxy */
        $postsProxy = unserialize($serialized, ['allowed_classes' => true]);
        self::assertInstanceOf(LazyObjectStorage::class, $postsProxy, 'Assert that $postsProxy is an instance of LazyObjectStorage');

        /** @var Post[] $posts */
        $posts = $postsProxy->toArray();

        self::assertInstanceOf(Post::class, $posts[0], 'Assert that $posts[0] is an instance of Post');
        self::assertInstanceOf(Post::class, $posts[1], 'Assert that $posts[1] is an instance of Post');

        self::assertSame('Post1', $posts[0]->getTitle());
        self::assertSame('Post2', $posts[1]->getTitle());
    }
}
