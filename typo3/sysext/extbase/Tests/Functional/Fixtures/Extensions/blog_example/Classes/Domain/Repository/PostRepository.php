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

namespace TYPO3Tests\BlogExample\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Post;

/**
 * A repository for blog posts
 *
 * @extends Repository<Post>
 */
class PostRepository extends Repository
{
    protected $defaultOrderings = ['date' => QueryInterface::ORDER_DESCENDING];

    /**
     * Finds all posts by the specified blog
     *
     * @param Blog $blog The blog the post must refer to
     */
    public function findAllByBlog(Blog $blog): QueryResultInterface
    {
        $query = $this->createQuery();
        return $query
            ->matching(
                $query->equals('blog', $blog)
            )
            ->execute();
    }

    /**
     * Finds posts by the specified tag and blog
     *
     * @param Blog $blog The blog the post must refer to
     */
    public function findByTagAndBlog(string $tag, Blog $blog): QueryResultInterface
    {
        $query = $this->createQuery();
        return $query
            ->matching(
                $query->logicalAnd(
                    $query->equals('blog', $blog),
                    $query->equals('tags.name', $tag)
                )
            )
            ->execute();
    }

    /**
     * Finds all remaining posts of the blog
     */
    public function findRemaining(Post $post): QueryResultInterface
    {
        $blog = $post->getBlog();
        $query = $this->createQuery();
        return $query
            ->matching(
                $query->logicalAnd(
                    $query->equals('blog', $blog),
                    $query->logicalNot(
                        $query->equals('uid', $post->getUid())
                    )
                )
            )
            ->execute();
    }

    /**
     * Finds the previous of the given post
     *
     * @param Post $post The reference post
     */
    public function findPrevious(Post $post): Post
    {
        $query = $this->createQuery();
        return $query
            ->matching(
                $query->lessThan('date', $post->getDate())
            )
            ->execute()
            ->getFirst();
    }

    /**
     * Finds the post next to the given post
     *
     * @param Post $post The reference post
     */
    public function findNext(Post $post): Post
    {
        $query = $this->createQuery();
        return $query
            ->matching(
                $query->greaterThan('date', $post->getDate())
            )
            ->execute()
            ->getFirst();
    }

    /**
     * Finds most recent posts by the specified blog
     *
     * @param Blog $blog The blog the post must refer to
     * @param int $limit The number of posts to return at max
     * @return QueryResultInterface The posts
     */
    public function findRecentByBlog(Blog $blog, int $limit = 5): QueryResultInterface
    {
        $query = $this->createQuery();
        return $query
            ->matching(
                $query->equals('blog', $blog)
            )
            ->setLimit((int)$limit)
            ->execute();
    }

    /**
     * Find posts by category
     */
    public function findByCategory(int $categoryUid): QueryResultInterface
    {
        $query = $this->createQuery();
        return $query
            ->matching(
                $query->contains('categories', $categoryUid)
            )
            ->execute();
    }

    /**
     * Find posts by categories (OR combined)
     * @param array<int, int> $categoryUids
     */
    public function findByCategories(array $categoryUids): QueryResultInterface
    {
        $query = $this->createQuery();
        return $query
            ->matching(
                $query->in('categories.uid', $categoryUids)
            )
            ->execute();
    }

    public function findAllSortedByCategory(array $uids): QueryResultInterface
    {
        $q = $this->createQuery();
        $q->matching($q->in('uid', $uids));
        $q->setOrderings([
            'categories.title' => QueryInterface::ORDER_ASCENDING,
            'uid' => QueryInterface::ORDER_ASCENDING,
        ]);
        return $q->execute();
    }
}
