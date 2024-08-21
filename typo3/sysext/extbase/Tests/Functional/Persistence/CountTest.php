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
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Repository\PersonRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class CountTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    /**
     * @var int number of all records
     */
    protected int $numberOfRecordsInFixture = 14;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CountTestImport.csv');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    #[Test]
    public function simpleCountTest(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        self::assertSame($this->numberOfRecordsInFixture, $query->count());
    }

    #[Test]
    public function offsetCountTest(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setLimit($this->numberOfRecordsInFixture + 1);
        $query->setOffset(6);
        self::assertSame($this->numberOfRecordsInFixture - 6, $query->count());
    }

    #[Test]
    public function exceedingOffsetCountTest(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setLimit($this->numberOfRecordsInFixture + 1);
        $query->setOffset($this->numberOfRecordsInFixture + 5);
        self::assertSame(0, $query->count());
    }

    #[Test]
    public function limitCountTest(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setLimit(4);
        self::assertSame(4, $query->count());
    }

    #[Test]
    public function limitAndOffsetCountTest(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query
            ->setOffset($this->numberOfRecordsInFixture - 3)
            ->setLimit(4);
        self::assertSame(3, $query->count());
    }

    #[Test]
    public function inConstraintCountTest(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching(
            $query->in('uid', [1, 2, 3])
        );
        self::assertSame(3, $query->count());
    }

    /**
     * Test if count works with subproperties in subselects.
     */
    #[Test]
    public function subpropertyJoinCountTest(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching(
            $query->equals('blog.title', 'Blog1')
        );
        self::assertSame(10, $query->count());
    }

    /**
     * Test if count works with subproperties in subselects that use the same table as the repository.
     */
    #[Test]
    public function subpropertyJoinSameTableCountTest(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching(
            $query->equals('relatedPosts.title', 'Post2')
        );
        self::assertSame(1, $query->count());
    }

    /**
     * Test if count works with subproperties in multiple left join.
     */
    #[Test]
    public function subpropertyInMultipleLeftJoinCountTest(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching(
            $query->logicalOr(
                $query->equals('tags.uid', 1),
                $query->equals('tags.uid', 2)
            )
        );
        // QueryResult is lazy, so we have to run valid method to initialize
        $result = $query->execute();
        $result->valid();
        self::assertCount(10, $result);
    }

    #[Test]
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

    #[Test]
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
