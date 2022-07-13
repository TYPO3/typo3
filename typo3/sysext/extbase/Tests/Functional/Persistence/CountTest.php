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

use ExtbaseTeam\BlogExample\Domain\Repository\PersonRepository;
use ExtbaseTeam\BlogExample\Domain\Repository\PostRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CountTest extends FunctionalTestCase
{
    /**
     * @var int number of all records
     */
    protected $numberOfRecordsInFixture = 14;

    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var PostRepository
     */
    protected $postRepository;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/blogs.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/posts.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/post-post-mm.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/tags.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/tags-mm.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/post-tag-mm.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/persons.csv');

        $this->postRepository = $this->get(PostRepository::class);
    }

    /**
     * @test
     */
    public function simpleCountTest(): void
    {
        $query = $this->postRepository->createQuery();
        self::assertSame($this->numberOfRecordsInFixture, $query->count());
    }

    /**
     * @test
     */
    public function offsetCountTest(): void
    {
        $query = $this->postRepository->createQuery();

        $query->setLimit($this->numberOfRecordsInFixture+1);
        $query->setOffset(6);

        self::assertSame($this->numberOfRecordsInFixture - 6, $query->count());
    }

    /**
     * @test
     */
    public function exceedingOffsetCountTest(): void
    {
        $query = $this->postRepository->createQuery();

        $query->setLimit($this->numberOfRecordsInFixture+1);
        $query->setOffset($this->numberOfRecordsInFixture + 5);

        self::assertSame(0, $query->count());
    }

    /**
     * @test
     */
    public function limitCountTest(): void
    {
        $query = $this->postRepository->createQuery();

        $query->setLimit(4);

        self::assertSame(4, $query->count());
    }

    /**
     * @test
     */
    public function limitAndOffsetCountTest(): void
    {
        $query = $this->postRepository->createQuery();

        $query
            ->setOffset($this->numberOfRecordsInFixture - 3)
            ->setLimit(4);

        self::assertSame(3, $query->count());
    }

    /**
     * @test
     */
    public function inConstraintCountTest(): void
    {
        $query = $this->postRepository->createQuery();

        $query->matching(
            $query->in('uid', [1, 2, 3])
        );

        self::assertSame(3, $query->count());
    }

    /**
     * Test if count works with subproperties in subselects.
     *
     * @test
     */
    public function subpropertyJoinCountTest(): void
    {
        $query = $this->postRepository->createQuery();

        $query->matching(
            $query->equals('blog.title', 'Blog1')
        );

        self::assertSame(10, $query->count());
    }

    /**
     * Test if count works with subproperties in subselects that use the same table as the repository.
     *
     * @test
     */
    public function subpropertyJoinSameTableCountTest(): void
    {
        $query = $this->postRepository->createQuery();

        $query->matching(
            $query->equals('relatedPosts.title', 'Post2')
        );

        self::assertSame(1, $query->count());
    }

    /**
     * Test if count works with subproperties in multiple left join.
     *
     * @test
     */
    public function subpropertyInMultipleLeftJoinCountTest(): void
    {
        $query = $this->postRepository->createQuery();

        $query->matching(
            $query->logicalOr(
                $query->equals('tags.uid', 1),
                $query->equals('tags.uid', 2)
            )
        );

        // QueryResult is lazy, so we have to run valid method to initialize
        $result = $query->execute();
        $result->valid();

        self::assertSame(10, $result->count());
    }

    /**
     * @test
     */
    public function queryWithAndConditionsToTheSameTableReturnExpectedCount(): void
    {
        $personRepository = $this->get(PersonRepository::class);
        $query = $personRepository->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('tags.name', 'TagForAuthor1'),
                $query->equals('tagsSpecial.name', 'SpecialTagForAuthor1')
            )
        );
        self::assertSame(1, $query->count());
    }

    /**
     * @test
     */
    public function queryWithOrConditionsToTheSameTableReturnExpectedCount(): void
    {
        $personRepository = $this->get(PersonRepository::class);
        $query = $personRepository->createQuery();
        $query->matching(
            $query->logicalOr(
                $query->equals('tags.name', 'TagForAuthor1'),
                $query->equals('tagsSpecial.name', 'SpecialTagForAuthor1')
            )
        );
        self::assertSame(4, $query->count());
    }
}
