<?php

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CountTest extends FunctionalTestCase
{
    /**
     * @var int number of all records
     */
    protected $numberOfRecordsInFixture = 14;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistentManager;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Repository
     */
    protected $blogRepository;

    /**
     * @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository
     */
    protected $postRepository;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-post-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-tag-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/persons.xml');

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->persistentManager = $this->objectManager->get(PersistenceManager::class);
        $this->postRepository = $this->objectManager->get(PostRepository::class);
    }

    /**
     * @test
     */
    public function simpleCountTest()
    {
        $query = $this->postRepository->createQuery();
        self::assertSame($this->numberOfRecordsInFixture, $query->count());
    }

    /**
     * @test
     */
    public function offsetCountTest()
    {
        $query = $this->postRepository->createQuery();

        $query->setLimit($this->numberOfRecordsInFixture+1);
        $query->setOffset(6);

        self::assertSame($this->numberOfRecordsInFixture - 6, $query->count());
    }

    /**
     * @test
     */
    public function exceedingOffsetCountTest()
    {
        $query = $this->postRepository->createQuery();

        $query->setLimit($this->numberOfRecordsInFixture+1);
        $query->setOffset($this->numberOfRecordsInFixture + 5);

        self::assertSame(0, $query->count());
    }

    /**
     * @test
     */
    public function limitCountTest()
    {
        $query = $this->postRepository->createQuery();

        $query->setLimit(4);

        self::assertSame(4, $query->count());
    }

    /**
     * @test
     */
    public function limitAndOffsetCountTest()
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
    public function inConstraintCountTest()
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
    public function subpropertyJoinCountTest()
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
    public function subpropertyJoinSameTableCountTest()
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
    public function subpropertyInMultipleLeftJoinCountTest()
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
    public function queryWithAndConditionsToTheSameTableReturnExpectedCount()
    {
        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PersonRepository $personRepository */
        $personRepository = $this->objectManager->get(PersonRepository::class);
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
    public function queryWithOrConditionsToTheSameTableReturnExpectedCount()
    {
        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PersonRepository $personRepository */
        $personRepository = $this->objectManager->get(PersonRepository::class);
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
