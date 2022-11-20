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

namespace TYPO3\CMS\Core\DataHandling\Model;

/**
 * The EntityUidPointer represents the concrete origin of the entity
 */
class EntityUidPointer implements EntityPointer
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $identifier;

    public function __construct(string $name, string $identifier)
    {
        $this->name = $name;
        $this->identifier = $identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return static
     */
    public function withUid(string $identifier): self
    {
        if ($this->identifier === $identifier) {
            return $this;
        }
        $target = clone $this;
        $target->identifier = $identifier;
        return $target;
    }

    public function isNode(): bool
    {
        return $this->name === 'pages';
    }

    public function isEqualTo(EntityPointer $other): bool
    {
        return $this->identifier === $other->getIdentifier()
            && $this->name === $other->getName();
    }
}
