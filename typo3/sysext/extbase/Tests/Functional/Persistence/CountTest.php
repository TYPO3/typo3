<?php
namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class CountTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
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
    protected function setUp()
    {
        parent::setUp();

        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-post-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-tag-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/persons.xml');

        $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->persistentManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
        $this->postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
    }

    /**
     * @test
     */
    public function simpleCountTest()
    {
        $query = $this->postRepository->createQuery();
        $this->assertSame($this->numberOfRecordsInFixture, $query->count());
    }

    /**
     * @test
     */
    public function offsetCountTest()
    {
        $query = $this->postRepository->createQuery();

        $query->setLimit($this->numberOfRecordsInFixture+1);
        $query->setOffset(6);

        $this->assertSame(($this->numberOfRecordsInFixture - 6), $query->count());
    }

    /**
     * @test
     */
    public function exceedingOffsetCountTest()
    {
        $query = $this->postRepository->createQuery();

        $query->setLimit($this->numberOfRecordsInFixture+1);
        $query->setOffset(($this->numberOfRecordsInFixture + 5));

        $this->assertSame(0, $query->count());
    }

    /**
     * @test
     */
    public function limitCountTest()
    {
        $query = $this->postRepository->createQuery();

        $query->setLimit(4);

        $this->assertSame(4, $query->count());
    }

    /**
     * @test
     */
    public function limitAndOffsetCountTest()
    {
        $query = $this->postRepository->createQuery();

        $query
            ->setOffset(($this->numberOfRecordsInFixture - 3))
            ->setLimit(4);

        $this->assertSame(3, $query->count());
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

        $this->assertSame(3, $query->count());
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

        $this->assertSame(10, $query->count());
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

        $this->assertSame(1, $query->count());
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

        $this->assertSame(10, $query->count());
    }

    /**
     * @test
     */
    public function queryWithAndConditionsToTheSameTableReturnExpectedCount()
    {
        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PersonRepository $personRepository */
        $personRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PersonRepository::class);
        $query = $personRepository->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('tags.name', 'TagForAuthor1'),
                $query->equals('tagsSpecial.name', 'SpecialTagForAuthor1')
            )
        );
        $this->assertSame(1, $query->count());
    }

    /**
     * @test
     */
    public function queryWithOrConditionsToTheSameTableReturnExpectedCount()
    {
        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PersonRepository $personRepository */
        $personRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PersonRepository::class);
        $query = $personRepository->createQuery();
        $query->matching(
            $query->logicalOr(
                $query->equals('tags.name', 'TagForAuthor1'),
                $query->equals('tagsSpecial.name', 'SpecialTagForAuthor1')
            )
        );
        $this->assertSame(3, $query->count());
    }
}
