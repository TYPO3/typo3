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

namespace TYPO3\CMS\Extbase\Tests\Fixture;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * An entity
 */
class Entity extends AbstractEntity
{
    protected string $name;

    /**
     * Constructs this entity
     *
     * @param string $name Name of this blog
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * Sets this entity's name
     *
     * @param string $name The entity's name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns the entity's name
     *
     * @return string The entity's name
     */
    public function getName(): string
    {
        return $this->name;
    }
}
