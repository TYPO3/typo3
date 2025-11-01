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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class RepositoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    #[Test]
    public function constructSetsObjectTypeFromClassName(): void
    {
        $subject = $this->get(PostRepository::class);
        $reflectionClass = new \ReflectionClass($subject);
        $reflectionProperty = $reflectionClass->getProperty('objectType');
        $objectType = $reflectionProperty->getValue($subject);
        self::assertSame(Post::class, $objectType);
    }

    #[Test]
    public function createQuerySetsDefaultOrderingIfDefined(): void
    {
        $subject = $this->get(PostRepository::class);
        $orderings = ['foo' => QueryInterface::ORDER_ASCENDING];
        $subject->setDefaultOrderings($orderings);
        $query = $subject->createQuery();
        self::assertSame($orderings, $query->getOrderings());
        $subject->setDefaultOrderings([]);
        $query = $subject->createQuery();
        self::assertSame([], $query->getOrderings());
    }

    #[Test]
    public function createQueryReturnsQueryWithUnmodifiedDefaultQuerySettings(): void
    {
        $mockDefaultQuerySettings = $this->createMock(QuerySettingsInterface::class);
        $subject = $this->get(PostRepository::class);
        $subject->setDefaultQuerySettings($mockDefaultQuerySettings);
        $query = $subject->createQuery();
        $instanceQuerySettings = $query->getQuerySettings();
        self::assertEquals($mockDefaultQuerySettings, $instanceQuerySettings);
        self::assertNotSame($mockDefaultQuerySettings, $instanceQuerySettings);
    }

    #[Test]
    public function addChecksObjectType(): void
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1248363335);
        $subject = $this->get(PostRepository::class);
        /** @phpstan-ignore-next-line */
        $subject->add(new \stdClass());
    }

    #[Test]
    public function removeChecksObjectType(): void
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1248363336);
        $subject = $this->get(PostRepository::class);
        /** @phpstan-ignore-next-line */
        $subject->remove(new \stdClass());
    }

    #[Test]
    public function updateChecksObjectType(): void
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1249479625);
        $subject = $this->get(PostRepository::class);
        /** @phpstan-ignore-next-line */
        $subject->update(new \stdClass());
    }

    #[Test]
    public function findAllReturnsResults(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RepositoryTestImport.csv');
        $subject = $this->get(PostRepository::class);
        $result = $subject->findAll();
        self::assertCount(14, $result);
    }

    public static function findByRespectsSingleCriteriaDataProvider(): \Generator
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

    #[DataProvider('findByRespectsSingleCriteriaDataProvider')]
    #[Test]
    public function findByRespectsSingleCriteria(array $criteria, int $expectedCount): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RepositoryTestImport.csv');
        self::assertCount($expectedCount, $this->get(PostRepository::class)->findBy($criteria));
    }

    #[Test]
    public function findByRespectsMultipleCriteria(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RepositoryTestImport.csv');
        self::assertCount(6, $this->get(PostRepository::class)->findBy(['blog' => 1, 'author' => 1]));
    }

    #[Test]
    public function findByRespectsSingleOrderBy(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RepositoryTestImport.csv');
        $posts = $this->get(PostRepository::class)->findBy(
            ['blog' => 1, 'author' => 1],
            ['title' => QueryInterface::ORDER_DESCENDING]
        )->toArray();
        $titles = array_map(
            static fn(Post $post): string => $post->getTitle(),
            $posts
        );
        self::assertSame([
            'Post9',
            'Post8',
            'Post7',
            'Post5',
            'Post4',
            'Post10',
        ], $titles);
    }

    #[Test]
    public function findByRespectsMultipleOrderBy(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RepositoryTestImport.csv');
        $posts = $this->get(PostRepository::class)->findBy(
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
            array_map(
                static fn(Post $post): array => ['blog.uid' => $post->getBlog()->getUid(), 'post.title' => $post->getTitle()],
                $posts
            )
        );
    }

    #[Test]
    public function findByRespectsLimit(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RepositoryTestImport.csv');
        $posts = $this->get(PostRepository::class)->findBy(
            ['author' => 1],
            ['uid' => QueryInterface::ORDER_DESCENDING],
            3
        )->toArray();
        $titles = array_map(
            static fn(Post $post): array => ['uid' => $post->getUid(), 'title' => $post->getTitle()],
            $posts
        );
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

    #[Test]
    public function findByThrowsExceptionIfNonStringGiven(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1741806517);
        $this->get(PostRepository::class)->findBy(
            [0 => 'author'],
        );
    }

    #[Test]
    public function findByRespectsOffset(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RepositoryTestImport.csv');
        $posts = $this->get(PostRepository::class)->findBy(
            ['author' => 1],
            ['uid' => QueryInterface::ORDER_DESCENDING],
            3,
            1
        )->toArray();
        $titles = array_map(
            static fn(Post $post): array => ['uid' => $post->getUid(), 'title' => $post->getTitle()],
            $posts
        );
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

    public static function findOneByRespectsSingleCriteriaDataProvider(): \Generator
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

    #[DataProvider('findOneByRespectsSingleCriteriaDataProvider')]
    #[Test]
    public function findOneByRespectsSingleCriteria(array $criteria, ?int $expectedUid): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RepositoryTestImport.csv');
        /** @var Post|null $post */
        $post = $this->get(PostRepository::class)->findOneBy($criteria);
        self::assertSame($expectedUid, $post?->getUid());
    }

    #[Group('not-postgres')]
    #[Test]
    public function findOneByRespectsMultipleCriteria(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RepositoryTestImport.csv');
        $post = $this->get(PostRepository::class)->findOneBy(['blog' => 1, 'author' => 1]);
        self::assertSame('Post4', $post?->getTitle());
    }

    #[Test]
    public function findOneByRespectsOrderBy(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RepositoryTestImport.csv');
        $post = $this->get(PostRepository::class)->findOneBy(
            ['blog' => 1, 'author' => 1],
            ['title' => QueryInterface::ORDER_DESCENDING]
        );
        self::assertSame('Post9', $post?->getTitle());
    }

    #[Test]
    public function countRespectsSingleCriteria(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RepositoryTestImport.csv');
        self::assertSame(
            10,
            $this->get(PostRepository::class)->count(
                ['blog' => 1],
            )
        );
    }

    #[Test]
    public function countRespectsMultipleCriteria(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RepositoryTestImport.csv');
        self::assertSame(
            1,
            $this->get(PostRepository::class)->count(
                ['blog' => 1, 'author' => 3],
            )
        );
    }
}
