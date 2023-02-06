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

namespace TYPO3\CMS\Core\Database\Query\Expression;

use Doctrine\DBAL\Query\Expression\CompositeExpression as DoctrineCompositeExpression;

/**
 * Facade of the Doctrine DBAL CompositeExpression to have
 * all Query related classes with in TYPO3\CMS namespace.
 */
class CompositeExpression extends DoctrineCompositeExpression
{
    /**
     * Each expression part of the composite expression.
     *
     * @var self[]|string[]
     */
    private array $parts;

    /**
     * The instance type of composite expression.
     */
    private string $type;

    private bool $isOuter;

    /**
     * @param string[]|self[] $parts
     * @internal Use factory methods `and()` or `or()` methods instead. Signature will change along with doctrine/dbal 4.
     */
    public function __construct(string $type, array $parts = [], bool $isOuter = false)
    {
        $this->isOuter = $isOuter;
        // parent::__construct() call is left out by intention. doctrine/dbal works with private properties, which
        // make it otherwise impossible to keep compat method signature and providing the features needed.
        $this->type = $type;
        if ($parts !== []) {
            // doctrine/dbal solved the issue to avoid empty parts by making it mandatory to avoid instantiating this
            // class without a part. As we allow this and handle empty parts later on, we apply the empty check here.
            // @see https://github.com/doctrine/dbal/issues/2388
            array_filter($parts, static fn(CompositeExpression|DoctrineCompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        }
        $this->parts = $parts;
    }

    /**
     * Retrieves the string representation of this composite expression.
     * If expression is empty, just return an empty string.
     * Native Doctrine expression would return () instead.
     */
    public function __toString(): string
    {
        $this->parts = array_filter($this->parts, static fn(CompositeExpression|DoctrineCompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        if ($this->count() === 0) {
            return '';
        }
        if ($this->count() === 1) {
            return (string)$this->parts[0];
        }
        if ($this->isOuter) {
            return '(' . implode(') ' . $this->type . ' (', $this->parts) . ')';
        }
        return '((' . implode(') ' . $this->type . ' (', $this->parts) . '))';
    }

    /**
     * @param self|string|null $part
     * @param self|string|null ...$parts
     */
    public static function and($part = null, ...$parts): self
    {
        $mergedParts = array_merge([$part], $parts);
        array_filter($mergedParts, static fn(CompositeExpression|DoctrineCompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        return (new self(self::TYPE_AND, []))->with(...$mergedParts);
    }

    /**
     * @param self|string|null $part
     * @param self|string|null ...$parts
     */
    public static function or($part = null, ...$parts): self
    {
        $mergedParts = array_merge([$part], $parts);
        array_filter($mergedParts, static fn(CompositeExpression|DoctrineCompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        return (new self(self::TYPE_OR, []))->with(...$mergedParts);
    }

    /**
     * Returns a new CompositeExpression with the given parts added.
     *
     * @param self|string|null $part
     * @param self|string|null ...$parts
     */
    public function with($part = null, ...$parts): self
    {
        $mergedParts = array_merge([$part], $parts);
        array_filter($mergedParts, static fn(CompositeExpression|DoctrineCompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        $that = clone $this;
        foreach ($mergedParts as $singlePart) {
            // Due to a bug in Doctrine DBAL, we must add our own check here,
            // which we luckily can, as we use a subclass anyway.
            // @see https://github.com/doctrine/dbal/issues/2388
            if (!self::isEmptyPart($singlePart)) {
                $that->parts[] = $singlePart;
            }
        }

        return $that;
    }

    /**
     * Retrieves the amount of expressions on composite expression.
     */
    public function count(): int
    {
        return count($this->parts);
    }

    /**
     * Returns the type of this composite expression (AND/OR).
     */
    public function getType(): string
    {
        return $this->type;
    }

    private static function isEmptyPart(CompositeExpression|DoctrineCompositeExpression|string|null $value): bool
    {
        return $value === null
            || ($value instanceof DoctrineCompositeExpression && $value->count() === 0)
            || trim((string)$value, '() ') === ''
        ;
    }
}
