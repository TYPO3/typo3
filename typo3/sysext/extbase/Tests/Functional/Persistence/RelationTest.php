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

use ExtbaseTeam\BlogExample\Domain\Model\Blog;
use ExtbaseTeam\BlogExample\Domain\Model\Category;
use ExtbaseTeam\BlogExample\Domain\Model\Post;
use ExtbaseTeam\BlogExample\Domain\Model\Tag;
use ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository;
use ExtbaseTeam\BlogExample\Domain\Repository\PersonRepository;
use ExtbaseTeam\BlogExample\Domain\Repository\PostRepository;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RelationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected Blog $blog;
    protected PersistenceManager $persistenceManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/blogs.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/posts.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/persons.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/tags.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/tags-mm.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/post-tag-mm.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/categories.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/category-mm.csv');

        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->persistenceManager = $this->get(PersistenceManager::class);
        $this->blog = $this->get(BlogRepository::class)->findByUid(1);

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->workspace = 0;
    }

    /**
     * Tests adding object at the end of sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function attachPostToBlogAtTheEnd(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->getRestrictions()->removeAll()->add(new WorkspaceRestriction(0));
        $countPostsOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), Connection::PARAM_INT)
                )
            )->executeQuery()
            ->fetchOne();

        $newPostTitle = 'sdufhisdhuf';
        $newPost = new Post();
        $newPost->setBlog($this->blog);
        $newPost->setTitle($newPostTitle);
        $newPost->setContent('Bla Bla Bla');

        $this->blog->addPost($newPost);
        $this->updateAndPersistBlog();

        $queryBuilder->resetQueryParts();
        $countPosts  = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), Connection::PARAM_INT)
                )
            )->executeQuery()
            ->fetchOne();
        self::assertEquals($countPostsOriginal + 1, $countPosts);

        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), Connection::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertSame($newPostTitle, $post['title']);
        self::assertEquals($countPostsOriginal + 1, $post['sorting']);
    }

    /**
     * Tests removing object from the end of sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function removeLastPostFromBlog(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->getRestrictions()
            ->removeAll()->add(new DeletedRestriction());
        $countPostsOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_post')
            ->executeQuery()
            ->fetchOne();

        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), Connection::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertEquals(10, $post['sorting']);

        $posts = $this->blog->getPosts();
        $postsArray = $posts->toArray();
        $latestPost = array_pop($postsArray);

        self::assertEquals(10, $latestPost->getUid());

        $this->blog->removePost($latestPost);
        $this->updateAndPersistBlog();

        $queryBuilder->resetQueryParts();
        $countPosts = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_post')
            ->executeQuery()
            ->fetchOne();
        self::assertEquals($countPostsOriginal - 1, $countPosts);

        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($latestPost->getUid(), Connection::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertNull($post['uid'] ?? null);

        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), Connection::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertSame('Post9', $post['title']);
        self::assertEquals(9, $post['sorting']);
    }

    /**
     * Tests adding object in the middle of the sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function addPostToBlogInTheMiddle(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->getRestrictions()
            ->removeAll()->add(new DeletedRestriction());
        $countPostsOriginal = $queryBuilder
        ->count('*')
        ->from('tx_blogexample_domain_model_post')
        ->executeQuery()
        ->fetchOne();

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

        $queryBuilder->resetQueryParts();
        $countPosts = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_post')
            ->executeQuery()
            ->fetchOne();
        self::assertEquals($countPostsOriginal + 1, $countPosts);

        //last post
        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), Connection::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertSame('Post10', $post['title']);
        self::assertEquals(11, $post['sorting']);

        // check sorting of the post added in the middle
        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($newPost->getUid(), Connection::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertSame($newPostTitle, $post['title']);
        self::assertEquals(6, $post['sorting']);
    }

    /**
     * Tests removing object from the middle of sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function removeMiddlePostFromBlog(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->getRestrictions()
            ->removeAll()->add(new DeletedRestriction());
        $countPostsOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_post')
            ->executeQuery()
            ->fetchOne();

        $posts = clone $this->blog->getPosts();
        $counter = 1;
        foreach ($posts as $post) {
            if ($counter === 5) {
                $this->blog->removePost($post);
            }
            $counter++;
        }
        $this->updateAndPersistBlog();

        $queryBuilder->resetQueryParts();
        $countPosts = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_post')
            ->executeQuery()
            ->fetchOne();
        self::assertEquals($countPostsOriginal - 1, $countPosts);

        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), Connection::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertSame('Post10', $post['title']);
        self::assertEquals(10, $post['sorting']);
    }

    /**
     * Tests moving object from the end to the middle of the sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function movePostFromEndToTheMiddle(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->getRestrictions()
            ->removeAll()->add(new DeletedRestriction());
        $countPostsOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_post')
            ->executeQuery()
            ->fetchOne();

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

        $queryBuilder->resetQueryParts();
        $countPosts = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_post')
            ->executeQuery()
            ->fetchOne();
        self::assertEquals($countPostsOriginal, $countPosts);

        $queryBuilder->getRestrictions()->removeAll();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), Connection::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertSame('Post9', $post['title']);
        self::assertEquals(10, $post['sorting']);

        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'uid')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'blog',
                        $queryBuilder->createNamedParameter($this->blog->getUid(), Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq('sorting', $queryBuilder->createNamedParameter(6, Connection::PARAM_INT))
                )
            )
            ->executeQuery()
            ->fetchAssociative();
        self::assertSame('MOVED POST Post10', $post['title']);
        self::assertEquals(10, $post['uid']);
    }

    /**
     * Tests adding object at the end of sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function attachTagToPostAtTheEnd(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_tag');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $countOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_tag')
            ->executeQuery()
            ->fetchOne();

        $newTagTitle = 'sdufhisdhuf';

        $newTag = new Tag($newTagTitle);

        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $post->addTag($newTag);

        $postRepository->update($post);
        $this->persistenceManager->persistAll();

        $queryBuilder->resetQueryParts();
        $count = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_tag')
            ->executeQuery()
            ->fetchOne();
        self::assertEquals($countOriginal + 1, $count);

        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_post_tag_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $tag = $queryBuilder
            ->select('uid_foreign')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($post->getUid(), Connection::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertEquals($newTag->getUid(), $tag['uid_foreign']);
    }

    /**
     * Tests removing object from the end of sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function removeLastTagFromPost(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_tag');
        $queryBuilder->getRestrictions()
            ->removeAll()->add(new DeletedRestriction());
        $countOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_tag')
            ->executeQuery()
            ->fetchOne();

        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = $post->getTags();
        $tagsArray = $tags->toArray();
        $latestTag = array_pop($tagsArray);

        self::assertEquals(10, $latestTag->getUid());

        $post->removeTag($latestTag);

        $postRepository->update($post);
        $this->persistenceManager->persistAll();

        $queryBuilder->resetQueryParts();
        $countTags = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_tag')
            ->executeQuery()
            ->fetchOne();
        self::assertEquals($countOriginal, $countTags);

        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_post_tag_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $tag = $queryBuilder
            ->select('uid_foreign')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($post->getUid(), Connection::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertEquals(9, $tag['uid_foreign']);

        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter($post->getUid(), Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'uid_foreign',
                        $queryBuilder->createNamedParameter($latestTag->getUid(), Connection::PARAM_INT)
                    )
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertNull($tag['uid_foreign'] ?? null);
    }

    /**
     * Tests adding object in the middle of sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function addTagToPostInTheMiddle(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_post_tag_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $countTagsOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();

        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = clone $post->getTags();
        $post->setTags(new ObjectStorage());

        // @todo Replace deprecated strftime in php 8.1. Suppress warning in v11.
        $newTag = new Tag('INSERTED TAG at position 6 : ' . @strftime(''));

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

        $queryBuilder->resetQueryParts();
        $countTags = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();
        self::assertEquals($countTagsOriginal + 1, $countTags);

        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($post->getUid(), Connection::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertEquals(10, $tag['uid_foreign']);

        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter($post->getUid(), Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq('sorting', $queryBuilder->createNamedParameter(6, Connection::PARAM_INT))
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertEquals($newTag->getUid(), $tag['uid_foreign']);
    }

    /**
     * Tests removing object from the middle of the sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function removeMiddleTagFromPost(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_post_tag_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $countTags = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();
        self::assertEquals(10, $countTags);

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

        $queryBuilder->resetQueryParts();
        $countTags = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();
        self::assertEquals(9, $countTags);

        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign', 'sorting')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($post->getUid(), Connection::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertEquals(10, $tag['uid_foreign']);
        self::assertEquals(10, $tag['sorting']);

        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter($post->getUid(), Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq('sorting', $queryBuilder->createNamedParameter(5, Connection::PARAM_INT))
                )
            )
            ->executeQuery()
            ->fetchAssociative();
        self::assertNull($tag['uid_foreign'] ?? null);
    }

    /**
     * Tests moving object from the end to the middle of sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function moveTagFromEndToTheMiddle(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_post_tag_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $countTags = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();
        self::assertEquals(10, $countTags);

        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = clone $post->getTags();
        $tagsArray = $tags->toArray();
        $latestTag = array_pop($tagsArray);
        $post->removeTag($latestTag);
        $post->setTags(new ObjectStorage());

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

        $queryBuilder->resetQueryParts();
        $countTags = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();
        self::assertEquals(10, $countTags);

        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign', 'sorting')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($post->getUid(), Connection::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        self::assertEquals(9, $tag['uid_foreign']);
        self::assertEquals(10, $tag['sorting']);

        $sorting = '6';
        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter($post->getUid(), Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sorting',
                        $queryBuilder->createNamedParameter($sorting)
                    )
                )
            )
            ->executeQuery()
            ->fetchAssociative();
        self::assertEquals(10, $tag['uid_foreign']);
    }

    /**
     * Test if timestamp field is updated when updating a record
     *
     * @test
     */
    public function timestampFieldIsUpdatedOnPostSave(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $rawPost = $queryBuilder
            ->select('*')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $post->setTitle('newTitle');

        $postRepository->update($post);
        $this->persistenceManager->persistAll();

        $queryBuilder->resetQueryParts();
        $rawPost2 = $queryBuilder
            ->select('*')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();
        self::assertTrue($rawPost2['tstamp'] > $rawPost['tstamp']);
    }

    /**
     * Test query matching for mm relation without MM_match_fields defined
     *
     * @test
     */
    public function mmRelationWithoutMatchFieldIsResolved(): void
    {
        $postRepository = $this->get(PostRepository::class);
        $posts = $postRepository->findByTagAndBlog('Tag2', $this->blog);
        self::assertCount(1, $posts);
    }

    /**
     * @test
     */
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
     *
     * @test
     */
    public function mmRelationWithMatchFieldIsResolvedFromForeignSide(): void
    {
        $postRepository = $this->get(PostRepository::class);
        $posts = $postRepository->findByCategory(1);
        self::assertCount(2, $posts);

        $posts = $postRepository->findByCategory(4);
        self::assertCount(0, $posts);
    }

    /**
     * @test
     */
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

        $queryBuilder->resetQueryParts();
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
     *
     * @test
     */
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

    /**
     * @return array
     */
    public function distinctDataProvider(): array
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

    /**
     * @param QueryInterface $query
     * @param array $queryRequest
     */
    protected function applyQueryRequest(QueryInterface $query, array $queryRequest): void
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
     * Addresses ColumnMap::RELATION_HAS_ONE relations.
     * @test
     * @dataProvider distinctDataProvider
     */
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
     * Addresses ColumnMap::RELATION_HAS_ONE relations.
     * @test
     * @dataProvider distinctDataProvider
     */
    public function distinctPersonRecordsAreFoundByPublisher(array $queryRequest): void
    {
        $query = $this->provideFindPostsByPublisherQuery(1);
        $this->applyQueryRequest($query, $queryRequest);
        $postRecords = $query->execute(true);
        $postIds = $this->resolveRecordIds($postRecords);

        $this->assertDistinctIds($postIds);
    }

    /**
     * @param int $publisherId
     * @return QueryInterface
     */
    protected function provideFindPostsByPublisherQuery(int $publisherId): QueryInterface
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
     * Addresses ColumnMap::RELATION_HAS_MANY relations.
     * @test
     * @dataProvider distinctDataProvider
     */
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
     * Addresses ColumnMap::RELATION_HAS_MANY relations.
     * @test
     * @dataProvider distinctDataProvider
     */
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

    /**
     * @param \DateTime $date
     * @return QueryInterface
     */
    protected function provideFindBlogsByPostsSinceQuery(\DateTime $date): QueryInterface
    {
        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();
        $query->matching(
            $query->greaterThanOrEqual('posts.date', $date)
        );
        return $query;
    }

    /**
     * Addresses ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY relations.
     * @test
     * @dataProvider distinctDataProvider
     */
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
     * Addresses ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY relations.
     * @test
     * @dataProvider distinctDataProvider
     */
    public function distinctPersonRecordsAreFoundByTagNameAreFiltered(array $queryRequest): void
    {
        $query = $this->provideFindPersonsByTagNameQuery('SharedTag');
        $this->applyQueryRequest($query, $queryRequest);
        $personRecords = $query->execute(true);
        $personIds = $this->resolveRecordIds($personRecords);

        $this->assertDistinctIds($personIds);
    }

    /**
     * @param string $tagName
     * @return QueryInterface
     */
    protected function provideFindPersonsByTagNameQuery(string $tagName): QueryInterface
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
     * Addresses ColumnMap::RELATION_HAS_ONE, ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY relations.
     * @test
     * @dataProvider distinctDataProvider
     */
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
     * Addresses ColumnMap::RELATION_HAS_ONE, ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY relations.
     * @test
     * @dataProvider distinctDataProvider
     */
    public function distinctPostRecordsAreFoundByAuthorTagNameAreFiltered(array $queryRequest): void
    {
        $query = $this->provideFindPostsByAuthorTagName('SharedTag');
        $this->applyQueryRequest($query, $queryRequest);
        $postRecords = $query->execute(true);
        $postsIds = $this->resolveRecordIds($postRecords);

        $this->assertDistinctIds($postsIds);
    }

    /**
     * @param string $tagName
     * @return QueryInterface
     */
    protected function provideFindPostsByAuthorTagName(string $tagName): QueryInterface
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
    protected function updateAndPersistBlog(): void
    {
        $blogRepository = $this->get(BlogRepository::class);
        $blogRepository->update($this->blog);
        $this->persistenceManager->persistAll();
    }

    /**
     * @param AbstractEntity[] $entities
     * @return int[]
     */
    protected function resolveEntityIds(array $entities): array
    {
        return array_map(
            static function (AbstractEntity $entity) {
                return $entity->getUid();
            },
            $entities
        );
    }

    /**
     * @param array $records
     * @return int[]
     */
    protected function resolveRecordIds(array $records): array
    {
        return array_column($records, 'uid');
    }

    /**
     * Counts amount of distinct IDS.
     *
     * @param array $ids
     * @return int
     */
    protected function countDistinctIds(array $ids): int
    {
        return count(array_unique($ids));
    }

    /**
     * Asserts distinct IDs by comparing the sum of the occurrence of
     * a particular ID to the amount of existing distinct IDs.
     *
     * @param array $ids
     */
    protected function assertDistinctIds(array $ids): void
    {
        $counts = array_count_values($ids);
        self::assertEquals(count($counts), array_sum($counts));
    }
}
