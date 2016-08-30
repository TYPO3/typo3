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
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class RelationTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    /**
     * @var \ExtbaseTeam\BlogExample\Domain\Model\Blog
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
    protected function setUp()
    {
        parent::setUp();

        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-tag-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/categories.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/category-mm.xml');

        $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->persistentManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
        /* @var $blogRepository \TYPO3\CMS\Extbase\Persistence\Repository */
        $blogRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository::class);
        $this->blog = $blogRepository->findByUid(1);
    }

    /**
     * Tests adding object at the end of sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function attachPostToBlogAtTheEnd()
    {
        $countPostsOriginal = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_post', 'blog =' . $this->blog->getUid());

        $newPostTitle = 'sdufhisdhuf';
        /** @var \ExtbaseTeam\BlogExample\Domain\Model\Post $newPost */
        $newPost = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Model\Post::class);
        $newPost->setBlog($this->blog);
        $newPost->setTitle($newPostTitle);
        $newPost->setContent('Bla Bla Bla');

        $this->blog->addPost($newPost);
        $this->updateAndPersistBlog();

        $countPosts = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_post', 'blog =' . $this->blog->getUid());
        $this->assertSame(($countPostsOriginal + 1), $countPosts);

        $post = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('title,sorting', 'tx_blogexample_domain_model_post', 'blog =' . $this->blog->getUid(), '', 'sorting DESC');
        $this->assertSame($newPostTitle, $post['title']);
        $this->assertSame((string)($countPostsOriginal + 1), $post['sorting']);
    }

    /**
     * Tests removing object from the end of sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function removeLastPostFromBlog()
    {
        $countPostsOriginal = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_post', 'deleted=0');

        $post = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('sorting', 'tx_blogexample_domain_model_post', 'blog =' . $this->blog->getUid(), '', 'sorting DESC');
        $this->assertEquals(10, $post['sorting']);

        $posts = $this->blog->getPosts();
        $postsArray = $posts->toArray();
        $latestPost = array_pop($postsArray);

        $this->assertEquals(10, $latestPost->getUid());

        $this->blog->removePost($latestPost);
        $this->updateAndPersistBlog();

        $countPosts = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_post', 'deleted=0');
        $this->assertEquals(($countPostsOriginal - 1), $countPosts);

        $post = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid', 'tx_blogexample_domain_model_post', 'uid =' . $latestPost->getUid() . ' AND deleted=0');
        $this->assertSame(null, $post['uid']);

        $post = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('title,sorting', 'tx_blogexample_domain_model_post', 'blog =' . $this->blog->getUid(), '', 'sorting DESC');
        $this->assertSame('Post9', $post['title']);
        $this->assertSame('9', $post['sorting']);
    }

    /**
     * Tests adding object in the middle of the sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function addPostToBlogInTheMiddle()
    {
        $countPostsOriginal = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_post', 'deleted=0');

        /** @var \ExtbaseTeam\BlogExample\Domain\Model\Post $newPost */
        $newPost = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Model\Post::class);

        $posts = clone $this->blog->getPosts();
        $this->blog->getPosts()->removeAll($posts);
        $counter = 1;
        $newPostTitle = 'INSERTED POST at position 6';
        foreach ($posts as $post) {
            $this->blog->addPost($post);
            if ($counter == 5) {
                $newPost->setBlog($this->blog);
                $newPost->setTitle($newPostTitle);
                $newPost->setContent('Bla Bla Bla');
                $this->blog->addPost($newPost);
            }
            $counter++;
        }
        $this->updateAndPersistBlog();

        $countPosts = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_post', 'deleted=0');
        $this->assertSame(($countPostsOriginal + 1), $countPosts);

        //last post
        $post = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('title,sorting', 'tx_blogexample_domain_model_post', 'blog =' . $this->blog->getUid(), '', 'sorting DESC');
        $this->assertSame('Post10', $post['title']);
        $this->assertSame('11', $post['sorting']);

        // check sorting of the post added in the middle
        $post = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('title,sorting', 'tx_blogexample_domain_model_post', 'uid=' . $newPost->getUid());
        $this->assertSame($newPostTitle, $post['title']);
        $this->assertSame('6', $post['sorting']);
    }

    /**
     * Tests removing object from the middle of sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function removeMiddlePostFromBlog()
    {
        $countPostsOriginal = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_post', 'deleted=0');

        $posts = clone $this->blog->getPosts();
        $counter = 1;
        foreach ($posts as $post) {
            if ($counter == 5) {
                $this->blog->removePost($post);
            }
            $counter++;
        }
        $this->updateAndPersistBlog();

        $countPosts = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_post', 'deleted=0');
        $this->assertSame(($countPostsOriginal - 1), $countPosts);

        $post = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('title,sorting', 'tx_blogexample_domain_model_post', 'blog =' . $this->blog->getUid(), '', 'sorting DESC');
        $this->assertSame('Post10', $post['title']);
        $this->assertSame('10', $post['sorting']);
    }

    /**
     * Tests moving object from the end to the middle of the sorted 1:M relation (Blog:Posts)
     *
     * @test
     */
    public function movePostFromEndToTheMiddle()
    {
        $countPostsOriginal = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_post', 'deleted=0');

        $posts = clone $this->blog->getPosts();
        $postsArray = $posts->toArray();
        $latestPost = array_pop($postsArray);

        $this->blog->getPosts()->removeAll($posts);
        $counter = 0;
        $postCount = $posts->count();
        foreach ($posts as $post) {
            if ($counter != ($postCount - 1)) {
                $this->blog->addPost($post);
            }
            if ($counter == 4) {
                $latestPost->setTitle('MOVED POST ' . $latestPost->getTitle());
                $this->blog->addPost($latestPost);
            }
            $counter++;
        }
        $this->updateAndPersistBlog();

        $countPosts = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_post', 'deleted=0');
        $this->assertSame($countPostsOriginal, $countPosts);

        $post = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('title,sorting', 'tx_blogexample_domain_model_post', 'blog =' . $this->blog->getUid(), '', 'sorting DESC');
        $this->assertSame('Post9', $post['title']);
        $this->assertSame('10', $post['sorting']);

        $post = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('title,uid', 'tx_blogexample_domain_model_post', 'blog =' . $this->blog->getUid() . ' AND sorting=6');
        $this->assertSame('MOVED POST Post10', $post['title']);
        $this->assertSame('10', $post['uid']);
    }

    /**
     * Tests adding object at the end of sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function attachTagToPostAtTheEnd()
    {
        $countOriginal = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_tag');

        $newTagTitle = 'sdufhisdhuf';

        /** @var \ExtbaseTeam\BlogExample\Domain\Model\Tag $newTag */
        $newTag = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Model\\Tag', $newTagTitle);

        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
        $post = $postRepository->findByUid(1);
        $post->addTag($newTag);

        $postRepository->update($post);
        $this->persistentManager->persistAll();

        $count = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_tag');
        $this->assertSame(($countOriginal + 1), $count);

        $tag = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid_foreign', 'tx_blogexample_post_tag_mm', 'uid_local =' . $post->getUid(), '', 'sorting DESC');
        $this->assertSame($newTag->getUid(), (int)$tag['uid_foreign']);
    }

    /**
     * Tests removing object from the end of sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function removeLastTagFromPost()
    {
        $countOriginal = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_tag', 'deleted=0');

        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = $post->getTags();
        $tagsArray = $tags->toArray();
        $latestTag = array_pop($tagsArray);

        $this->assertEquals(10, $latestTag->getUid());

        $post->removeTag($latestTag);

        $postRepository->update($post);
        $this->persistentManager->persistAll();

        $countPosts = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_tag', 'deleted=0');
        $this->assertEquals($countOriginal, $countPosts);

        $tag = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid_foreign', 'tx_blogexample_post_tag_mm', 'uid_local =' . $post->getUid(), '', 'sorting DESC');
        $this->assertSame('9', $tag['uid_foreign']);

        $tag = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid_foreign', 'tx_blogexample_post_tag_mm', 'uid_local =' . $post->getUid() . ' AND uid_foreign=' . $latestTag->getUid());
        $this->assertSame(null, $tag['uid_foreign']);
    }

    /**
     * Tests adding object in the middle of sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function addTagToPostInTheMiddle()
    {
        $countTagsOriginal = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_post_tag_mm', 'uid_local=1');

        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = clone $post->getTags();
        $post->setTags(new ObjectStorage());

        /** @var \ExtbaseTeam\BlogExample\Domain\Model\Tag $newTag */
        $newTag = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Model\Tag::class, 'INSERTED TAG at position 6 : ' . strftime(''));

        $counter = 1;
        foreach ($tags as $tag) {
            $post->addTag($tag);
            if ($counter == 5) {
                $post->addTag($newTag);
            }
            $counter++;
        }

        $postRepository->update($post);
        $this->persistentManager->persistAll();

        $countTags = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_post_tag_mm', 'uid_local=1');
        $this->assertSame(($countTagsOriginal + 1), $countTags);

        $tag = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid_foreign', 'tx_blogexample_post_tag_mm', 'uid_local =' . $post->getUid(), '', 'sorting DESC');
        $this->assertSame('10', $tag['uid_foreign']);

        $tag = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid_foreign', 'tx_blogexample_post_tag_mm', 'uid_local =' . $post->getUid() . ' AND sorting=6');
        $this->assertSame($newTag->getUid(), (int)$tag['uid_foreign']);
    }

    /**
     * Tests removing object from the middle of the sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function removeMiddleTagFromPost()
    {
        $countTags = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_post_tag_mm', 'uid_local=1');
        $this->assertSame(10, $countTags);

        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = clone $post->getTags();
        $counter = 1;
        foreach ($tags as $tag) {
            if ($counter == 5) {
                $post->removeTag($tag);
            }
            $counter++;
        }

        $postRepository->update($post);
        $this->persistentManager->persistAll();

        $countTags = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_post_tag_mm', 'uid_local=1');
        $this->assertSame(9, $countTags);

        $tag = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid_foreign,sorting', 'tx_blogexample_post_tag_mm', 'uid_local =' . $post->getUid(), '', 'sorting DESC');
        $this->assertSame('10', $tag['uid_foreign']);
        $this->assertSame('10', $tag['sorting']);

        $tag = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid_foreign', 'tx_blogexample_post_tag_mm', 'uid_local =' . $post->getUid() . ' AND sorting=5');
        $this->assertSame(null, $tag['uid_foreign']);
    }

    /**
     * Tests moving object from the end to the middle of sorted M:M relation (Post:Tag)
     *
     * @test
     */
    public function moveTagFromEndToTheMiddle()
    {
        $countTags = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_post_tag_mm', 'uid_local=1');
        $this->assertSame(10, $countTags);

        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = clone $post->getTags();
        $tagsArray = $tags->toArray();
        $latestTag = array_pop($tagsArray);
        $post->removeTag($latestTag);
        $post->setTags(new ObjectStorage());

        $counter = 1;
        $tagCount = $tags->count();
        foreach ($tags as $tag) {
            if ($counter != $tagCount) {
                $post->addTag($tag);
            }
            if ($counter == 5) {
                $post->addTag($latestTag);
            }
            $counter++;
        }
        $post->addTag($latestTag);

        $postRepository->update($post);
        $this->persistentManager->persistAll();

        $countTags = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_post_tag_mm', 'uid_local=1');
        $this->assertSame(10, $countTags);

        $tag = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid_foreign,sorting', 'tx_blogexample_post_tag_mm', 'uid_local =' . $post->getUid(), '', 'sorting DESC');
        $this->assertSame('9', $tag['uid_foreign']);
        $this->assertSame('10', $tag['sorting']);

        $sorting = '6';
        $tag = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid_foreign', 'tx_blogexample_post_tag_mm', 'uid_local =' . $post->getUid() . ' AND sorting=' . $sorting);
        $this->assertSame('10', $tag['uid_foreign']);
    }

    /**
     * Test if timestamp field is updated when updating a record
     *
     * @test
     */
    public function timestampFieldIsUpdatedOnPostSave()
    {
        $rawPost = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'tx_blogexample_domain_model_post', 'uid=1');

        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
        $post = $postRepository->findByUid(1);
        $post->setTitle('newTitle');

        $postRepository->update($post);
        $this->persistentManager->persistAll();

        $rawPost2 = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'tx_blogexample_domain_model_post', 'uid=1');
        $this->assertTrue($rawPost2['tstamp'] > $rawPost['tstamp']);
    }

    /**
     * Test query matching for mm relation without MM_match_fields defined
     *
     * @test
     */
    public function mmRelationWithoutMatchFieldIsResolved()
    {
        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
        $posts = $postRepository->findByTagAndBlog('Tag2', $this->blog);
        $this->assertSame(1, count($posts));
    }

    /**
     * @test
     */
    public function mmRelationWithMatchFieldIsResolvedFromLocalSide()
    {
        $countCategories = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'sys_category_record_mm', 'uid_foreign=1 AND tablenames="tx_blogexample_domain_model_post" AND fieldname="categories"');
        $this->assertSame(3, $countCategories);

        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
        $post = $postRepository->findByUid(1);
        $this->assertSame(3, count($post->getCategories()));
    }

    /**
     * Test query matching respects MM_match_fields
     *
     * @test
     */
    public function mmRelationWithMatchFieldIsResolvedFromForeignSide()
    {
        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
        $posts = $postRepository->findByCategory(1);
        $this->assertSame(2, count($posts));

        $posts = $postRepository->findByCategory(4);
        $this->assertSame(0, count($posts));
    }

    /**
     * @test
     */
    public function mmRelationWithMatchFieldIsCreatedFromLocalSide()
    {
        $countCategories = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'sys_category_record_mm', 'uid_foreign=1 AND tablenames="tx_blogexample_domain_model_post" AND fieldname="categories"');
        $this->assertSame(3, $countCategories);

        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
        $post = $postRepository->findByUid(1);

        /** @var \TYPO3\CMS\Extbase\Domain\Model\Category $newCategory */
        $newCategory = $this->objectManager->get(\TYPO3\CMS\Extbase\Domain\Model\Category::class);
        $newCategory->setTitle('New Category');

        $post->addCategory($newCategory);

        $postRepository->update($post);
        $this->persistentManager->persistAll();

        $countCategories = $this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'sys_category_record_mm',
            'uid_foreign=1 AND tablenames="tx_blogexample_domain_model_post" AND fieldname="categories"'
        );
        $this->assertSame(4, $countCategories);
    }

    /**
     * Test if adjusting existing mm relations do not relations with other objects
     *
     * @test
     */
    public function adjustingMmRelationWithTablesnameAndFieldnameFieldDoNotTouchOtherRelations()
    {
        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository $postRepository */
        $postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
        /** @var \ExtbaseTeam\BlogExample\Domain\Model\Post $post */
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
        $newBlogCategoryCount = $this->getDatabaseConnection()->exec_SELECTcountRows(
            'uid_local',
            'sys_category_record_mm',
            'tablenames = "tx_blogexample_domain_model_blog"
			AND fieldname = "categories"
			AND uid_foreign = ' . $this->blog->getUid() . ''
        );

        $this->assertSame($this->blog->getCategories()->count(), $newBlogCategoryCount);
    }

    /**
     * Helper method for persisting blog
     */
    protected function updateAndPersistBlog()
    {
        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository $blogRepository */
        $blogRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository::class);
        $blogRepository->update($this->blog);
        $this->persistentManager->persistAll();
    }
}
