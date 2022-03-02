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

/**
 * Facade of the Doctrine DBAL CompositeExpression to have
 * all Query related classes with in TYPO3\CMS namespace.
 */
class CompositeExpression extends \Doctrine\DBAL\Query\Expression\CompositeExpression
{
    /**
     * Each expression part of the composite expression.
     *
     * @var self[]|string[]
     */
    private array $parts = [];

    /**
     * The instance type of composite expression.
     */
    private string $type;

    /**
     * @param string $type
     * @param string[]|self[] $parts
     * @internal Use the and() / or() factory methods.
     */
    public function __construct($type, array $parts = [])
    {
        // pass empty parent to parent constructor as we have borrowed nearly all
        // method to this level because of their private visibility nature.
        parent::__construct((string)$type, []);
        $this->type = (string)$type;
        $this->addMultiple($parts);
    }

    /**
     * Adds an expression to composite expression.
     *
     * @param mixed $part
     * @return self
     */
    public function add($part): self
    {
        // Due to a bug in Doctrine DBAL, we must add our own check here,
        // which we luckily can, as we use a subclass anyway.
        // @see https://github.com/doctrine/dbal/issues/2388
        $isEmpty = $part instanceof self ? $part->count() === 0 : empty($part);
        if (!$isEmpty) {
            $this->parts[] = $part;
        }

        return $this;
    }

    /**
     * Adds multiple parts to composite expression.
     *
     * @param string[]|self[] $parts
     * @return self
     */
    public function addMultiple(array $parts = []): self
    {
        foreach ($parts as $part) {
            // Due to a bug in Doctrine DBAL, we must add our own check here,
            // which we luckily can, as we use a subclass anyway.
            // @see https://github.com/doctrine/dbal/issues/2388
            $isEmpty = $part instanceof self ? $part->count() === 0 : empty($part);
            if (!$isEmpty) {
                $this->parts[] = $part;
            }
        }

        return $this;
    }

    /**
     * @param self|string|null $part
     * @param self|string|null ...$parts
     */
    public static function and($part=null, ...$parts): self
    {
        $mergedParts = array_merge([$part], $parts);
        array_filter($mergedParts, static fn ($value) => !is_null($value));
        return (new self(self::TYPE_AND, []))->with(...$mergedParts);
    }

    /**
     * @param self|string|null $part
     * @param self|string|null ...$parts
     */
    public static function or($part=null, ...$parts): self
    {
        $mergedParts = array_merge([$part], $parts);
        array_filter($mergedParts, static fn ($value) => !is_null($value));
        return (new self(self::TYPE_OR, []))->with(...$mergedParts);
    }

    /**
     * Returns a new CompositeExpression with the given parts added.
     *
     * @param self|string|null $part
     * @param self|string|null ...$parts
     */
    public function with($part=null, ...$parts): self
    {
        $mergedParts = array_merge([$part], $parts);
        $that = clone $this;
        foreach ($mergedParts as $singlePart) {
            // Due to a bug in Doctrine DBAL, we must add our own check here,
            // which we luckily can, as we use a subclass anyway.
            // @see https://github.com/doctrine/dbal/issues/2388
            $isEmpty = $singlePart instanceof self ? $singlePart->count() === 0 : empty($singlePart);
            if (!$isEmpty) {
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
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Retrieves the string representation of this composite expression.
     * If expression is empty, just return an empty string.
     * Native Doctrine expression would return () instead.
     */
    public function __toString(): string
    {
        if ($this->count() === 0) {
            return '';
        }
        if ($this->count() === 1) {
            return (string)$this->parts[0];
        }
        return '(' . implode(') ' . $this->type . ' (', $this->parts) . ')';
    }
}
