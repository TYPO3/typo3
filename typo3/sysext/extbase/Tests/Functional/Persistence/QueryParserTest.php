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

use ExtbaseTeam\BlogExample\Domain\Repository\AdministratorRepository;
use ExtbaseTeam\BlogExample\Domain\Repository\PostRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class QueryParserTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/categories.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/persons.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-tag-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/category-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/fe_users.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/fe_groups.xml');
    }

    /**
     * @test
     */
    public function queryWithMultipleRelationsToIdenticalTablesReturnsExpectedResultForOrQuery(): void
    {
        $postRepository = $this->getContainer()->get(PostRepository::class);
        $query = $postRepository->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('blog', 3),
                $query->logicalOr(
                    $query->equals('tags.name', 'Tag12'),
                    $query->equals('author.tags.name', 'TagForAuthor1')
                )
            )
        );

        $result = $query->execute()->toArray();
        self::assertCount(3, $result);
    }

    /**
     * Test ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY
     *
     * @test
     */
    public function queryWithRelationHasAndBelongsToManyReturnsExpectedResult(): void
    {
        $postRepository = $this->getContainer()->get(PostRepository::class);
        $query = $postRepository->createQuery();
        $query->matching(
            $query->equals('tags.name', 'Tag12')
        );
        $result = $query->execute()->toArray();
        self::assertCount(2, $result);
    }

    /**
     * Test ColumnMap::RELATION_HAS_MANY
     *
     * @test
     * @group not-mssql
     */
    public function queryWithRelationHasManyWithoutParentKeyFieldNameReturnsExpectedResult(): void
    {
        $administratorRepository = $this->getContainer()->get(AdministratorRepository::class);
        $query = $administratorRepository->createQuery();
        $query->matching(
            $query->equals('usergroup.title', 'Group A')
        );

        $result = $query->execute()->toArray();
        self::assertCount(2, $result);
    }

    /**
     * Test ColumnMap::RELATION_HAS_ONE, ColumnMap::ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY
     *
     * @test
     */
    public function queryWithRelationHasOneAndHasAndBelongsToManyWithoutParentKeyFieldNameReturnsExpectedResult(): void
    {
        $postRepository = $this->getContainer()->get(PostRepository::class);
        $query = $postRepository->createQuery();
        $query->matching(
            $query->equals('author.firstname', 'Author')
        );
        $result = $query->execute()->toArray();
        // there are 16 post in total, 2 without author, 1 hidden, 1 deleted => 12 posts
        self::assertCount(12, $result);
    }

    /**
     * @test
     */
    public function orReturnsExpectedResult(): void
    {
        $postRepository = $this->getContainer()->get(PostRepository::class);
        $query = $postRepository->createQuery();
        $query->matching(
            $query->logicalOr(
                $query->equals('tags.name', 'Tag12'),
                $query->equals('tags.name', 'Tag11')
            )
        );
        $result = $query->execute()->toArray();
        self::assertCount(2, $result);
    }

    /**
     * @test
     */
    public function queryWithMultipleRelationsToIdenticalTablesReturnsExpectedResultForAndQuery(): void
    {
        $postRepository = $this->getContainer()->get(PostRepository::class);
        $query = $postRepository->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('blog', 3),
                $query->equals('tags.name', 'Tag12'),
                $query->equals('author.tags.name', 'TagForAuthor1')
            )
        );
        $result = $query->execute()->toArray();
        self::assertCount(1, $result);
    }

    /**
     * @test
     */
    public function queryWithFindInSetReturnsExpectedResult(): void
    {
        $administratorRepository = $this->getContainer()->get(AdministratorRepository::class);
        $query = $administratorRepository->createQuery();

        $result = $query->matching($query->contains('usergroup', 1))
            ->execute();
        self::assertCount(2, $result);
    }

    /**
     * @test
     */
    public function queryForPostWithCategoriesReturnsPostWithCategories(): void
    {
        $postRepository = $this->getContainer()->get(PostRepository::class);
        $query = $postRepository->createQuery();
        $post = $query->matching($query->equals('uid', 1))->execute()->current();
        self::assertCount(3, $post->getCategories());
    }
}
