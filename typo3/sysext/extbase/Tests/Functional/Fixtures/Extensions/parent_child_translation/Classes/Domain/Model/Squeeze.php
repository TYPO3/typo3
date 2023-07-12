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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Squeeze extends AbstractEntity
{
    /**
     * title
     */
    protected string $title = '';

    /**
     * child
     */
    protected Child|null $child = null;

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
}
