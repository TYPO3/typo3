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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A bookmark - a record without delete column
 */
class Bookmark extends AbstractEntity
{
    protected string $title = '';

    /**
     * @var ObjectStorage<Tag>
     */
    protected ObjectStorage $tags;

    /**
     * @var ObjectStorage<Post>
     */
    protected ObjectStorage $posts;

    public function __construct()
    {
        $this->tags = new ObjectStorage();
        $this->posts = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return ObjectStorage<Tag>
     */
    public function getTags(): ObjectStorage
    {
        return $this->tags;
    }

    /**
     * @return ObjectStorage<Post>
     */
    public function getPosts(): ObjectStorage
    {
        return $this->posts;
    }

    public function removePost(Post $post): void
    {
        $this->posts->detach($post);
    }
}
