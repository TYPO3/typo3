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
     * @param list<self|DoctrineCompositeExpression|string|null> $parts
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
            $parts = array_filter($parts, static fn(CompositeExpression|DoctrineCompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
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
        $count = $this->count();
        if ($count === 0) {
            return '';
        }
        if ($count === 1) {
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
        return (new self(self::TYPE_AND, []))->with($part, ...$parts);
    }

    /**
     * @param self|string|null $part
     * @param self|string|null ...$parts
     */
    public static function or($part = null, ...$parts): self
    {
        return (new self(self::TYPE_OR, []))->with($part, ...$parts);
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
        $mergedParts = array_filter($mergedParts, static fn(CompositeExpression|DoctrineCompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        $that = clone $this;
        foreach ($mergedParts as $singlePart) {
            $that->parts[] = $singlePart;
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

    /**
     * Determine if a part is considerable empty.
     *
     * doctrine/dbal solved the issue to avoid empty parts by making it mandatory to avoid instantiating this
     * class without a part. As we allow this and handle empty parts later on, we apply the empty check here.
     * @see https://github.com/doctrine/dbal/issues/2388
     */
    private static function isEmptyPart(CompositeExpression|DoctrineCompositeExpression|string|null $value): bool
    {
        if ($value === null) {
            return true;
        }
        if (is_string($value)) {
            return trim($value, '() ') === '';
        }
        if ($value instanceof CompositeExpression) {
            // TYPO3 implementation filters empty parts on setting and count is reliable in that case.
            return $value->parts === [];
        }
        // We need to use the count method, because the property is private in Doctrine and cannot be checked
        // against an empty array like it can be done for the own instance. Using Reflection would negate the
        // benefit. That's life.
        if ($value->count() === 0) {
            // Note that this should not be possible with plain Doctrine DBAL
            // composite expression,  still lets ensure a fallback here.
            return true;
        }
        // Doctrine DBAL CompositeExpression does not filter empty parts, so we need to build the string to
        // evaluate if it is empty or not, which comes with some performance impact hitting only when TYPO3
        // extension authors are using the Doctrine Composite Expression instead of the TYPO3 variant.
        return trim((string)$value, '() ') === '';
    }
}
