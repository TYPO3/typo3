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

use ExtbaseTeam\BlogExample\Domain\Model\Blog;
use ExtbaseTeam\BlogExample\Domain\Model\Post;
use ExtbaseTeam\BlogExample\Domain\Model\Tag;
use ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository;
use ExtbaseTeam\BlogExample\Domain\Repository\PersonRepository;
use ExtbaseTeam\BlogExample\Domain\Repository\PostRepository;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RelationTest extends FunctionalTestCase
{
    /**
     * @var Blog
     */
    protected $blog;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistentManager;

    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager
     */
    protected $objectManager;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/persons.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-tag-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/categories.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/category-mm.xml');

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->persistentManager = $this->objectManager->get(PersistenceManager::class);
        /* @var $blogRepository \TYPO3\CMS\Extbase\Persistence\Repository */
        $blogRepository = $this->objectManager->get(BlogRepository::class);
        $this->blog = $blogRepository->findByUid(1);

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->workspace = 0;
    }

    /**
     * Tests adding object at the end of sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function attachPostToBlogAtTheEnd()
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->getRestrictions()->removeAll();
        $countPostsOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), \PDO::PARAM_INT)
                )
            )->execute()
            ->fetchColumn(0);

        $newPostTitle = 'sdufhisdhuf';
        /** @var Post $newPost */
        $newPost = $this->objectManager->get(Post::class);
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
                    $queryBuilder->createNamedParameter($this->blog->getUid(), \PDO::PARAM_INT)
                )
            )->execute()
            ->fetchColumn(0);
        self::assertEquals($countPostsOriginal + 1, $countPosts);

        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), \PDO::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        self::assertSame($newPostTitle, $post['title']);
        self::assertEquals($countPostsOriginal + 1, $post['sorting']);
    }

    /**
     * Tests removing object from the end of sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function removeLastPostFromBlog()
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->getRestrictions()
            ->removeAll()->add(new DeletedRestriction());
        $countPostsOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_post')
            ->execute()
            ->fetchColumn(0);

        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), \PDO::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
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
            ->execute()
            ->fetchColumn(0);
        self::assertEquals($countPostsOriginal - 1, $countPosts);

        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($latestPost->getUid(), \PDO::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        self::assertNull($post['uid']);

        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), \PDO::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        self::assertSame('Post9', $post['title']);
        self::assertEquals(9, $post['sorting']);
    }

    /**
     * Tests adding object in the middle of the sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function addPostToBlogInTheMiddle()
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->getRestrictions()
            ->removeAll()->add(new DeletedRestriction());
        $countPostsOriginal = $queryBuilder
        ->count('*')
        ->from('tx_blogexample_domain_model_post')
        ->execute()
        ->fetchColumn(0);

        /** @var Post $newPost */
        $newPost = $this->objectManager->get(Post::class);

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
            ->execute()
            ->fetchColumn(0);
        self::assertEquals($countPostsOriginal + 1, $countPosts);

        //last post
        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), \PDO::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
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
                    $queryBuilder->createNamedParameter($newPost->getUid(), \PDO::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        self::assertSame($newPostTitle, $post['title']);
        self::assertEquals(6, $post['sorting']);
    }

    /**
     * Tests removing object from the middle of sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function removeMiddlePostFromBlog()
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->getRestrictions()
            ->removeAll()->add(new DeletedRestriction());
        $countPostsOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_post')
            ->execute()
            ->fetchColumn(0);

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
            ->execute()
            ->fetchColumn(0);
        self::assertEquals($countPostsOriginal - 1, $countPosts);

        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), \PDO::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        self::assertSame('Post10', $post['title']);
        self::assertEquals(10, $post['sorting']);
    }

    /**
     * Tests moving object from the end to the middle of the sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function movePostFromEndToTheMiddle()
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->getRestrictions()
            ->removeAll()->add(new DeletedRestriction());
        $countPostsOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_post')
            ->execute()
            ->fetchColumn(0);

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
            ->execute()
            ->fetchColumn(0);
        self::assertEquals($countPostsOriginal, $countPosts);

        $queryBuilder->getRestrictions()->removeAll();
        $post = $queryBuilder
            ->select('title', 'sorting')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq(
                    'blog',
                    $queryBuilder->createNamedParameter($this->blog->getUid(), \PDO::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        self::assertSame('Post9', $post['title']);
        self::assertEquals(10, $post['sorting']);

        $queryBuilder->resetQueryParts();
        $post = $queryBuilder
            ->select('title', 'uid')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'blog',
                        $queryBuilder->createNamedParameter($this->blog->getUid(), \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq('sorting', $queryBuilder->createNamedParameter(6, \PDO::PARAM_INT))
                )
            )
            ->execute()
            ->fetch();
        self::assertSame('MOVED POST Post10', $post['title']);
        self::assertEquals(10, $post['uid']);
    }

    /**
     * Tests adding object at the end of sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function attachTagToPostAtTheEnd()
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_tag');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $countOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_tag')
            ->execute()
            ->fetchColumn(0);

        $newTagTitle = 'sdufhisdhuf';

        /** @var Tag $newTag */
        $newTag = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Model\\Tag', $newTagTitle);

        /** @var PostRepository $postRepository */
        $postRepository = $this->objectManager->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $post->addTag($newTag);

        $postRepository->update($post);
        $this->persistentManager->persistAll();

        $queryBuilder->resetQueryParts();
        $count = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_tag')
            ->execute()
            ->fetchColumn(0);
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
                    $queryBuilder->createNamedParameter($post->getUid(), \PDO::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        self::assertEquals($newTag->getUid(), $tag['uid_foreign']);
    }

    /**
     * Tests removing object from the end of sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function removeLastTagFromPost()
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_tag');
        $queryBuilder->getRestrictions()
            ->removeAll()->add(new DeletedRestriction());
        $countOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_tag')
            ->execute()
            ->fetchColumn(0);

        /** @var PostRepository $postRepository */
        $postRepository = $this->objectManager->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = $post->getTags();
        $tagsArray = $tags->toArray();
        $latestTag = array_pop($tagsArray);

        self::assertEquals(10, $latestTag->getUid());

        $post->removeTag($latestTag);

        $postRepository->update($post);
        $this->persistentManager->persistAll();

        $queryBuilder->resetQueryParts();
        $countTags = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_domain_model_tag')
            ->execute()
            ->fetchColumn(0);
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
                    $queryBuilder->createNamedParameter($post->getUid(), \PDO::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        self::assertEquals(9, $tag['uid_foreign']);

        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter($post->getUid(), \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'uid_foreign',
                        $queryBuilder->createNamedParameter($latestTag->getUid(), \PDO::PARAM_INT)
                    )
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        self::assertNull($tag['uid_foreign']);
    }

    /**
     * Tests adding object in the middle of sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function addTagToPostInTheMiddle()
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_post_tag_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $countTagsOriginal = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchColumn(0);

        /** @var PostRepository $postRepository */
        $postRepository = $this->objectManager->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = clone $post->getTags();
        $post->setTags(new ObjectStorage());

        /** @var Tag $newTag */
        $newTag = $this->objectManager->get(Tag::class, 'INSERTED TAG at position 6 : ' . strftime(''));

        $counter = 1;
        foreach ($tags as $tag) {
            $post->addTag($tag);
            if ($counter === 5) {
                $post->addTag($newTag);
            }
            $counter++;
        }

        $postRepository->update($post);
        $this->persistentManager->persistAll();

        $queryBuilder->resetQueryParts();
        $countTags = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchColumn(0);
        self::assertEquals($countTagsOriginal + 1, $countTags);

        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($post->getUid(), \PDO::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        self::assertEquals(10, $tag['uid_foreign']);

        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter($post->getUid(), \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq('sorting', $queryBuilder->createNamedParameter(6, \PDO::PARAM_INT))
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        self::assertEquals($newTag->getUid(), $tag['uid_foreign']);
    }

    /**
     * Tests removing object from the middle of the sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function removeMiddleTagFromPost()
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_post_tag_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $countTags = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchColumn(0);
        self::assertEquals(10, $countTags);

        /** @var PostRepository $postRepository */
        $postRepository = $this->objectManager->get(PostRepository::class);
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
        $this->persistentManager->persistAll();

        $queryBuilder->resetQueryParts();
        $countTags = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchColumn(0);
        self::assertEquals(9, $countTags);

        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign', 'sorting')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($post->getUid(), \PDO::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        self::assertEquals(10, $tag['uid_foreign']);
        self::assertEquals(10, $tag['sorting']);

        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter($post->getUid(), \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq('sorting', $queryBuilder->createNamedParameter(5, \PDO::PARAM_INT))
                )
            )
            ->execute()
            ->fetch();
        self::assertNull($tag['uid_foreign']);
    }

    /**
     * Tests moving object from the end to the middle of sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function moveTagFromEndToTheMiddle()
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_post_tag_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $countTags = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchColumn(0);
        self::assertEquals(10, $countTags);

        /** @var PostRepository $postRepository */
        $postRepository = $this->objectManager->get(PostRepository::class);
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
        $this->persistentManager->persistAll();

        $queryBuilder->resetQueryParts();
        $countTags = $queryBuilder
            ->count('*')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchColumn(0);
        self::assertEquals(10, $countTags);

        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign', 'sorting')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($post->getUid(), \PDO::PARAM_INT)
                )
            )->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        self::assertEquals(9, $tag['uid_foreign']);
        self::assertEquals(10, $tag['sorting']);

        $sorting = '6';
        $queryBuilder->resetQueryParts();
        $tag = $queryBuilder
            ->select('uid_foreign')
            ->from('tx_blogexample_post_tag_mm')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter($post->getUid(), \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sorting',
                        $queryBuilder->createNamedParameter($sorting, \PDO::PARAM_STR)
                    )
                )
            )
            ->execute()
            ->fetch();
        self::assertEquals(10, $tag['uid_foreign']);
    }

    /**
     * Test if timestamp field is updated when updating a record
     *
     * @test
     */
    public function timestampFieldIsUpdatedOnPostSave()
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $rawPost = $queryBuilder
            ->select('*')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetch();

        /** @var PostRepository $postRepository */
        $postRepository = $this->objectManager->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $post->setTitle('newTitle');

        $postRepository->update($post);
        $this->persistentManager->persistAll();

        $queryBuilder->resetQueryParts();
        $rawPost2 = $queryBuilder
            ->select('*')
            ->from('tx_blogexample_domain_model_post')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetch();
        self::assertTrue($rawPost2['tstamp'] > $rawPost['tstamp']);
    }

    /**
     * Test query matching for mm relation without MM_match_fields defined
     *
     * @test
     */
    public function mmRelationWithoutMatchFieldIsResolved()
    {
        /** @var PostRepository $postRepository */
        $postRepository = $this->objectManager->get(PostRepository::class);
        $posts = $postRepository->findByTagAndBlog('Tag2', $this->blog);
        self::assertCount(1, $posts);
    }

    /**
     * @test
     */
    public function mmRelationWithMatchFieldIsResolvedFromLocalSide()
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('sys_category_record_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $countCategories = $queryBuilder
            ->count('*')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter('tx_blogexample_domain_model_post', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'fieldname',
                        $queryBuilder->createNamedParameter('categories', \PDO::PARAM_STR)
                    )
                )
            )
            ->execute()
            ->fetchColumn(0);
        self::assertEquals(3, $countCategories);

        /** @var PostRepository $postRepository */
        $postRepository = $this->objectManager->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        self::assertSame(3, count($post->getCategories()));
    }

    /**
     * Test query matching respects MM_match_fields
     *
     * @test
     */
    public function mmRelationWithMatchFieldIsResolvedFromForeignSide()
    {
        /** @var PostRepository $postRepository */
        $postRepository = $this->objectManager->get(PostRepository::class);
        $posts = $postRepository->findByCategory(1);
        self::assertSame(2, count($posts));

        $posts = $postRepository->findByCategory(4);
        self::assertSame(0, count($posts));
    }

    /**
     * @test
     */
    public function mmRelationWithMatchFieldIsCreatedFromLocalSide()
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('sys_category_record_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $countCategories = $queryBuilder
            ->count('*')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter('tx_blogexample_domain_model_post', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'fieldname',
                        $queryBuilder->createNamedParameter('categories', \PDO::PARAM_STR)
                    )
                )
            )
            ->execute()
            ->fetchColumn(0);
        self::assertEquals(3, $countCategories);

        /** @var PostRepository $postRepository */
        $postRepository = $this->objectManager->get(PostRepository::class);
        $post = $postRepository->findByUid(1);

        /** @var \TYPO3\CMS\Extbase\Domain\Model\Category $newCategory */
        $newCategory = $this->objectManager->get(Category::class);
        $newCategory->setTitle('New Category');

        $post->addCategory($newCategory);

        $postRepository->update($post);
        $this->persistentManager->persistAll();

        $queryBuilder->resetQueryParts();
        $countCategories = $queryBuilder
            ->count('*')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter('tx_blogexample_domain_model_post', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'fieldname',
                        $queryBuilder->createNamedParameter('categories', \PDO::PARAM_STR)
                    )
                )
            )
            ->execute()
            ->fetchColumn(0);
        self::assertEquals(4, $countCategories);
    }

    /**
     * Test if adjusting existing mm relations do not relations with other objects
     *
     * @test
     */
    public function adjustingMmRelationWithTablesnameAndFieldnameFieldDoNotTouchOtherRelations()
    {
        /** @var PostRepository $postRepository */
        $postRepository = $this->objectManager->get(PostRepository::class);
        /** @var Post $post */
        $post = $postRepository->findByUid(1);
        // Move category down
        foreach ($post->getCategories() as $category) {
            $post->removeCategory($category);
            $post->addCategory($category);
            break;
        }
        $postRepository->update($post);
        $this->persistentManager->persistAll();

        // re-fetch Post and Blog
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('sys_category_record_mm');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $newBlogCategoryCount = $queryBuilder
            ->count('*')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'uid_foreign',
                        $queryBuilder->createNamedParameter($this->blog->getUid(), \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter('tx_blogexample_domain_model_post', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'fieldname',
                        $queryBuilder->createNamedParameter('categories', \PDO::PARAM_STR)
                    )
                )
            )
            ->execute()
            ->fetchColumn(0);

        self::assertEquals($this->blog->getCategories()->count(), $newBlogCategoryCount);
    }

    /**
     * @return array
     */
    public function distinctDataProvider()
    {
        return [
            'order default' => [
                []
            ],
            'order default, offset 0' => [
                [
                    'offset' => 0
                ]
            ],
            'order default, limit 100' => [
                [
                    'limit' => 100
                ]
            ],
            'order default, offset 0, limit 100' => [
                [
                    'offset' => 0,
                    'limit' => 100
                ]
            ],
            'order false' => [
                [
                    'order' => false
                ]
            ],
            'order false, offset 0' => [
                [
                    'order' => false,
                    'offset' => 0
                ]
            ],
            'order false, limit 100' => [
                [
                    'order' => false, 'limit' => 100
                ]
            ],
            'order false, offset 0, limit 100' => [
                [
                    'order' => false,
                    'offset' => 0,
                    'limit' => 100
                ]
            ],
            'order uid, offset 0' => [
                [
                    'order' => ['uid' => QueryInterface::ORDER_ASCENDING],
                    'offset' => 0
                ]
            ],
            'order uid, limit 100' => [
                [
                    'order' => ['uid' => QueryInterface::ORDER_ASCENDING],
                    'limit' => 100
                ]
            ],
            'order uid, offset 0, limit 100' => [
                [
                    'order' => ['uid' => QueryInterface::ORDER_ASCENDING],
                    'offset' => 0,
                    'limit' => 100
                ]
            ],
        ];
    }

    /**
     * @param QueryInterface $query
     * @param array $queryRequest
     */
    protected function applyQueryRequest(QueryInterface $query, array $queryRequest)
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
     *
     * @param $queryRequest
     *
     * @test
     * @dataProvider distinctDataProvider
     */
    public function distinctPersonEntitiesAreFoundByPublisher(array $queryRequest)
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
     *
     * @param $queryRequest
     *
     * @test
     * @dataProvider distinctDataProvider
     */
    public function distinctPersonRecordsAreFoundByPublisher(array $queryRequest)
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
    protected function provideFindPostsByPublisherQuery(int $publisherId)
    {
        $postRepository = $this->objectManager->get(PostRepository::class);
        $query = $postRepository->createQuery();
        $query->matching(
            $query->logicalOr([
                $query->equals('author.uid', $publisherId),
                $query->equals('reviewer.uid', $publisherId)
            ])
        );
        return $query;
    }

    /**
     * Addresses ColumnMap::RELATION_HAS_MANY relations.
     *
     * @param $queryRequest
     *
     * @test
     * @dataProvider distinctDataProvider
     */
    public function distinctBlogEntitiesAreFoundByPostsSince(array $queryRequest)
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
     *
     * @param $queryRequest
     *
     * @test
     * @dataProvider distinctDataProvider
     */
    public function distinctBlogRecordsAreFoundByPostsSince(array $queryRequest)
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
    protected function provideFindBlogsByPostsSinceQuery(\DateTime $date)
    {
        $blogRepository = $this->objectManager->get(BlogRepository::class);
        $query = $blogRepository->createQuery();
        $query->matching(
            $query->greaterThanOrEqual('posts.date', $date)
        );
        return $query;
    }

    /**
     * Addresses ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY relations.
     *
     * @param $queryRequest
     *
     * @test
     * @dataProvider distinctDataProvider
     */
    public function distinctPersonEntitiesAreFoundByTagNameAreFiltered(array $queryRequest)
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
     *
     * @param $queryRequest
     *
     * @test
     * @dataProvider distinctDataProvider
     */
    public function distinctPersonRecordsAreFoundByTagNameAreFiltered(array $queryRequest)
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
    protected function provideFindPersonsByTagNameQuery(string $tagName)
    {
        $personRepository = $this->objectManager->get(PersonRepository::class);
        $query = $personRepository->createQuery();
        $query->matching(
            $query->logicalOr([
                $query->equals('tags.name', $tagName),
                $query->equals('tagsSpecial.name', $tagName)
            ])
        );
        return $query;
    }

    /**
     * Addresses ColumnMap::RELATION_HAS_ONE, ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY relations.
     *
     * @param $queryRequest
     *
     * @test
     * @dataProvider distinctDataProvider
     */
    public function distinctPostEntitiesAreFoundByAuthorTagNameAreFiltered(array $queryRequest)
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
     *
     * @param $queryRequest
     *
     * @test
     * @dataProvider distinctDataProvider
     */
    public function distinctPostRecordsAreFoundByAuthorTagNameAreFiltered(array $queryRequest)
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
    protected function provideFindPostsByAuthorTagName(string $tagName)
    {
        $postRepository = $this->objectManager->get(PostRepository::class);
        $query = $postRepository->createQuery();
        $query->matching(
            $query->logicalOr([
                $query->equals('author.tags.name', $tagName),
                $query->equals('author.tagsSpecial.name', $tagName)
            ])
        );
        return $query;
    }

    /**
     * Helper method for persisting blog
     */
    protected function updateAndPersistBlog()
    {
        /** @var BlogRepository $blogRepository */
        $blogRepository = $this->objectManager->get(BlogRepository::class);
        $blogRepository->update($this->blog);
        $this->persistentManager->persistAll();
    }

    /**
     * @param AbstractEntity[] $entities
     * @return int[]
     */
    protected function resolveEntityIds(array $entities)
    {
        return array_map(
            function (AbstractEntity $entity) {
                return $entity->getUid();
            },
            $entities
        );
    }

    /**
     * @param array $records
     * @return int[]
     */
    protected function resolveRecordIds(array $records)
    {
        return array_column($records, 'uid');
    }

    /**
     * Counts amount of distinct IDS.
     *
     * @param array $ids
     * @return int
     */
    protected function countDistinctIds(array $ids)
    {
        return count(array_unique($ids));
    }

    /**
     * Asserts distinct IDs by comparing the sum of the occurrence of
     * a particular ID to the amount of existing distinct IDs.
     *
     * @param array $ids
     */
    protected function assertDistinctIds(array $ids)
    {
        $counts = array_count_values($ids);
        self::assertEquals(count($counts), array_sum($counts));
    }
}
