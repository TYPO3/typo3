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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Fixtures;

use TYPO3\CMS\Core\Resource\AbstractFile;

/**
 * Testing subclass of `AbstractFile`.
 */
final class TestingFile extends AbstractFile
{
    private string $identifier;

    public function updateProperties(array $properties): void
    {
        // stub
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function isIndexed(): bool
    {
        throw new \BadMethodCallException('Not implemented', 1691576988);
    }

    public function toArray(): array
    {
        throw new \BadMethodCallException('Not implemented', 1691580005);
    }
}
