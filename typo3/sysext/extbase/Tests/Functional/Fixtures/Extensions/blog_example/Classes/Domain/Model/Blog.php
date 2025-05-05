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
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A blog
 */
class Blog extends AbstractEntity
{
    /**
     * The blog's title.
     */
    #[Validate(['validator' => 'NotEmpty'])]
    #[Validate(['validator' => 'StringLength', 'options' => ['minimum' => 1, 'maximum' => 80]])]
    protected string $title = '';

    /**
     * The blog's subtitle
     */
    protected ?string $subtitle = null;

    /**
     * A short description of the blog
     */
    #[Validate(['validator' => 'StringLength', 'options' => ['minimum' => 1, 'maximum' => 150]])]
    protected string $description = '';

    /**
     * A logo
     *
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $logo;

    /**
     * The posts of this blog
     *
     * @var ObjectStorage<Post>
     */
    #[Lazy()]
    #[Cascade(['value' => 'remove'])]
    protected ObjectStorage $posts;

    /**
     * @var ObjectStorage<Category>
     */
    protected ObjectStorage $categories;

    /**
     * The blog's administrator
     */
    #[Lazy()]
    protected Administrator|LazyLoadingProxy|null $administrator = null;

    public function __construct()
    {
        $this->posts = new ObjectStorage();
        $this->categories = new ObjectStorage();
        $this->logo = new ObjectStorage();
    }

    public function setSubtitle(?string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param ObjectStorage<FileReference> $logo
     */
    public function setLogo(ObjectStorage $logo): void
    {
        $this->logo = $logo;
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getLogo(): ObjectStorage
    {
        return $this->logo;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function addPost(Post $post): void
    {
        $this->posts->attach($post);
    }

    public function removePost(Post $postToRemove): void
    {
        $this->posts->detach($postToRemove);
    }

    public function removeAllPosts(): void
    {
        $this->posts = new ObjectStorage();
    }

    /**
     * @return ObjectStorage<Post>
     */
    public function getPosts(): ObjectStorage
    {
        return $this->posts;
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

    public function setAdministrator(Administrator $administrator): void
    {
        $this->administrator = $administrator;
    }

    public function getAdministrator(): Administrator|LazyLoadingProxy|null
    {
        return $this->administrator;
    }
}
