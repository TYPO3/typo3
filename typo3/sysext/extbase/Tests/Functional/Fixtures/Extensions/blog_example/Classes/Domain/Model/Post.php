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

namespace ExtbaseTeam\BlogExample\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
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
     * @var Person
     */
    protected $author;

    /**
     * @var Person
     */
    protected $secondAuthor;

    /**
     * @var Person
     */
    protected $reviewer;

    /**
     * @var string
     * @Extbase\Validate("StringLength", options={"minimum": 3})
     */
    protected $content = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<Tag>
     */
    protected $tags;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<Category>
     */
    protected $categories;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<Comment>
     * @Extbase\ORM\Lazy
     * @Extbase\ORM\Cascade("remove")
     */
    protected $comments;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<Post>
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
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<Comment>
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
    public function setBlog(Blog $blog): void
    {
        $this->blog = $blog;
    }

    /**
     * Returns the blog this post is part of
     *
     * @return \ExtbaseTeam\BlogExample\Domain\Model\Blog|null The blog this post is part of
     */
    public function getBlog(): ?Blog
    {
        return $this->blog;
    }

    /**
     * Setter for title
     *
     * @param string $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * Getter for title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Setter for date
     *
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * Getter for date
     *
     *
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Setter for tags
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $tags One or more Tag objects
     */
    public function setTags(ObjectStorage $tags): void
    {
        $this->tags = $tags;
    }

    /**
     * Adds a tag to this post
     *
     * @param Tag $tag
     */
    public function addTag(Tag $tag): void
    {
        $this->tags->attach($tag);
    }

    /**
     * Removes a tag from this post
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $tag): void
    {
        $this->tags->detach($tag);
    }

    /**
     * Remove all tags from this post
     */
    public function removeAllTags(): void
    {
        $this->tags = new ObjectStorage();
    }

    /**
     * Getter for tags
     * Note: We return a clone of the tags because they must not be modified as they are Value Objects
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage A storage holding objects
     */
    public function getTags(): ObjectStorage
    {
        return clone $this->tags;
    }

    /**
     * Add category to a post
     *
     * @param Category $category
     */
    public function addCategory(Category $category): void
    {
        $this->categories->attach($category);
    }

    /**
     * Set categories
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $categories
     */
    public function setCategories($categories): void
    {
        $this->categories = $categories;
    }

    /**
     * Get categories
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getCategories(): ObjectStorage
    {
        return $this->categories;
    }

    /**
     * Remove category from post
     *
     * @param Category $category
     */
    public function removeCategory(Category $category): void
    {
        $this->categories->detach($category);
    }

    /**
     * Sets the author for this post
     *
     * @param Person $author
     */
    public function setAuthor(Person $author): void
    {
        $this->author = $author;
    }

    /**
     * Getter for author
     *
     * @return Person|null
     */
    public function getAuthor(): ?Person
    {
        return $this->author;
    }

    /**
     * @return Person
     */
    public function getSecondAuthor(): ?Person
    {
        return $this->secondAuthor;
    }

    /**
     * @param Person $secondAuthor
     */
    public function setSecondAuthor(Person $secondAuthor): void
    {
        $this->secondAuthor = $secondAuthor;
    }

    /**
     * @return Person|null
     */
    public function getReviewer(): ?Person
    {
        return $this->reviewer;
    }

    /**
     * @param Person $reviewer
     */
    public function setReviewer(Person $reviewer): void
    {
        $this->reviewer = $reviewer;
    }

    /**
     * Sets the content for this post
     *
     * @param string $content
     */
    public function setContent($content): void
    {
        $this->content = $content;
    }

    /**
     * Getter for content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Setter for the comments to this post
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $comments An Object Storage of related Comment instances
     */
    public function setComments(ObjectStorage $comments): void
    {
        $this->comments = $comments;
    }

    /**
     * Adds a comment to this post
     *
     * @param Comment $comment
     */
    public function addComment(Comment $comment): void
    {
        $this->comments->attach($comment);
    }

    /**
     * Removes Comment from this post
     *
     * @param Comment $commentToDelete
     */
    public function removeComment(Comment $commentToDelete): void
    {
        $this->comments->detach($commentToDelete);
    }

    /**
     * Remove all comments from this post
     */
    public function removeAllComments(): void
    {
        $comments = clone $this->comments;
        $this->comments->removeAll($comments);
    }

    /**
     * Returns the comments to this post
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage holding instances of Comment
     */
    public function getComments(): ObjectStorage
    {
        return $this->comments;
    }

    /**
     * Setter for the related posts
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $relatedPosts An Object Storage containing related Posts instances
     */
    public function setRelatedPosts(ObjectStorage $relatedPosts): void
    {
        $this->relatedPosts = $relatedPosts;
    }

    /**
     * Adds a related post
     *
     * @param Post $post
     */
    public function addRelatedPost(Post $post): void
    {
        $this->relatedPosts->attach($post);
    }

    /**
     * Remove all related posts
     */
    public function removeAllRelatedPosts(): void
    {
        $relatedPosts = clone $this->relatedPosts;
        $this->relatedPosts->removeAll($relatedPosts);
    }

    /**
     * Returns the related posts
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage holding instances of Post
     */
    public function getRelatedPosts(): ObjectStorage
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
    public function addAdditionalComment(Comment $comment): void
    {
        $this->additionalComments->attach($comment);
    }

    /**
     * Remove all additional Comments
     */
    public function removeAllAdditionalComments(): void
    {
        $comments = clone $this->additionalComments;
        $this->additionalComments->removeAll($comments);
    }

    /**
     * @param Comment $comment
     */
    public function removeAdditionalComment(Comment $comment): void
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
