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

use TYPO3\CMS\Extbase\Attribute\ORM\Cascade;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A blog whose posts can only be written as a whole through `setPosts()`: unlike `Blog` it has neither `addPost()` nor
 * `removePost()`, so property mapping replaces the storage instead of adjusting it. The posts are loaded eagerly, so
 * the storage has a clean state when it is replaced. The model is mapped to the blog table.
 */
class BlogWithSetterOnlyPosts extends AbstractEntity
{
    protected string $title = '';

    /**
     * @var ObjectStorage<Post>
     */
    #[Cascade('remove')]
    protected ObjectStorage $posts;

    public function __construct()
    {
        $this->posts = new ObjectStorage();
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
     * @param ObjectStorage<Post> $posts
     */
    public function setPosts(ObjectStorage $posts): void
    {
        $this->posts = $posts;
    }

    /**
     * @return ObjectStorage<Post>
     */
    public function getPosts(): ObjectStorage
    {
        return $this->posts;
    }
}
