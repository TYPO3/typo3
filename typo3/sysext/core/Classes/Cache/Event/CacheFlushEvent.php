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

namespace TYPO3\CMS\Core\Cache\Event;

/**
 * Event fired when caches are to be cleared
 */
final class CacheFlushEvent
{
    private array $groups = [];
    private array $errors = [];

    public function __construct(array $groups)
    {
        $this->groups = $groups;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function hasGroup(string $group): bool
    {
        return in_array($group, $this->groups, true);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }
}
