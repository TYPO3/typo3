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

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A blog post with relations that no other test of the blog example uses: tags in a select and a group field without
 * relation table, and two inline fields that share the comment table. The model is mapped to the post table, so
 * posts are written through `Post` and only read through this class.
 */
class PostWithRelations extends Post
{
    /**
     * @var ObjectStorage<Tag>
     */
    protected ObjectStorage $pickedTags;

    /**
     * @var ObjectStorage<Tag>
     */
    protected ObjectStorage $referencedTags;

    /**
     * @var ObjectStorage<Comment>
     */
    protected ObjectStorage $pinnedComments;

    /**
     * @var ObjectStorage<Comment>
     */
    protected ObjectStorage $highlightedComments;

    public function __construct()
    {
        parent::__construct();
        $this->pickedTags = new ObjectStorage();
        $this->referencedTags = new ObjectStorage();
        $this->pinnedComments = new ObjectStorage();
        $this->highlightedComments = new ObjectStorage();
    }

    /**
     * @return ObjectStorage<Tag>
     */
    public function getPickedTags(): ObjectStorage
    {
        return $this->pickedTags;
    }

    /**
     * @return ObjectStorage<Tag>
     */
    public function getReferencedTags(): ObjectStorage
    {
        return $this->referencedTags;
    }

    /**
     * @return ObjectStorage<Comment>
     */
    public function getPinnedComments(): ObjectStorage
    {
        return $this->pinnedComments;
    }

    /**
     * @return ObjectStorage<Comment>
     */
    public function getHighlightedComments(): ObjectStorage
    {
        return $this->highlightedComments;
    }
}
