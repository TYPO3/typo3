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
 * A collection of labels and attachments - a record with relations of several types
 */
class Collection extends AbstractEntity
{
    protected string $title = '';

    /**
     * MM relation, the foreign side is Label::$collections
     *
     * @var ObjectStorage<Label>
     */
    protected ObjectStorage $labels;

    /**
     * Group field without relation table, the uids are stored as CSV value
     *
     * @var ObjectStorage<Label>
     */
    protected ObjectStorage $refs;

    /**
     * Inline field, told apart from the documents by `parent_field`
     *
     * @var ObjectStorage<Attachment>
     */
    protected ObjectStorage $galleryAttachments;

    /**
     * Inline field, told apart from the gallery by `parent_field`
     *
     * @var ObjectStorage<Attachment>
     */
    protected ObjectStorage $documentAttachments;

    public function __construct()
    {
        $this->labels = new ObjectStorage();
        $this->refs = new ObjectStorage();
        $this->galleryAttachments = new ObjectStorage();
        $this->documentAttachments = new ObjectStorage();
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
     * @return ObjectStorage<Label>
     */
    public function getLabels(): ObjectStorage
    {
        return $this->labels;
    }

    /**
     * @return ObjectStorage<Label>
     */
    public function getRefs(): ObjectStorage
    {
        return $this->refs;
    }

    /**
     * @return ObjectStorage<Attachment>
     */
    public function getGalleryAttachments(): ObjectStorage
    {
        return $this->galleryAttachments;
    }

    /**
     * @return ObjectStorage<Attachment>
     */
    public function getDocumentAttachments(): ObjectStorage
    {
        return $this->documentAttachments;
    }
}
