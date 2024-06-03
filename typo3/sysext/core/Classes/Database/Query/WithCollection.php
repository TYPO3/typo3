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

namespace TYPO3\CMS\Core\Database\Query;

use TYPO3\CMS\Core\Service\DependencyOrderingService;

/**
 * @internal experimental and not part of public core API.
 */
final class WithCollection implements \Stringable
{
    /**
     * @var With[]
     */
    private array $with = [];
    private bool $recursive = false;

    public function set(With ...$with): WithCollection
    {
        return $this->reset()->add(...array_values($with));
    }

    public function add(With ...$with): WithCollection
    {
        foreach ($with as $singleWith) {
            $this->with[] = $singleWith;
            if ($singleWith->isRecursive()) {
                $this->recursive = true;
            }
        }
        return $this;
    }

    public function reset(): WithCollection
    {
        $this->recursive = false;
        $this->with = [];
        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->with === [];
    }

    public function __toString(): string
    {
        if ($this->with === []) {
            return '';
        }
        $parts = [];
        foreach ($this->getSortedParts() as $part) {
            $parts[] = (string)$part;
        }
        return sprintf(
            '%s %s',
            ($this->recursive ? 'WITH RECURSIVE' : 'WITH'),
            implode(', ', $parts)
        );
    }

    /**
     * @return With[]
     */
    private function getSortedParts(): array
    {
        $parts = [];
        foreach ($this->prepareParts() as $part) {
            $parts[] = $part['instance'];
        }
        return $parts;
    }

    /**
     * @return array<string, array{instance: With, before: string[], after: string[]}>
     */
    private function prepareParts(): array
    {
        $parts = [];
        foreach ($this->with as $part) {
            $parts[$part->getName()] = [
                'instance' => $part,
                'before' => [],
                'after' => $part->getDependencies(),
            ];
        }
        return (new DependencyOrderingService())->orderByDependencies($parts);
    }
}
