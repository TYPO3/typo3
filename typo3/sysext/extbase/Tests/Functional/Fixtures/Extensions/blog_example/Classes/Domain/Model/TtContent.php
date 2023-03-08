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
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A tt_content model
 */
class TtContent extends AbstractEntity
{
    /**
     * @var int<1, max>|null
     */
    protected $uid;

    /**
     * @var int<0, max>|null
     */
    protected $pid;

    protected string $header;

    /**
     * @var ObjectStorage<FileReference>
     * @Extbase\ORM\Lazy
     */
    protected ObjectStorage $image;

    /**
     * @var ObjectStorage<Category>
     */
    protected ObjectStorage $categories;

    public function __construct()
    {
        $this->image = new ObjectStorage();
        $this->categories = new ObjectStorage();
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function setHeader(string $header): void
    {
        $this->header = $header;
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getImage(): ObjectStorage
    {
        return $this->image;
    }

    /**
     * @param ObjectStorage<FileReference> $image
     */
    public function setImage(ObjectStorage $image): void
    {
        $this->image = $image;
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

    /**
     * Returns this as a formatted string
     */
    public function __toString(): string
    {
        return $this->getHeader();
    }
}
