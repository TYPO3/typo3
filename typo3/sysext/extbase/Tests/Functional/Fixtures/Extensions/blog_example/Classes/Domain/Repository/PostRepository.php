<?php
namespace ExtbaseTeam\BlogExample\Domain\Repository;

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

/**
 * A repository for blog posts
 *
 * @method \ExtbaseTeam\BlogExample\Domain\Model\Post findByUid($uid)
 */
class PostRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    protected $defaultOrderings = ['date' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING];

    /**
     * Finds all posts by the specified blog
     *
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Blog $blog The blog the post must refer to
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface The posts
     */
    public function findAllByBlog(\ExtbaseTeam\BlogExample\Domain\Model\Blog $blog)
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
     * @param string $tag
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Blog $blog The blog the post must refer to
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface The posts
     */
    public function findByTagAndBlog($tag, \ExtbaseTeam\BlogExample\Domain\Model\Blog $blog)
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
     *
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Post $post The reference post
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface The posts
     */
    public function findRemaining(\ExtbaseTeam\BlogExample\Domain\Model\Post $post)
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
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Post $post The reference post
     * @return \ExtbaseTeam\BlogExample\Domain\Model\Post
     */
    public function findPrevious(\ExtbaseTeam\BlogExample\Domain\Model\Post $post)
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
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Post $post The reference post
     * @return \ExtbaseTeam\BlogExample\Domain\Model\Post
     */
    public function findNext(\ExtbaseTeam\BlogExample\Domain\Model\Post $post)
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
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Blog $blog The blog the post must refer to
     * @param int $limit The number of posts to return at max
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface The posts
     */
    public function findRecentByBlog(\ExtbaseTeam\BlogExample\Domain\Model\Blog $blog, $limit = 5)
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
     *
     * @param int $categoryUid
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findByCategory($categoryUid)
    {
        $query = $this->createQuery();
        return $query
            ->matching(
                $query->contains('categories', $categoryUid)
            )
            ->execute();
    }
}
