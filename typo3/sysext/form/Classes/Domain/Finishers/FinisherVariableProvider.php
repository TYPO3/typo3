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

namespace TYPO3\CMS\Form\Domain\Finishers;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;

/**
 * Store data for usage between the finishers.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 * @internal
 */
final class FinisherVariableProvider implements \ArrayAccess, \IteratorAggregate, \Countable
{

    /**
     * Two-dimensional object array storing the values. The first dimension is the finisher identifier,
     * and the second dimension is the identifier for the data the finisher wants to store.
     *
     * @var array
     */
    protected $objects = [];

    /**
     * Add a variable to the finisher container.
     *
     * @param string $finisherIdentifier
     * @param string $key
     * @param mixed $value
     */
    public function add(string $finisherIdentifier, string $key, $value)
    {
        $this->addOrUpdate($finisherIdentifier, $key, $value);
    }

    /**
     * Add a variable to the Variable Container.
     * In case the value is already inside, it is silently overridden.
     *
     * @param string $finisherIdentifier
     * @param string $key
     * @param mixed $value
     */
    public function addOrUpdate(string $finisherIdentifier, string $key, $value)
    {
        if (!array_key_exists($finisherIdentifier, $this->objects)) {
            $this->objects[$finisherIdentifier] = [];
        }
        $this->objects[$finisherIdentifier] = ArrayUtility::setValueByPath(
            $this->objects[$finisherIdentifier],
            $key,
            $value,
            '.'
        );
    }

    /**
     * Gets a variable which is stored
     *
     * @param string $finisherIdentifier
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $finisherIdentifier, string $key, $default = null)
    {
        if ($this->exists($finisherIdentifier, $key)) {
            return ArrayUtility::getValueByPath($this->objects[$finisherIdentifier], $key, '.');
        }
        return $default;
    }

    /**
     * Determine whether there is a variable stored for the given key
     *
     * @param string $finisherIdentifier
     * @param string $key
     * @return bool
     */
    public function exists($finisherIdentifier, $key): bool
    {
        try {
            ArrayUtility::getValueByPath($this->objects[$finisherIdentifier] ?? [], $key, '.');
        } catch (MissingArrayPathException $e) {
            return false;
        }
        return true;
    }

    /**
     * Remove a value from the variable container
     *
     * @param string $finisherIdentifier
     * @param string $key
     */
    public function remove(string $finisherIdentifier, string $key)
    {
        if ($this->exists($finisherIdentifier, $key)) {
            $this->objects[$finisherIdentifier] = ArrayUtility::removeByPath(
                $this->objects[$finisherIdentifier],
                $key,
                '.'
            );
        }
    }

    /**
     * Clean up for serializing.
     *
     * @return array
     */
    public function __sleep()
    {
        return ['objects'];
    }

    /**
     * Whether an offset exists
     *
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset An offset to check for.
     * @return bool TRUE on success or FALSE on failure.
     * @todo Set $offset to mixed type in v12.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->objects[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     * @todo Set $offset to mixed type in v12.
     * @todo Set return type to ?mixed in v12.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->objects[$offset];
    }

    /**
     * Offset to set
     *
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @todo Set $offset and $value to mixed type in v12.
     */
    public function offsetSet($offset, $value): void
    {
        $this->objects[$offset] = $value;
    }

    /**
     * Offset to unset
     *
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset.
     * @todo Set $offset to mixed type in v12.
     */
    public function offsetUnset($offset): void
    {
        unset($this->objects[$offset]);
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->objects as $offset => $value) {
            yield $offset => $value;
        }
    }

    /**
     * Count elements of an object
     *
     * @link https://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     */
    public function count(): int
    {
        return count($this->objects);
    }
}
