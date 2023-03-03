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

use ExtbaseTeam\BlogExample\Domain\Model\Post;
use ExtbaseTeam\BlogExample\Domain\Repository\PostRepository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RepositoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected PostRepository $postRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/blogs.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/posts.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/tags.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/post-tag-mm.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/persons.csv');

        $this->postRepository = $this->get(PostRepository::class);
    }

    public function findByRespectsSingleCriteriaDataProvider(): \Generator
    {
        yield 'findBy(["blog" => 1]) => 10' => [
            ['blog' => 1],
            10,
        ];

        yield 'findBy(["blog" => 1]) => 1' => [
            ['blog' => 2],
            1,
        ];

        yield 'findBy(["blog" => 1]) => 3' => [
            ['blog' => 3],
            3,
        ];
    }

    /**
     * @test
     * @dataProvider findByRespectsSingleCriteriaDataProvider
     */
    public function findByRespectsSingleCriteria(array $criteria, int $expectedCount): void
    {
        self::assertCount($expectedCount, $this->postRepository->findBy($criteria));
    }

    /**
     * @test
     */
    public function findByRespectsMultipleCriteria(): void
    {
        self::assertCount(6, $this->postRepository->findBy(['blog' => 1, 'author' => 1]));
    }

    /**
     * @test
     */
    public function findByRespectsSingleOrderBy(): void
    {
        $posts = $this->postRepository->findBy(
            ['blog' => 1, 'author' => 1],
            ['title' => QueryInterface::ORDER_DESCENDING]
        )->toArray();

        $titles = array_map(fn (Post $post) => $post->getTitle(), $posts);

        self::assertSame([
            'Post9',
            'Post8',
            'Post7',
            'Post5',
            'Post4',
            'Post10',
        ], $titles);
    }

    /**
     * @test
     */
    public function findByRespectsMultipleOrderBy(): void
    {
        $posts = $this->postRepository->findBy(
            [],
            ['blog.uid' => QueryInterface::ORDER_ASCENDING, 'title' => QueryInterface::ORDER_DESCENDING]
        )->toArray();

        self::assertSame(
            [
                [
                    'blog.uid' => 1,
                    'post.title' => 'Post9',
                ],
                [
                    'blog.uid' => 1,
                    'post.title' => 'Post8',
                ],
                [
                    'blog.uid' => 1,
                    'post.title' => 'Post7',
                ],
                [
                    'blog.uid' => 1,
                    'post.title' => 'Post6',
                ],
                [
                    'blog.uid' => 1,
                    'post.title' => 'Post5',
                ],
                [
                    'blog.uid' => 1,
                    'post.title' => 'Post4',
                ],
                [
                    'blog.uid' => 1,
                    'post.title' => 'Post3',
                ],
                [
                    'blog.uid' => 1,
                    'post.title' => 'Post2',
                ],
                [
                    'blog.uid' => 1,
                    'post.title' => 'Post10',
                ],
                [
                    'blog.uid' => 1,
                    'post.title' => 'Post1',
                ],
                [
                    'blog.uid' => 2,
                    'post.title' => 'post1',
                ],
                [
                    'blog.uid' => 3,
                    'post.title' => 'post with tagged author',
                ],
                [
                    'blog.uid' => 3,
                    'post.title' => 'post with tag and tagged author',
                ],
                [
                    'blog.uid' => 3,
                    'post.title' => 'post with tag',
                ],
            ],
            array_map(fn (Post $post) => ['blog.uid' => $post->getBlog()->getUid(), 'post.title' => $post->getTitle()], $posts)
        );
    }

    /**
     * @test
     */
    public function findByRespectsLimit(): void
    {
        $posts = $this->postRepository->findBy(
            ['author' => 1],
            ['uid' => QueryInterface::ORDER_DESCENDING],
            3
        )->toArray();

        $titles = array_map(fn (Post $post) => ['uid' => $post->getUid(), 'title' => $post->getTitle()], $posts);

        self::assertSame([
            [
                'uid' => 14,
                'title' => 'post with tag and tagged author',
            ],
            [
                'uid' => 13,
                'title' => 'post with tagged author',
            ],
            [
                'uid' => 10,
                'title' => 'Post10',
            ],
        ], $titles);
    }

    /**
     * @test
     */
    public function findByRespectsOffset(): void
    {
        $posts = $this->postRepository->findBy(
            ['author' => 1],
            ['uid' => QueryInterface::ORDER_DESCENDING],
            3,
            1
        )->toArray();

        $titles = array_map(fn (Post $post) => ['uid' => $post->getUid(), 'title' => $post->getTitle()], $posts);

        self::assertSame([
            [
                'uid' => 13,
                'title' => 'post with tagged author',
            ],
            [
                'uid' => 10,
                'title' => 'Post10',
            ],
            [
                'uid' => 9,
                'title' => 'Post9',
            ],
        ], $titles);
    }

    public function findOneByRespectsSingleCriteriaDataProvider(): \Generator
    {
        yield 'findOneBy(["blog" => 1]) => "Post4"' => [
            ['uid' => 1],
            1,
        ];

        yield 'findOneBy(["blog" => 100]) => null' => [
            ['uid' => 100],
            null,
        ];
    }

    /**
     * @test
     * @dataProvider findOneByRespectsSingleCriteriaDataProvider
     */
    public function findOneByRespectsSingleCriteria(array $criteria, int|null $expectedUid): void
    {
        /** @var Post|null $post */
        $post = $this->postRepository->findOneBy($criteria);

        self::assertSame($expectedUid, $post?->getUid());
    }

    /**
     * @test
     * @group not-postgres
     */
    public function findOneByRespectsMultipleCriteria(): void
    {
        $post = $this->postRepository->findOneBy(['blog' => 1, 'author' => 1]);

        self::assertSame('Post4', $post?->getTitle());
    }

    /**
     * @test
     */
    public function findOneByRespectsOrderBy(): void
    {
        $post = $this->postRepository->findOneBy(
            ['blog' => 1, 'author' => 1],
            ['title' => QueryInterface::ORDER_DESCENDING]
        );

        self::assertSame('Post9', $post?->getTitle());
    }

    /**
     * @test
     */
    public function countRespectsSingleCriteria(): void
    {
        self::assertSame(
            10,
            $this->postRepository->count(
                ['blog' => 1],
            )
        );
    }

    /**
     * @test
     */
    public function countRespectsMultipleCriteria(): void
    {
        self::assertSame(
            1,
            $this->postRepository->count(
                ['blog' => 1, 'author' => 3],
            )
        );
    }
}
