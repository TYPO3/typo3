<?php
namespace ExtbaseTeam\BlogExample\Domain\Model;

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
 * A blog post
 */
class Post extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var \ExtbaseTeam\BlogExample\Domain\Model\Blog
     */
    protected $blog = null;

    /**
     * @var string
     * @validate StringLength(minimum = 3, maximum = 50)
     */
    protected $title = '';

    /**
     * @var \DateTime
     */
    protected $date = null;

    /**
     * @var \ExtbaseTeam\BlogExample\Domain\Model\Person
     */
    protected $author = null;

    /**
     * @var string
     * @validate StringLength(minimum = 3)
     */
    protected $content = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\ExtbaseTeam\BlogExample\Domain\Model\Tag>
     */
    protected $tags = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>
     */
    protected $categories = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\ExtbaseTeam\BlogExample\Domain\Model\Comment>
     * @lazy
     * @cascade remove
     */
    protected $comments = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\ExtbaseTeam\BlogExample\Domain\Model\Post>
     * @lazy
     */
    protected $relatedPosts = null;

    /**
     * Constructs this post
     */
    public function __construct()
    {
        $this->tags = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->categories = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->comments = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->relatedPosts = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->date = new \DateTime();
    }

    /**
     * Sets the blog this post is part of
     *
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Blog $blog The blog
     * @return void
     */
    public function setBlog(\ExtbaseTeam\BlogExample\Domain\Model\Blog $blog)
    {
        $this->blog = $blog;
    }

    /**
     * Returns the blog this post is part of
     *
     * @return \ExtbaseTeam\BlogExample\Domain\Model\Blog The blog this post is part of
     */
    public function getBlog()
    {
        return $this->blog;
    }

    /**
     * Setter for title
     *
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Getter for title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Setter for date
     *
     * @param \DateTime $date
     * @return void
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Getter for date
     *
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Setter for tags
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $tags One or more Tag objects
     * @return void
     */
    public function setTags(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Adds a tag to this post
     *
     * @param Tag $tag
     * @return void
     */
    public function addTag(Tag $tag)
    {
        $this->tags->attach($tag);
    }

    /**
     * Removes a tag from this post
     *
     * @param Tag $tag
     * @return void
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->detach($tag);
    }

    /**
     * Remove all tags from this post
     *
     * @return void
     */
    public function removeAllTags()
    {
        $this->tags = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Getter for tags
     * Note: We return a clone of the tags because they must not be modified as they are Value Objects
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage A storage holding objects
     */
    public function getTags()
    {
        return clone $this->tags;
    }

    /**
     * Add category to a post
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\Category $category
     */
    public function addCategory(\TYPO3\CMS\Extbase\Domain\Model\Category $category)
    {
        $this->categories->attach($category);
    }

    /**
     * Set categories
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $categories
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    }

    /**
     * Get categories
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Remove category from post
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\Category $category
     */
    public function removeCategory(\TYPO3\CMS\Extbase\Domain\Model\Category $category)
    {
        $this->categories->detach($category);
    }

    /**
     * Sets the author for this post
     *
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Person $author
     * @return void
     */
    public function setAuthor(\ExtbaseTeam\BlogExample\Domain\Model\Person $author)
    {
        $this->author = $author;
    }

    /**
     * Getter for author
     *
     * @return \ExtbaseTeam\BlogExample\Domain\Model\Person
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Sets the content for this post
     *
     * @param string $content
     * @return void
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Getter for content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Setter for the comments to this post
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $comments An Object Storage of related Comment instances
     * @return void
     */
    public function setComments(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $comments)
    {
        $this->comments = $comments;
    }

    /**
     * Adds a comment to this post
     *
     * @param Comment $comment
     * @return void
     */
    public function addComment(Comment $comment)
    {
        $this->comments->attach($comment);
    }

    /**
     * Removes Comment from this post
     *
     * @param Comment $commentToDelete
     * @return void
     */
    public function removeComment(Comment $commentToDelete)
    {
        $this->comments->detach($commentToDelete);
    }

    /**
     * Remove all comments from this post
     *
     * @return void
     */
    public function removeAllComments()
    {
        $comments = clone $this->comments;
        $this->comments->removeAll($comments);
    }

    /**
     * Returns the comments to this post
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage holding instances of Comment
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Setter for the related posts
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $relatedPosts An Object Storage containing related Posts instances
     * @return void
     */
    public function setRelatedPosts(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $relatedPosts)
    {
        $this->relatedPosts = $relatedPosts;
    }

    /**
     * Adds a related post
     *
     * @param Post $post
     * @return void
     */
    public function addRelatedPost(Post $post)
    {
        $this->relatedPosts->attach($post);
    }

    /**
     * Remove all related posts
     *
     * @return void
     */
    public function removeAllRelatedPosts()
    {
        $relatedPosts = clone $this->relatedPosts;
        $this->relatedPosts->removeAll($relatedPosts);
    }

    /**
     * Returns the related posts
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage holding instances of Post
     */
    public function getRelatedPosts()
    {
        return $this->relatedPosts;
    }

    /**
     * Returns this post as a formatted string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->title . chr(10) .
            ' written on ' . $this->date->format('Y-m-d') . chr(10) .
            ' by ' . $this->author->getFullName() . chr(10) .
            wordwrap($this->content, 70, chr(10)) . chr(10) .
            implode(', ', $this->tags->toArray());
    }
}
