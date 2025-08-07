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

namespace TYPO3Tests\ParentChildTranslation\Domain\Model;

use TYPO3\CMS\Extbase\Attribute\ORM\Cascade;
use TYPO3\CMS\Extbase\Attribute\ORM\Lazy;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Main extends AbstractEntity
{
    /**
     * title
     */
    protected string $title = '';

    /**
     * child
     */
    protected ?Child $child = null;

    /**
     * squeeze
     *
     * @var ObjectStorage<Squeeze>
     */
    #[Cascade(['value' => 'remove'])]
    #[Lazy]
    protected ObjectStorage $squeeze;

    /**
     * __construct
     */
    public function __construct()
    {
        // Do not remove the next line: It would break the functionality
        $this->initializeObject();
    }

    /**
     * Initializes all ObjectStorage properties when model is reconstructed from DB (where __construct is not called)
     * Do not modify this method!
     * It will be rewritten on each save in the extension builder
     * You may modify the constructor of this class instead
     */
    public function initializeObject(): void
    {
        $this->squeeze = new ObjectStorage();
    }

    /**
     * Returns the title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Returns the child
     */
    public function getChild(): ?Child
    {
        return $this->child;
    }

    /**
     * Sets the child
     */
    public function setChild(Child $child): void
    {
        $this->child = $child;
    }

    /**
     * Adds a Squeeze
     */
    public function addSqueeze(Squeeze $squeeze): void
    {
        $this->squeeze->attach($squeeze);
    }

    /**
     * Removes a Squeeze
     */
    public function removeSqueeze(Squeeze $squeezeToRemove): void
    {
        $this->squeeze->detach($squeezeToRemove);
    }

    /**
     * Returns the squeeze
     *
     * @return ObjectStorage<Squeeze>
     */
    public function getSqueeze(): ObjectStorage
    {
        return $this->squeeze;
    }

    /**
     * Sets the squeeze
     *
     * @param ObjectStorage<Squeeze> $squeeze
     */
    public function setSqueeze(ObjectStorage $squeeze): void
    {
        $this->squeeze = $squeeze;
    }
}
