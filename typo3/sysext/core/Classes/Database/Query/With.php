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

final class With implements \Stringable
{
    /**
     * @param string[] $fields
     * @param string[] $dependencies
     */
    public function __construct(
        private readonly string $name,
        private readonly array $fields,
        private readonly array $dependencies,
        private readonly string|ConcreteQueryBuilder|QueryBuilder $expression,
        private readonly bool $recursive,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function isRecursive(): bool
    {
        return $this->recursive;
    }

    /** @return string[] */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function getSQL(): string
    {
        $fields = '';

        if ($this->fields !== []) {
            $fields = sprintf(' (%s)', implode(', ', $this->fields));
        }

        return sprintf(
            '%s%s AS (%s)',
            $this->getName(),
            $fields,
            $this->expression,
        );
    }

    public function __toString(): string
    {
        return $this->getSQL();
    }
}
