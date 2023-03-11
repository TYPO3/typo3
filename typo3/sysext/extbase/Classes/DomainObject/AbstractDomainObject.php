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

namespace TYPO3\CMS\Extbase\DomainObject;

use TYPO3\CMS\Extbase\Persistence\Generic\Exception\TooDirtyException;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\ObjectMonitoringInterface;

/**
 * A generic Domain Object.
 *
 * All Model domain objects need to inherit from either AbstractEntity or AbstractValueObject, as this provides important framework information.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
abstract class AbstractDomainObject implements DomainObjectInterface
{
    public const PROPERTY_UID = 'uid';
    public const PROPERTY_PID = 'pid';
    public const PROPERTY_LOCALIZED_UID = '_localizedUid';
    public const PROPERTY_LANGUAGE_UID = '_languageUid';
    public const PROPERTY_VERSIONED_UID = '_versionedUid';

    /**
     * @var int<1, max>|null The uid of the record. The uid is only unique in the context of the database table.
     * @todo introduce type declarations in 13.0 (possibly breaking)
     */
    protected $uid;

    /**
     * @var int<0, max>|null The uid of the localized record. Holds the uid of the record in default language (the translationOrigin).
     *
     * @internal
     * @todo make private in 13.0 and expose value via getter
     */
    protected int|null $_localizedUid = null;

    /**
     * @var int<-1, max>|null The uid of the language of the object. This is the id of the corresponding sing language.
     *
     * @internal
     * @todo make private in 13.0 and expose value via getter
     */
    protected int|null $_languageUid = null;

    /**
     * The uid of the versioned record.
     *
     * @internal
     * @todo make private in 13.0 and expose value via getter
     */
    protected int|null $_versionedUid = null;

    /**
     * @var int<0, max>|null The id of the page the record is "stored".
     * @todo introduce type declarations in 13.0 (possibly breaking)
     */
    protected $pid;

    /**
     * TRUE if the object is a clone
     *
     * @internal
     */
    private bool $_isClone = false;

    /**
     * @var array<non-empty-string, mixed>
     *
     * @internal
     */
    private array $_cleanProperties = [];

    /**
     * @return int<1, max>|null
     */
    public function getUid(): int|null
    {
        if ($this->uid !== null) {
            return (int)$this->uid;
        }
        return null;
    }

    /**
     * @param int<0, max> $pid
     */
    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    /**
     * @return int<0, max>|null
     */
    public function getPid(): int|null
    {
        if ($this->pid === null) {
            return null;
        }
        return (int)$this->pid;
    }

    /**
     * @internal
     */
    public function _setProperty(string $propertyName, mixed $propertyValue): bool
    {
        if ($this->_hasProperty($propertyName)) {
            $this->{$propertyName} = $propertyValue;
            return true;
        }
        return false;
    }

    /**
     * @internal
     */
    public function _getProperty(string $propertyName): mixed
    {
        return $this->_hasProperty($propertyName) && isset($this->{$propertyName})
            ? $this->{$propertyName}
            : null;
    }

    /**
     * @return array<non-empty-string, mixed> a hash map of property names and property values.
     *
     * @internal
     */
    public function _getProperties(): array
    {
        $properties = get_object_vars($this);
        foreach ($properties as $propertyName => $propertyValue) {
            if (str_starts_with($propertyName, '_')) {
                unset($properties[$propertyName]);
            }
        }
        return $properties;
    }

    /**
     * @param non-empty-string $propertyName
     *
     * @internal
     */
    public function _hasProperty(string $propertyName): bool
    {
        return property_exists($this, $propertyName);
    }

    /**
     * Returns TRUE if the object is new (the uid was not set, yet)
     *
     * @internal
     */
    public function _isNew(): bool
    {
        return $this->uid === null;
    }

    /**
     * Register an object's clean state, e.g. after it has been reconstituted
     * from the database.
     *
     * @param non-empty-string|null $propertyName The name of the property to be memorized. If omitted all persistable properties are memorized.
     */
    public function _memorizeCleanState(string|null $propertyName = null): void
    {
        if ($propertyName !== null) {
            $this->_memorizePropertyCleanState($propertyName);
        } else {
            $this->_cleanProperties = [];
            foreach ($this->_getProperties() as $propertyName => $propertyValue) {
                $this->_memorizePropertyCleanState($propertyName);
            }
        }
    }

    /**
     * Register a property's clean state, e.g. after it has been reconstituted
     * from the database.
     *
     * @param non-empty-string $propertyName The name of the property to be memorized. If omitted all persistable properties are memorized.
     */
    public function _memorizePropertyCleanState(string $propertyName): void
    {
        $propertyValue = $this->_getProperty($propertyName);
        if (is_object($propertyValue)) {
            $propertyValueClone = clone $propertyValue;
            // We need to make sure the clone and the original object
            // are identical when compared with == (see _isDirty()).
            // After the cloning, the Domain Object will have the property
            // "isClone" set to TRUE, so we manually have to set it to FALSE
            // again. Possible fix: Somehow get rid of the "isClone" property,
            // which is currently needed in Fluid.
            if ($propertyValueClone instanceof AbstractDomainObject) {
                $propertyValueClone->_setClone(false);
            }

            $this->_cleanProperties[$propertyName] = $propertyValueClone;
        } else {
            $this->_cleanProperties[$propertyName] = $propertyValue;
        }
    }

    /**
     * Returns a hash map of clean properties and $values.
     *
     * @return array<non-empty-string, mixed>
     */
    public function _getCleanProperties(): array
    {
        return $this->_cleanProperties;
    }

    /**
     * Returns the clean value of the given property. The returned value will be NULL if the clean state was not memorized before, or
     * if the clean value is NULL.
     *
     * @param non-empty-string $propertyName The name of the property to be memorized.
     *
     * @internal
     */
    public function _getCleanProperty(string $propertyName): mixed
    {
        return $this->_cleanProperties[$propertyName] ?? null;
    }

    /**
     * Returns TRUE if the properties were modified after reconstitution
     *
     * @param non-empty-string|null $propertyName An optional name of a property to be checked if its value is dirty
     *
     * @throws TooDirtyException
     */
    public function _isDirty(string|null $propertyName = null): bool
    {
        if ($this->uid !== null && $this->_getCleanProperty(self::PROPERTY_UID) !== null && $this->uid != $this->_getCleanProperty(self::PROPERTY_UID)) {
            throw new TooDirtyException('The ' . self::PROPERTY_UID . ' "' . $this->uid . '" has been modified, that is simply too much.', 1222871239);
        }

        if ($propertyName === null) {
            foreach ($this->_getCleanProperties() as $propertyName => $cleanPropertyValue) {
                if ($this->isPropertyDirty($cleanPropertyValue, $this->_getProperty($propertyName)) === true) {
                    return true;
                }
            }
            return false;
        }

        if ($this->isPropertyDirty($this->_getCleanProperty($propertyName), $this->_getProperty($propertyName)) === true) {
            return true;
        }

        return false;
    }

    /**
     * Checks the $value against the $cleanState.
     */
    protected function isPropertyDirty(mixed $previousValue, mixed $currentValue): bool
    {
        // In case it is an object and it implements the ObjectMonitoringInterface, we call _isDirty() instead of a simple comparison of objects.
        // We do this, because if the object itself contains a lazy loaded property, the comparison of the objects might fail even if the object didn't change
        if (is_object($currentValue)) {
            $currentTypeString = null;
            if ($currentValue instanceof LazyLoadingProxy) {
                $currentTypeString = $currentValue->_getTypeAndUidString();
            } elseif ($currentValue instanceof DomainObjectInterface) {
                $currentTypeString = $currentValue::class . ':' . $currentValue->getUid();
            }

            if ($currentTypeString !== null) {
                $previousTypeString = null;
                if ($previousValue instanceof LazyLoadingProxy) {
                    $previousTypeString = $previousValue->_getTypeAndUidString();
                } elseif ($previousValue instanceof DomainObjectInterface) {
                    $previousTypeString = $previousValue::class . ':' . $previousValue->getUid();
                }

                $result = $currentTypeString !== $previousTypeString;
            } elseif ($currentValue instanceof ObjectMonitoringInterface) {
                $result = !is_object($previousValue) || $currentValue->_isDirty() || $previousValue::class !== $currentValue::class;
            } else {
                // For all other objects we do only a simple comparison (!=) as we want cloned objects to return the same values.
                $result = $previousValue != $currentValue;
            }
        } else {
            $result = $previousValue !== $currentValue;
        }
        return $result;
    }

    public function _isClone(): bool
    {
        return $this->_isClone;
    }

    /**
     * Setter whether this Domain Object is a clone of another one.
     * NEVER SET THIS PROPERTY DIRECTLY. We currently need it to make the
     * _isDirty check inside AbstractEntity work, but it is just a work-
     * around right now.
     *
     * @internal
     */
    public function _setClone(bool $clone)
    {
        $this->_isClone = $clone;
    }

    public function __clone()
    {
        $this->_isClone = true;
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return static::class . ':' . (string)$this->uid;
    }
}
