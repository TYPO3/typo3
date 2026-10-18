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

/**
 * A generic Domain Object.
 *
 * All Model domain objects need to inherit from either `AbstractEntity` or `AbstractValueObject` (instead of from this
 * class), as this provides important framework information.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
abstract class AbstractDomainObject implements DomainObjectInterface
{
    public const string PROPERTY_UID = 'uid';
    public const string PROPERTY_PID = 'pid';
    public const string PROPERTY_LOCALIZED_UID = '_localizedUid';
    public const string PROPERTY_LANGUAGE_UID = '_languageUid';
    public const string PROPERTY_VERSIONED_UID = '_versionedUid';

    /**
     * @var int<1, max>|null The uid of the record. The uid is only unique in the context of the database table.
     */
    protected ?int $uid = null;

    /**
     * @var int<0, max>|null The uid of the localized record. Holds the uid of the record in default language (the translationOrigin).
     *
     * @internal
     * @todo make private in 13.0 and expose value via getter
     */
    protected ?int $_localizedUid = null;

    /**
     * @var int<-1, max>|null The uid of the language of the object. This is the id of the corresponding sing language.
     *
     * @internal
     * @todo make private in 13.0 and expose value via getter
     */
    protected ?int $_languageUid = null;

    /**
     * The uid of the versioned record.
     *
     * @internal
     * @todo make private in 13.0 and expose value via getter
     */
    protected ?int $_versionedUid = null;

    /**
     * @var int<0, max>|null The id of the page the record is "stored".
     */
    protected ?int $pid = null;

    /**
     * @return int<1, max>|null
     */
    public function getUid(): ?int
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
    public function getPid(): ?int
    {
        if ($this->pid === null) {
            return null;
        }
        return (int)$this->pid;
    }

    /**
     * @internal
     */
    public function _setProperty(string $propertyName, mixed $value): bool
    {
        if ($this->_hasProperty($propertyName)) {
            $this->{$propertyName} = $value;
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
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return static::class . ':' . $this->uid;
    }

    /**
     * A lazy entity is explicitly initialized before its state is collected: The engine's
     * serializer must never trigger the initialization of a lazy object during its traversal,
     * as that corrupts back references within the serialized payload.
     */
    public function __serialize(): array
    {
        $reflection = new \ReflectionClass($this);
        if ($reflection->isUninitializedLazyObject($this)) {
            $reflection->initializeLazyObject($this);
        }
        return (array)$this;
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            if (str_starts_with($key, "\0")) {
                // Mangled key of a protected ("\0*\0name") or private ("\0Class\0name") property
                $nulPosition = strrpos($key, "\0");
                $scope = substr($key, 1, $nulPosition - 1);
                $name = substr($key, $nulPosition + 1);
                $isFormerCleanStateProperty = $scope === self::class && in_array($name, ['_cleanProperties', '_isClone'], true);
                if ($isFormerCleanStateProperty) {
                    // Payload serialized before the clean state moved into the CleanStateTracker
                    continue;
                }
                if ($scope === '*' || $scope === self::class) {
                    $this->{$name} = $value;
                } elseif (property_exists($scope, $name)) {
                    new \ReflectionProperty($scope, $name)->setValue($this, $value);
                }
            } else {
                $this->{$key} = $value;
            }
        }
    }
}
