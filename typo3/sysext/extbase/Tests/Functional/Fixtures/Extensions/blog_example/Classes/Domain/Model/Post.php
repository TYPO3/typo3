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

namespace TYPO3Tests\BlogExample\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A blog post
 */
class Post extends AbstractEntity
{
    protected ?Blog $blog = null;

    #[Validate(['validator' => 'StringLength', 'options' => ['minimum' => 3, 'maximum' => 50]])]
    protected string $title = '';

    protected \DateTime $date;

    protected ?\DateTime $archiveDate = null;

    protected ?Person $author = null;

    protected ?Person $secondAuthor = null;

    protected ?Person $reviewer = null;

    #[Validate(['validator' => 'StringLength', 'options' => ['minimum' => 3]])]
    protected string $content = '';

    /**
     * @var ObjectStorage<Tag>
     */
    protected ObjectStorage $tags;

    /**
     * @var ObjectStorage<Category>
     */
    protected ObjectStorage $categories;

    /**
     * @var ObjectStorage<Comment>
     */
    #[Lazy]
    #[Cascade(['value' => 'remove'])]
    protected ObjectStorage $comments;

    /**
     * @var ObjectStorage<Post>
     */
    #[Lazy]
    protected ObjectStorage $relatedPosts;

    /**
     * 1:1 relation stored as CSV value in this class
     */
    protected ?Info $additionalName = null;

    /**
     * 1:1 relation stored as foreign key in Info class
     */
    protected ?Info $additionalInfo = null;

    /**
     * 1:n relation stored as CSV value
     * @var ObjectStorage<Comment>
     */
    #[Lazy]
    protected ObjectStorage $additionalComments;

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
     */
    public function setBlog(Blog $blog): void
    {
        $this->blog = $blog;
    }

    /**
     * Returns the blog this post is part of
     */
    public function getBlog(): ?Blog
    {
        return $this->blog;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getArchiveDate(): ?\DateTime
    {
        return $this->archiveDate;
    }

    public function setArchiveDate(?\DateTime $archiveDate): void
    {
        $this->archiveDate = $archiveDate;
    }

    /**
     * @param ObjectStorage<Tag> $tags
     */
    public function setTags(ObjectStorage $tags): void
    {
        $this->tags = $tags;
    }

    public function addTag(Tag $tag): void
    {
        $this->tags->attach($tag);
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags->detach($tag);
    }

    public function removeAllTags(): void
    {
        $this->tags = new ObjectStorage();
    }

    /**
     * Getter for tags
     * Note: We return a clone of the tags because they must not be modified as they are Value Objects
     *
     * @return ObjectStorage<Tag> A storage holding objects
     */
    public function getTags(): ObjectStorage
    {
        return clone $this->tags;
    }

    public function addCategory(Category $category): void
    {
        $this->categories->attach($category);
    }

    /**
     * @param ObjectStorage<Category> $categories
     */
    public function setCategories(ObjectStorage $categories): void
    {
        $this->categories = $categories;
    }

    /**
     * @return ObjectStorage<Category>
     */
    public function getCategories(): ObjectStorage
    {
        return $this->categories;
    }

    public function removeCategory(Category $category): void
    {
        $this->categories->detach($category);
    }

    public function setAuthor(?Person $author): void
    {
        $this->author = $author;
    }

    public function getAuthor(): ?Person
    {
        return $this->author;
    }

    public function getSecondAuthor(): ?Person
    {
        return $this->secondAuthor;
    }

    public function setSecondAuthor(?Person $secondAuthor): void
    {
        $this->secondAuthor = $secondAuthor;
    }

    public function getReviewer(): ?Person
    {
        return $this->reviewer;
    }

    public function setReviewer(?Person $reviewer): void
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
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Setter for the comments to this post
     *
     * @param ObjectStorage<Comment> $comments An Object Storage of related Comment instances
     */
    public function setComments(ObjectStorage $comments): void
    {
        $this->comments = $comments;
    }

    /**
     * Adds a comment to this post
     */
    public function addComment(Comment $comment): void
    {
        $this->comments->attach($comment);
    }

    /**
     * Removes Comment from this post
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
     * @return ObjectStorage<Comment> holding instances of Comment
     */
    public function getComments(): ObjectStorage
    {
        return $this->comments;
    }

    /**
     * Setter for the related posts
     *
     * @param ObjectStorage<Post> $relatedPosts An Object Storage containing related Posts instances
     */
    public function setRelatedPosts(ObjectStorage $relatedPosts): void
    {
        $this->relatedPosts = $relatedPosts;
    }

    /**
     * Adds a related post
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
     * @return ObjectStorage<Post> holding instances of Post
     */
    public function getRelatedPosts(): ObjectStorage
    {
        return $this->relatedPosts;
    }

    public function getAdditionalName(): ?Info
    {
        return $this->additionalName;
    }

    public function setAdditionalName(Info $additionalName): void
    {
        $this->additionalName = $additionalName;
    }

    public function getAdditionalInfo(): ?Info
    {
        return $this->additionalInfo;
    }

    public function setAdditionalInfo(Info $additionalInfo): void
    {
        $this->additionalInfo = $additionalInfo;
    }

    /**
     * @return ObjectStorage<Comment>
     */
    public function getAdditionalComments(): ObjectStorage
    {
        return $this->additionalComments;
    }

    /**
     * @param ObjectStorage<Comment> $additionalComments
     */
    public function setAdditionalComments(ObjectStorage $additionalComments): void
    {
        $this->additionalComments = $additionalComments;
    }

    public function addAdditionalComment(Comment $comment): void
    {
        $this->additionalComments->attach($comment);
    }

    public function removeAllAdditionalComments(): void
    {
        $comments = clone $this->additionalComments;
        $this->additionalComments->removeAll($comments);
    }

    public function removeAdditionalComment(Comment $comment): void
    {
        $this->additionalComments->detach($comment);
    }

    /**
     * Returns this post as a formatted string
     */
    public function __toString(): string
    {
        return $this->title . chr(10) .
            ' written on ' . $this->date->format('Y-m-d') . chr(10) .
            ' by ' . $this->author->getFullName() . chr(10) .
            wordwrap($this->content, 70, chr(10)) . chr(10) .
            implode(', ', $this->tags->toArray());
    }
}
