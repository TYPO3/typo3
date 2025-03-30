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
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Enum\Salutation;
use TYPO3Tests\BlogExample\Domain\Repository\AdministratorRepository;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\DateExampleRepository;
use TYPO3Tests\BlogExample\Domain\Repository\DateTimeImmutableExampleRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PersonRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class QueryParserTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/QueryParserTestImport.csv');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    #[Test]
    public function queryWithMultipleRelationsToIdenticalTablesReturnsExpectedResultForOrQuery(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('blog', 3),
                $query->logicalOr(
                    $query->equals('tags.name', 'Tag12'),
                    $query->equals('author.tags.name', 'TagForAuthor1')
                )
            )
        );
        self::assertCount(3, $query->execute()->toArray());
    }

    /**
     * Test Relation::HAS_AND_BELONGS_TO_MANY
     */
    #[Test]
    public function queryWithRelationHasAndBelongsToManyReturnsExpectedResult(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching(
            $query->equals('tags.name', 'Tag12')
        );
        self::assertCount(2, $query->execute()->toArray());
    }

    /**
     * Test Relation::HAS_MANY
     */
    #[Test]
    public function queryWithRelationHasManyWithoutParentKeyFieldNameReturnsExpectedResult(): void
    {
        $query = $this->get(AdministratorRepository::class)->createQuery();
        $query->matching(
            $query->equals('usergroup.title', 'Group A')
        );
        self::assertCount(2, $query->execute()->toArray());
    }

    /**
     * Test Relation::HAS_ONE, ColumnMap::Relation::HAS_AND_BELONGS_TO_MANY
     */
    #[Test]
    public function queryWithRelationHasOneAndHasAndBelongsToManyWithoutParentKeyFieldNameReturnsExpectedResult(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching(
            $query->equals('author.firstname', 'Author')
        );
        // there are 16 post in total, 2 without author, 1 hidden, 1 deleted => 12 posts
        self::assertCount(12, $query->execute()->toArray());
    }

    #[Test]
    public function orReturnsExpectedResult(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching(
            $query->logicalOr(
                $query->equals('tags.name', 'Tag12'),
                $query->equals('tags.name', 'Tag11')
            )
        );
        self::assertCount(2, $query->execute()->toArray());
    }

    #[Test]
    public function queryWithMultipleRelationsToIdenticalTablesReturnsExpectedResultForAndQuery(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('blog', 3),
                $query->equals('tags.name', 'Tag12'),
                $query->equals('author.tags.name', 'TagForAuthor1')
            )
        );
        self::assertCount(1, $query->execute()->toArray());
    }

    #[Test]
    public function queryWithFindInSetReturnsExpectedResult(): void
    {
        $query = $this->get(AdministratorRepository::class)->createQuery();
        self::assertCount(2, $query->matching($query->contains('usergroup', 1))->execute());
    }

    #[Test]
    public function queryForPostWithCategoriesReturnsPostWithCategories(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $post = $query->matching($query->equals('uid', 1))->execute()->current();
        self::assertCount(3, $post->getCategories());
    }

    #[Test]
    public function queryForBlogsAndPostsWithNoPostsShowsBlogRecord(): void
    {
        $query = $this->get(BlogRepository::class)->createQuery();
        /** @var Blog $blog */
        $blog = $query->matching($query->logicalOr(
            $query->like('description', '%w/o%'),
            $query->like('posts.title', '%w/o%'),
        ))->execute()->current();
        self::assertSame(7, $blog->getUid());
    }

    #[Test]
    public function queryForPersonSalutationEnum(): void
    {
        $query = $this->get(PersonRepository::class)->createQuery();
        $person = $query->matching($query->equals('salutation', Salutation::MR))->execute()->getFirst();
        self::assertSame(4, $person->getUid());
    }

    public static function queryForDateTimeDataProvider(): array
    {
        return [
            'datetime int' => [
                'property' => 'datetimeInt',
                'repository' => DateExampleRepository::class,
                'objectType' => \DateTime::class,
            ],
            'datetime native' => [
                'property' => 'datetimeDatetime',
                'repository' => DateExampleRepository::class,
                'objectType' => \DateTime::class,
            ],
            'datetime text' => [
                'property' => 'datetimeText',
                'repository' => DateExampleRepository::class,
                'objectType' => \DateTime::class,
            ],
            'datetime immutable int' => [
                'property' => 'datetimeImmutableInt',
                'repository' => DateTimeImmutableExampleRepository::class,
                'objectType' => \DateTimeImmutable::class,
            ],
            'datetime immutable native' => [
                'property' => 'datetimeImmutableDatetime',
                'repository' => DateTimeImmutableExampleRepository::class,
                'objectType' => \DateTimeImmutable::class,
            ],
            'datetime immutable text' => [
                'property' => 'datetimeImmutableText',
                'repository' => DateTimeImmutableExampleRepository::class,
                'objectType' => \DateTimeImmutable::class,
            ],
        ];
    }

    #[DataProvider('queryForDateTimeDataProvider')]
    #[Test]
    public function queryForDateTime(string $property, string $repository, string $objectType): void
    {
        $query = $this->get($repository)->createQuery();

        $dateExample = $query->matching(
            $query->equals($property, new $objectType('2025-01-20T14:18:56'))
        )->execute()->getFirst();
        self::assertNotNull($dateExample);
        self::assertSame(1, $dateExample->getUid());

        $dateExample = $query->matching(
            $query->greaterThan($property, new $objectType('2025-01-20T14:18:55'))
        )->execute()->getFirst();
        self::assertNotNull($dateExample);
        self::assertSame(1, $dateExample->getUid());

        $dateExample = $query->matching(
            $query->greaterThanOrEqual($property, new $objectType('2025-01-20T14:18:56'))
        )->execute()->getFirst();
        self::assertNotNull($dateExample);
        self::assertSame(1, $dateExample->getUid());

        $dateExample = $query->matching(
            $query->lessThan($property, new $objectType('2025-01-20T14:18:57'))
        )->execute()->getFirst();
        self::assertNotNull($dateExample);
        self::assertSame(1, $dateExample->getUid());

        $dateExample = $query->matching(
            $query->lessThanOrEqual($property, new $objectType('2025-01-20T14:18:56'))
        )->execute()->getFirst();
        self::assertNotNull($dateExample);
        self::assertSame(1, $dateExample->getUid());

        $dateExampleQueryInvalid = $query->matching(
            $query->equals($property, new $objectType('2025-01-20T14:18:55'))
        )->execute()->getFirst();
        self::assertNull($dateExampleQueryInvalid);

        $dateExampleQueryInvalid = $query->matching(
            $query->greaterThan($property, new $objectType('2025-01-20T14:18:56'))
        )->execute()->getFirst();
        self::assertNull($dateExampleQueryInvalid);

        $dateExampleQueryInvalid = $query->matching(
            $query->lessThan($property, new $objectType('2025-01-20T14:18:56'))
        )->execute()->getFirst();
        self::assertNull($dateExampleQueryInvalid);
    }
}
