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

namespace TYPO3\CMS\Beuser\Domain\Model;

use TYPO3\CMS\Extbase\Attribute as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Model for backend user group
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendUserGroup extends AbstractEntity
{
    protected string $title = '';
    protected string $description = '';
    protected bool $hidden = false;

    /**
     * @var ObjectStorage<BackendUserGroup>
     */
    #[Extbase\ORM\Lazy]
    protected ObjectStorage $subGroups;

    public function __construct()
    {
        $this->initializeObject();
    }

    public function initializeObject(): void
    {
        $this->subGroups = new ObjectStorage();
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    public function setSubGroups(ObjectStorage $subGroups): void
    {
        $this->subGroups = $subGroups;
    }

    public function getSubGroups(): ObjectStorage
    {
        return $this->subGroups;
    }
}
