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

namespace ExtbaseTeam\BlogExample\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A blog post
 */
class Post extends AbstractEntity
{
    /**
     * @var \ExtbaseTeam\BlogExample\Domain\Model\Blog
     */
    protected $blog;

    /**
     * @var string
     * @Extbase\Validate("StringLength", options={"minimum": 3, "maximum": 50})
     */
    protected $title = '';

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var \ExtbaseTeam\BlogExample\Domain\Model\Person
     */
    protected $author;

    /**
     * @var \ExtbaseTeam\BlogExample\Domain\Model\Person
     */
    protected $secondAuthor;

    /**
     * @var \ExtbaseTeam\BlogExample\Domain\Model\Person
     */
    protected $reviewer;

    /**
     * @var string
     * @Extbase\Validate("StringLength", options={"minimum": 3})
     */
    protected $content = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\ExtbaseTeam\BlogExample\Domain\Model\Tag>
     */
    protected $tags;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>
     */
    protected $categories;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\ExtbaseTeam\BlogExample\Domain\Model\Comment>
     * @Extbase\ORM\Lazy
     * @Extbase\ORM\Cascade("remove")
     */
    protected $comments;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\ExtbaseTeam\BlogExample\Domain\Model\Post>
     * @Extbase\ORM\Lazy
     */
    protected $relatedPosts;

    /**
     * 1:1 relation stored as CSV value in this class
     * @var \ExtbaseTeam\BlogExample\Domain\Model\Info
     */
    protected $additionalName;

    /**
     * 1:1 relation stored as foreign key in Info class
     * @var \ExtbaseTeam\BlogExample\Domain\Model\Info
     */
    protected $additionalInfo;

    /**
     * 1:n relation stored as CSV value
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\ExtbaseTeam\BlogExample\Domain\Model\Comment>
     * @Extbase\ORM\Lazy
     */
    protected $additionalComments;

    /**
     * Constructs this post
     */
    public function __construct()
    {
        $this->tags = new ObjectStorage();
        $this->categories = new ObjectStorage();
        $this->comments = new ObjectStorage();
        $this->relatedPosts = new ObjectStorage();
        $this->date = new \DateTime();
        $this->additionalComments = new ObjectStorage();
    }

    /**
     * Sets the blog this post is part of
     *
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Blog $blog The blog
     */
    public function setBlog(Blog $blog)
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
     */
    public function setTags(ObjectStorage $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Adds a tag to this post
     *
     * @param Tag $tag
     */
    public function addTag(Tag $tag)
    {
        $this->tags->attach($tag);
    }

    /**
     * Removes a tag from this post
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->detach($tag);
    }

    /**
     * Remove all tags from this post
     */
    public function removeAllTags()
    {
        $this->tags = new ObjectStorage();
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
    public function addCategory(Category $category)
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
    public function removeCategory(Category $category)
    {
        $this->categories->detach($category);
    }

    /**
     * Sets the author for this post
     *
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Person $author
     */
    public function setAuthor(Person $author)
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
     * @return \ExtbaseTeam\BlogExample\Domain\Model\Person
     */
    public function getSecondAuthor(): ?Person
    {
        return $this->secondAuthor;
    }

    /**
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Person $secondAuthor
     */
    public function setSecondAuthor(Person $secondAuthor): void
    {
        $this->secondAuthor = $secondAuthor;
    }

    /**
     * @return \ExtbaseTeam\BlogExample\Domain\Model\Person
     */
    public function getReviewer()
    {
        return $this->reviewer;
    }

    /**
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Person $reviewer
     */
    public function setReviewer(Person $reviewer)
    {
        $this->reviewer = $reviewer;
    }

    /**
     * Sets the content for this post
     *
     * @param string $content
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
     */
    public function setComments(ObjectStorage $comments)
    {
        $this->comments = $comments;
    }

    /**
     * Adds a comment to this post
     *
     * @param Comment $comment
     */
    public function addComment(Comment $comment)
    {
        $this->comments->attach($comment);
    }

    /**
     * Removes Comment from this post
     *
     * @param Comment $commentToDelete
     */
    public function removeComment(Comment $commentToDelete)
    {
        $this->comments->detach($commentToDelete);
    }

    /**
     * Remove all comments from this post
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
     */
    public function setRelatedPosts(ObjectStorage $relatedPosts)
    {
        $this->relatedPosts = $relatedPosts;
    }

    /**
     * Adds a related post
     *
     * @param Post $post
     */
    public function addRelatedPost(Post $post)
    {
        $this->relatedPosts->attach($post);
    }

    /**
     * Remove all related posts
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
     * @return ?Info
     */
    public function getAdditionalName(): ?Info
    {
        return $this->additionalName;
    }

    /**
     * @param Info $additionalName
     */
    public function setAdditionalName(Info $additionalName): void
    {
        $this->additionalName = $additionalName;
    }

    /**
     * @return ?Info
     */
    public function getAdditionalInfo(): ?Info
    {
        return $this->additionalInfo;
    }

    /**
     * @param Info $additionalInfo
     */
    public function setAdditionalInfo(Info $additionalInfo): void
    {
        $this->additionalInfo = $additionalInfo;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getAdditionalComments(): ObjectStorage
    {
        return $this->additionalComments;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $additionalComments
     */
    public function setAdditionalComments(ObjectStorage $additionalComments): void
    {
        $this->additionalComments = $additionalComments;
    }

    /**
     * @param Comment $comment
     */
    public function addAdditionalComment(Comment $comment)
    {
        $this->additionalComments->attach($comment);
    }

    /**
     * Remove all additional Comments
     */
    public function removeAllAdditionalComments()
    {
        $comments = clone $this->additionalComments;
        $this->additionalComments->removeAll($comments);
    }

    /**
     * @param Comment $comment
     */
    public function removeAdditionalComment(Comment $comment)
    {
        $this->additionalComments->detach($comment);
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
