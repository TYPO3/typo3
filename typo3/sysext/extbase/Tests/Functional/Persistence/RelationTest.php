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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Category;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PersonRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class RelationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    private Blog $blog;
    private PersistenceManager $persistenceManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationTestImport.csv');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $this->persistenceManager = $this->get(PersistenceManager::class);
        $this->blog = $this->get(BlogRepository::class)->findByUid(1);
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->workspace = 0;
    }

    /**
     * Tests adding object at the end of sorted 1:M relation (Blog:Posts)
     */
    #[Test]
    public function attachPostToBlogAtTheEnd(): void
    {
        $newPostTitle = 'sdufhisdhuf';
        $newPost = new Post();
        $newPost->setBlog($this->blog);
        $newPost->setTitle($newPostTitle);
        $newPost->setContent('Bla Bla Bla');
        $this->blog->addPost($newPost);
        $this->updateAndPersistBlog();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTestResultAttachPostToBlogAtTheEnd.csv');
    }

    /**
     * Tests removing object from the end of sorted 1:M relation (Blog:Posts)
     */
    #[Test]
    public function removeLastPostFromBlog(): void
    {
        $posts = $this->blog->getPosts();
        $postsArray = $posts->toArray();
        $latestPost = array_pop($postsArray);
        self::assertEquals(10, $latestPost->getUid());
        $this->blog->removePost($latestPost);
        $this->updateAndPersistBlog();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTestResultRemoveLastPostFromBlog.csv');
    }

    /**
     * Tests adding object in the middle of the sorted 1:M relation (Blog:Posts)
     */
    #[Test]
    public function addPostToBlogInTheMiddle(): void
    {
        $newPost = new Post();
        $posts = clone $this->blog->getPosts();
        $this->blog->getPosts()->removeAll($posts);
        $counter = 1;
        $newPostTitle = 'INSERTED POST at position 6';
        foreach ($posts as $post) {
            $this->blog->addPost($post);
            if ($counter === 5) {
                $newPost->setBlog($this->blog);
                $newPost->setTitle($newPostTitle);
                $newPost->setContent('Bla Bla Bla');
                $this->blog->addPost($newPost);
            }
            $counter++;
        }
        $this->updateAndPersistBlog();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTestResultAddPostToBlogInTheMiddle.csv');
    }

    /**
     * Tests removing object from the middle of sorted 1:M relation (Blog:Posts)
     */
    #[Test]
    public function removeMiddlePostFromBlog(): void
    {
        $posts = clone $this->blog->getPosts();
        $counter = 1;
        foreach ($posts as $post) {
            if ($counter === 5) {
                $this->blog->removePost($post);
            }
            $counter++;
        }
        $this->updateAndPersistBlog();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTestResultRemoveMiddlePostFromBlog.csv');
    }

    /**
     * Tests moving object from the end to the middle of the sorted 1:M relation (Blog:Posts)
     */
    #[Test]
    public function movePostFromEndToTheMiddle(): void
    {
        $posts = clone $this->blog->getPosts();
        $postsArray = $posts->toArray();
        $latestPost = array_pop($postsArray);

        $this->blog->getPosts()->removeAll($posts);
        $counter = 0;
        $postCount = $posts->count();
        foreach ($posts as $post) {
            if ($counter !== ($postCount - 1)) {
                $this->blog->addPost($post);
            }
            if ($counter === 4) {
                $latestPost->setTitle('MOVED POST ' . $latestPost->getTitle());
                $this->blog->addPost($latestPost);
            }
            $counter++;
        }
        $this->updateAndPersistBlog();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTestResultMovePostFromEndToTheMiddle.csv');
    }

    /**
     * Tests adding object at the end of sorted M:M relation (Post:Tag)
     */
    #[Test]
    public function attachTagToPostAtTheEnd(): void
    {
        $newTagTitle = 'sdufhisdhuf';
        $newTag = new Tag($newTagTitle);
        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $post->addTag($newTag);
        $postRepository->update($post);
        $this->persistenceManager->persistAll();
        self::assertCSVDataSet(__DIR__ . '/Fixtures/RelationTestResultAttachTagToPostAtTheEnd.csv');
    }

    /**
     * Tests removing object from the end of sorted M:M relation (Post:Tag)
     */
    #[Test]
    public function removeLastTagFromPost(): void
    {
        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = $post->getTags();
        $tagsArray = $tags->toArray();
        $latestTag = array_pop($tagsArray);

        self::assertEquals(10, $latestTag->getUid());

        $post->removeTag($latestTag);

        $postRepository->update($post);
        $this->persistenceManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTestResultRemoveLastTagFromPost.csv');
    }

    /**
     * Tests adding object in the middle of sorted M:M relation (Post:Tag)
     */
    #[Test]
    public function addTagToPostInTheMiddle(): void
    {
        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = clone $post->getTags();
        /** @var ObjectStorage<Tag> $emptyTagStorage */
        $emptyTagStorage = new ObjectStorage();
        $post->setTags($emptyTagStorage);
        $newTag = new Tag('INSERTED TAG at position 6 : ');

        $counter = 1;
        foreach ($tags as $tag) {
            $post->addTag($tag);
            if ($counter === 5) {
                $post->addTag($newTag);
            }
            $counter++;
        }

        $postRepository->update($post);
        $this->persistenceManager->persistAll();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTestResultAddTagToPostInTheMiddle.csv');
    }

    /**
     * Tests removing object from the middle of the sorted M:M relation (Post:Tag)
     */
    #[Test]
    public function removeMiddleTagFromPost(): void
    {
        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = clone $post->getTags();
        $counter = 1;
        foreach ($tags as $tag) {
            if ($counter === 5) {
                $post->removeTag($tag);
            }
            $counter++;
        }

        $postRepository->update($post);
        $this->persistenceManager->persistAll();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTestResultRemoveMiddleTagFromPost.csv');
    }

    /**
     * Tests moving object from the end to the middle of sorted M:M relation (Post:Tag)
     */
    #[Test]
    public function moveTagFromEndToTheMiddle(): void
    {
        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = clone $post->getTags();
        $tagsArray = $tags->toArray();
        $latestTag = array_pop($tagsArray);
        $post->removeTag($latestTag);
        /** @var ObjectStorage<Tag> $emptyTagStorage */
        $emptyTagStorage = new ObjectStorage();
        $post->setTags($emptyTagStorage);

        $counter = 1;
        $tagCount = $tags->count();
        foreach ($tags as $tag) {
            if ($counter !== $tagCount) {
                $post->addTag($tag);
            }
            if ($counter === 5) {
                $post->addTag($latestTag);
            }
            $counter++;
        }
        $post->addTag($latestTag);

        $postRepository->update($post);
        $this->persistenceManager->persistAll();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RelationTestResultMoveTagFromEndToTheMiddle.csv');
    }

    #[Test]
    public function mmRelationWithMatchFieldIsResolvedFromLocalSide(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('sys_category_record_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $countCategories = $queryBuilder
            ->count('*')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter('tx_blogexample_domain_model_post')
                    ),
                    $queryBuilder->expr()->eq(
                        'fieldname',
                        $queryBuilder->createNamedParameter('categories')
                    )
                )
            )
            ->executeQuery()
            ->fetchOne();
        self::assertEquals(4, $countCategories);

        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        self::assertCount(3, $post->getCategories());
    }

    /**
     * Test query matching respects MM_match_fields
     */
    #[Test]
    public function mmRelationWithMatchFieldIsResolvedFromForeignSide(): void
    {
        $postRepository = $this->get(PostRepository::class);
        $posts = $postRepository->findByCategory(1);
        self::assertCount(2, $posts);

        $posts = $postRepository->findByCategory(4);
        self::assertCount(0, $posts);
    }

    #[Test]
    public function mmRelationWithMatchFieldIsCreatedFromLocalSide(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('sys_category_record_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $countCategories = $queryBuilder
            ->count('*')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter('tx_blogexample_domain_model_post')
                    ),
                    $queryBuilder->expr()->eq(
                        'fieldname',
                        $queryBuilder->createNamedParameter('categories')
                    )
                )
            )
            ->executeQuery()
            ->fetchOne();
        self::assertEquals(4, $countCategories);

        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);

        $newCategory = new Category();
        $newCategory->setTitle('New Category');

        $post->addCategory($newCategory);

        $postRepository->update($post);
        $this->persistenceManager->persistAll();

        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('sys_category_record_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $countCategories = $queryBuilder
            ->count('*')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter('tx_blogexample_domain_model_post')
                    ),
                    $queryBuilder->expr()->eq(
                        'fieldname',
                        $queryBuilder->createNamedParameter('categories')
                    )
                )
            )
            ->executeQuery()
            ->fetchOne();
        self::assertEquals(5, $countCategories);
    }

    /**
     * Test if adjusting existing mm relations do not relations with other objects
     */
    #[Test]
    public function adjustingMmRelationWithTablesnameAndFieldnameFieldDoNotTouchOtherRelations(): void
    {
        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        // Move category down
        foreach ($post->getCategories() as $category) {
            $post->removeCategory($category);
            $post->addCategory($category);
            break;
        }
        $postRepository->update($post);
        $this->persistenceManager->persistAll();

        // re-fetch Post and Blog
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('sys_category_record_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $newBlogCategoryCount = $queryBuilder
            ->count('*')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'uid_foreign',
                        $queryBuilder->createNamedParameter($this->blog->getUid(), Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter('tx_blogexample_domain_model_post')
                    ),
                    $queryBuilder->expr()->eq(
                        'fieldname',
                        $queryBuilder->createNamedParameter('categories')
                    )
                )
            )
            ->executeQuery()
            ->fetchOne();

        // one category is hidden, so the expected count has to be one less
        $newBlogCategoryCount--;

        self::assertEquals($this->blog->getCategories()->count(), $newBlogCategoryCount);
    }

    public static function distinctDataProvider(): array
    {
        return [
            'order default' => [
                [],
            ],
            'order default, offset 0' => [
                [
                    'offset' => 0,
                ],
            ],
            'order default, limit 100' => [
                [
                    'limit' => 100,
                ],
            ],
            'order default, offset 0, limit 100' => [
                [
                    'offset' => 0,
                    'limit' => 100,
                ],
            ],
            'order false' => [
                [
                    'order' => false,
                ],
            ],
            'order false, offset 0' => [
                [
                    'order' => false,
                    'offset' => 0,
                ],
            ],
            'order false, limit 100' => [
                [
                    'order' => false, 'limit' => 100,
                ],
            ],
            'order false, offset 0, limit 100' => [
                [
                    'order' => false,
                    'offset' => 0,
                    'limit' => 100,
                ],
            ],
            'order uid, offset 0' => [
                [
                    'order' => ['uid' => QueryInterface::ORDER_ASCENDING],
                    'offset' => 0,
                ],
            ],
            'order uid, limit 100' => [
                [
                    'order' => ['uid' => QueryInterface::ORDER_ASCENDING],
                    'limit' => 100,
                ],
            ],
            'order uid, offset 0, limit 100' => [
                [
                    'order' => ['uid' => QueryInterface::ORDER_ASCENDING],
                    'offset' => 0,
                    'limit' => 100,
                ],
            ],
        ];
    }

    private function applyQueryRequest(QueryInterface $query, array $queryRequest): void
    {
        if (isset($queryRequest['order']) && !$queryRequest['order']) {
            $query->setOrderings([]);
        } elseif (!empty($queryRequest['order'])) {
            $query->setOrderings($queryRequest['order']);
        }
        if (isset($queryRequest['offset'])) {
            $query->setOffset($queryRequest['offset']);
        }
        if (isset($queryRequest['limit'])) {
            $query->setLimit($queryRequest['limit']);
        }
    }

    /**
     * Addresses Relation::HAS_ONE relations.
     */
    #[DataProvider('distinctDataProvider')]
    #[Test]
    public function distinctPersonEntitiesAreFoundByPublisher(array $queryRequest): void
    {
        $query = $this->provideFindPostsByPublisherQuery(1);
        $this->applyQueryRequest($query, $queryRequest);
        $posts = $query->execute();
        $postCount = $posts->count();

        $postIds = $this->resolveEntityIds($posts->toArray());

        self::assertEquals($this->countDistinctIds($postIds), $postCount);
        $this->assertDistinctIds($postIds);
    }

    /**
     * Addresses Relation::HAS_ONE relations.
     */
    #[DataProvider('distinctDataProvider')]
    #[Test]
    public function distinctPersonRecordsAreFoundByPublisher(array $queryRequest): void
    {
        $query = $this->provideFindPostsByPublisherQuery(1);
        $this->applyQueryRequest($query, $queryRequest);
        $postRecords = $query->execute(true);
        $postIds = $this->resolveRecordIds($postRecords);

        $this->assertDistinctIds($postIds);
    }

    private function provideFindPostsByPublisherQuery(int $publisherId): QueryInterface
    {
        $postRepository = $this->get(PostRepository::class);
        $query = $postRepository->createQuery();
        $query->matching(
            $query->logicalOr(
                $query->equals('author.uid', $publisherId),
                $query->equals('reviewer.uid', $publisherId),
            )
        );
        return $query;
    }

    /**
     * Addresses Relation::HAS_MANY relations.
     */
    #[DataProvider('distinctDataProvider')]
    #[Test]
    public function distinctBlogEntitiesAreFoundByPostsSince(array $queryRequest): void
    {
        $query = $this->provideFindBlogsByPostsSinceQuery(
            new \DateTime('2017-08-01')
        );
        $this->applyQueryRequest($query, $queryRequest);
        $blogs = $query->execute();
        $blogCount = $blogs->count();

        $blogIds = $this->resolveEntityIds($blogs->toArray());

        self::assertEquals($this->countDistinctIds($blogIds), $blogCount);
        $this->assertDistinctIds($blogIds);
    }

    /**
     * Addresses Relation::HAS_MANY relations.
     */
    #[DataProvider('distinctDataProvider')]
    #[Test]
    public function distinctBlogRecordsAreFoundByPostsSince(array $queryRequest): void
    {
        $query = $this->provideFindBlogsByPostsSinceQuery(
            new \DateTime('2017-08-01')
        );
        $this->applyQueryRequest($query, $queryRequest);
        $blogRecords = $query->execute(true);
        $blogIds = $this->resolveRecordIds($blogRecords);

        $this->assertDistinctIds($blogIds);
    }

    private function provideFindBlogsByPostsSinceQuery(\DateTime $date): QueryInterface
    {
        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();
        $query->matching(
            $query->greaterThanOrEqual('posts.date', $date)
        );
        return $query;
    }

    /**
     * Addresses Relation::HAS_AND_BELONGS_TO_MANY relations.
     */
    #[DataProvider('distinctDataProvider')]
    #[Test]
    public function distinctPersonEntitiesAreFoundByTagNameAreFiltered(array $queryRequest): void
    {
        $query = $this->provideFindPersonsByTagNameQuery('SharedTag');
        $this->applyQueryRequest($query, $queryRequest);
        $persons = $query->execute();
        $personCount = $persons->count();

        $personIds = $this->resolveEntityIds($persons->toArray());

        self::assertEquals($this->countDistinctIds($personIds), $personCount);
        $this->assertDistinctIds($personIds);
    }

    /**
     * Addresses Relation::HAS_AND_BELONGS_TO_MANY relations.
     */
    #[DataProvider('distinctDataProvider')]
    #[Test]
    public function distinctPersonRecordsAreFoundByTagNameAreFiltered(array $queryRequest): void
    {
        $query = $this->provideFindPersonsByTagNameQuery('SharedTag');
        $this->applyQueryRequest($query, $queryRequest);
        $personRecords = $query->execute(true);
        $personIds = $this->resolveRecordIds($personRecords);

        $this->assertDistinctIds($personIds);
    }

    private function provideFindPersonsByTagNameQuery(string $tagName): QueryInterface
    {
        $personRepository = $this->get(PersonRepository::class);
        $query = $personRepository->createQuery();
        $query->matching(
            $query->logicalOr(
                $query->equals('tags.name', $tagName),
                $query->equals('tagsSpecial.name', $tagName),
            )
        );
        return $query;
    }

    /**
     * Addresses Relation::HAS_ONE, Relation::HAS_AND_BELONGS_TO_MANY relations.
     */
    #[DataProvider('distinctDataProvider')]
    #[Test]
    public function distinctPostEntitiesAreFoundByAuthorTagNameAreFiltered(array $queryRequest): void
    {
        $query = $this->provideFindPostsByAuthorTagName('SharedTag');
        $this->applyQueryRequest($query, $queryRequest);
        $posts = $query->execute();
        $postCount = $posts->count();

        $postsIds = $this->resolveEntityIds($posts->toArray());

        self::assertEquals($this->countDistinctIds($postsIds), $postCount);
        $this->assertDistinctIds($postsIds);
    }

    /**
     * Addresses Relation::HAS_ONE, Relation::HAS_AND_BELONGS_TO_MANY relations.
     */
    #[DataProvider('distinctDataProvider')]
    #[Test]
    public function distinctPostRecordsAreFoundByAuthorTagNameAreFiltered(array $queryRequest): void
    {
        $query = $this->provideFindPostsByAuthorTagName('SharedTag');
        $this->applyQueryRequest($query, $queryRequest);
        $postRecords = $query->execute(true);
        $postsIds = $this->resolveRecordIds($postRecords);

        $this->assertDistinctIds($postsIds);
    }

    private function provideFindPostsByAuthorTagName(string $tagName): QueryInterface
    {
        $postRepository = $this->get(PostRepository::class);
        $query = $postRepository->createQuery();
        $query->matching(
            $query->logicalOr(
                $query->equals('author.tags.name', $tagName),
                $query->equals('author.tagsSpecial.name', $tagName),
            )
        );
        return $query;
    }

    /**
     * Helper method for persisting blog
     */
    private function updateAndPersistBlog(): void
    {
        $blogRepository = $this->get(BlogRepository::class);
        $blogRepository->update($this->blog);
        $this->persistenceManager->persistAll();
    }

    /**
     * @param AbstractEntity[] $entities
     * @return int[]
     */
    private function resolveEntityIds(array $entities): array
    {
        return array_map(
            static fn(AbstractEntity $entity): int => $entity->getUid(),
            $entities
        );
    }

    /**
     * @return int[]
     */
    private function resolveRecordIds(array $records): array
    {
        return array_column($records, 'uid');
    }

    /**
     * Counts amount of distinct IDS.
     */
    private function countDistinctIds(array $ids): int
    {
        return count(array_unique($ids));
    }

    /**
     * Asserts distinct IDs by comparing the sum of the occurrence of
     * a particular ID to the amount of existing distinct IDs.
     */
    private function assertDistinctIds(array $ids): void
    {
        $counts = array_count_values($ids);
        self::assertEquals(count($counts), array_sum($counts));
    }
}
