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

class QueryParserTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{

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
     * @var \ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository
     */
    protected $blogRepository;

    /**
     * Sets up this test suite.
     */
    protected function setUp()
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

        $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->blogRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository::class);
    }

    /**
     * @test
     */
    public function queryWithMultipleRelationsToIdenticalTablesReturnsExpectedResultForOrQuery()
    {
        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Repository\\PostRepository');
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
        $this->assertEquals(3, count($result));
    }

    /**
     * Test ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY
     *
     * @test
     */
    public function queryWithRelationHasAndBelongsToManyReturnsExpectedResult()
    {
        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Repository\\PostRepository');
        $query = $postRepository->createQuery();
        $query->matching(
            $query->equals('tags.name', 'Tag12')
        );
        $result = $query->execute()->toArray();
        $this->assertEquals(2, count($result));
    }

    /**
     * Test ColumnMap::RELATION_HAS_MANY
     *
     * @test
     */
    public function queryWithRelationHasManyWithoutParentKeyFieldNameReturnsExpectedResult()
    {
        /** @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository $frontendUserRepository */
        $frontendUserRepository = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserRepository');
        $query = $frontendUserRepository->createQuery();

        $result = $query->matching(
            $query->equals('usergroup.title', 'Group A')
        )->execute();
        $this->assertSame(2, count($result));
    }

    /**
     * Test ColumnMap::RELATION_HAS_ONE, ColumnMap::ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY
     *
     * @test
     */
    public function queryWithRelationHasOneAndHasAndBelongsToManyWithoutParentKeyFieldNameReturnsExpectedResult()
    {
        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Repository\\PostRepository');
        $query = $postRepository->createQuery();
        $query->matching(
            $query->equals('author.firstname', 'Author')
        );
        $result = $query->execute()->toArray();
        $this->assertEquals(2, count($result));
    }

    /**
     * @test
     */
    public function orReturnsExpectedResult()
    {
        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Repository\\PostRepository');
        $query = $postRepository->createQuery();
        $query->matching(
            $query->logicalOr(
                $query->equals('tags.name', 'Tag12'),
                $query->equals('tags.name', 'Tag11')
            )
        );
        $result = $query->execute()->toArray();
        $this->assertEquals(2, count($result));
    }

    /**
     * @test
     */
    public function queryWithMultipleRelationsToIdenticalTablesReturnsExpectedResultForAndQuery()
    {
        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Repository\\PostRepository');
        $query = $postRepository->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('blog', 3),
                $query->equals('tags.name', 'Tag12'),
                $query->equals('author.tags.name', 'TagForAuthor1')
            )
        );
        $result = $query->execute()->toArray();
        $this->assertEquals(1, count($result));
    }

    /**
     * @test
     */
    public function queryWithFindInSetReturnsExpectedResult()
    {
        /** @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository $frontendUserRepository */
        $frontendUserRepository = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserRepository');
        $query = $frontendUserRepository->createQuery();

        $result = $query->matching(
                $query->contains('usergroup', 1)
        )->execute();
        $this->assertSame(2, count($result));
    }

    /**
     * @test
     */
    public function queryForPostWithCategoriesReturnsPostWithCategories()
    {
        $postRepository = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Repository\\PostRepository');
        $query = $postRepository->createQuery();
        $post = $query->matching($query->equals('uid', 1))->execute()->current();
        $this->assertSame(3, count($post->getCategories()));
    }
}
