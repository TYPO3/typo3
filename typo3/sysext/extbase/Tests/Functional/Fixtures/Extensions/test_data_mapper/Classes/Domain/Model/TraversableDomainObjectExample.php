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

namespace TYPO3Tests\TestDataMapper\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class TraversableDomainObjectExample extends AbstractEntity implements \Iterator
{
    public function getUid(): ?int
    {
        return 1;
    }

    public function current(): mixed
    {
        return [];
    }

    public function next(): void
    {
    }

    public function key(): mixed
    {
        return 1;
    }

    public function valid(): bool
    {
        return true;
    }

    public function rewind(): void
    {
    }
}
