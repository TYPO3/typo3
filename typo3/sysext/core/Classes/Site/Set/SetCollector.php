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

namespace TYPO3\CMS\Core\Site\Set;

/**
 * @internal
 */
class SetCollector
{
    /** @var array<string, SetDefinition> */
    protected array $sets = [];

    /** @var array<string, array{ error: SetError, name: string, context: string }> */
    protected array $invalidSets = [];

    /**
     * @return array<string, SetDefinition>
     */
    public function getSetDefinitions(): array
    {
        return $this->sets;
    }

    /**
     * @return array<string, array{ error: SetError, name: string, context: string }>
     */
    public function getInvalidSets(): array
    {
        return $this->invalidSets;
    }

    public function add(SetDefinition $set): void
    {
        $this->sets[$set->name] = $set;
    }

    public function addError(SetError $error, string $name, string $context): void
    {
        $this->invalidSets[$name] = [
            'error' => $error,
            'name' => $name,
            'context' => $context,
        ];
    }
}
