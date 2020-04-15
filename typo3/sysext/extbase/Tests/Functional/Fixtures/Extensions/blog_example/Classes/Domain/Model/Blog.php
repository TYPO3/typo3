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
 * A blog
 */
class Blog extends AbstractEntity
{
    /**
     * The blog's title.
     *
     * @var string
     * @Extbase\Validate("StringLength", options={"minimum": 1, "maximum": 80})
     */
    protected $title = '';

    /**
     * The blog's subtitle
     *
     * @var string
     */
    protected $subtitle;

    /**
     * A short description of the blog
     *
     * @var string
     * @Extbase\Validate("StringLength", options={"maximum": 150})
     */
    protected $description = '';

    /**
     * A relative path to a logo image
     *
     * @var string
     */
    protected $logo = '';

    /**
     * The posts of this blog
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\ExtbaseTeam\BlogExample\Domain\Model\Post>
     * @Extbase\ORM\Lazy
     * @Extbase\ORM\Cascade("remove")
     */
    protected $posts;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>
     */
    protected $categories;

    /**
     * The blog's administrator
     *
     * @var \ExtbaseTeam\BlogExample\Domain\Model\Administrator
     * @Extbase\ORM\Lazy
     */
    protected $administrator;

    /**
     * Constructs a new Blog
     */
    public function __construct()
    {
        $this->posts = new ObjectStorage();
        $this->categories = new ObjectStorage();
    }

    /**
     * @return string
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Sets this blog's title
     *
     * @param string $title The blog's title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the blog's title
     *
     * @return string The blog's title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $logo
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Sets the description for the blog
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Returns the description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Adds a post to this blog
     *
     * @param Post $post
     */
    public function addPost(Post $post)
    {
        $this->posts->attach($post);
    }

    /**
     * Remove a post from this blog
     *
     * @param Post $postToRemove The post to be removed
     */
    public function removePost(Post $postToRemove)
    {
        $this->posts->detach($postToRemove);
    }

    /**
     * Remove all posts from this blog
     */
    public function removeAllPosts()
    {
        $this->posts = new ObjectStorage();
    }

    /**
     * Returns all posts in this blog
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * Add category to a blog
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
     * Remove category from blog
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\Category $category
     */
    public function removeCategory(Category $category)
    {
        $this->categories->detach($category);
    }

    /**
     * Sets the administrator value
     *
     * @param Administrator $administrator The Administrator of this Blog
     */
    public function setAdministrator(Administrator $administrator)
    {
        $this->administrator = $administrator;
    }

    /**
     * Returns the administrator value
     *
     * @return Administrator
     */
    public function getAdministrator()
    {
        return $this->administrator;
    }

    /**
     * @param ?string $subtitle
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }
}
