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

namespace TYPO3\CMS\Extbase\Persistence\Generic;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * The persistence session - acts as a Unit of Work for Extbase persistence framework.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Session
{
    protected ObjectStorage $reconstitutedEntities;
    protected ObjectStorage $objectMap;

    /**
     * @var array<non-empty-string, array<non-empty-string, object>>
     */
    protected array $identifierMap = [];

    /**
     * Constructs a new Session
     */
    public function __construct()
    {
        $this->reconstitutedEntities = new ObjectStorage();
        $this->objectMap = new ObjectStorage();
    }

    /**
     * Registers data for a reconstituted object.
     *
     * $entityData format is described in
     * "Documentation/PersistenceFramework object data format.txt"
     */
    public function registerReconstitutedEntity(object $entity): void
    {
        $this->reconstitutedEntities->attach($entity);
    }

    /**
     * Unregisters data for a reconstituted object
     */
    public function unregisterReconstitutedEntity(object $entity): void
    {
        if ($this->reconstitutedEntities->contains($entity)) {
            $this->reconstitutedEntities->detach($entity);
        }
    }

    /**
     * Returns all objects which have been registered as reconstituted
     */
    public function getReconstitutedEntities(): ObjectStorage
    {
        return $this->reconstitutedEntities;
    }

    // @todo implement the is dirty checking behaviour of the Flow persistence session here

    /**
     * Checks whether the given object is known to the identity map
     */
    public function hasObject(object $object): bool
    {
        return $this->objectMap->contains($object);
    }

    /**
     * Checks whether the given identifier is known to the identity map
     *
     * @param non-empty-string $identifier
     * @param class-string $className
     */
    public function hasIdentifier(string $identifier, string $className): bool
    {
        return isset($this->identifierMap[$this->getClassIdentifier($className)][$identifier]);
    }

    /**
     * Returns the object for the given identifier
     *
     * @param non-empty-string $identifier
     * @param class-string $className
     */
    public function getObjectByIdentifier(string $identifier, string $className): object
    {
        return $this->identifierMap[$this->getClassIdentifier($className)][$identifier];
    }

    /**
     * Returns the identifier for the given object from
     * the session, if the object was registered.
     *
     * @return non-empty-string|null
     */
    public function getIdentifierByObject(object $object): string|null
    {
        if ($this->hasObject($object)) {
            return $this->objectMap[$object];
        }
        return null;
    }

    /**
     * Register an identifier for an object
     *
     * @param non-empty-string $identifier
     */
    public function registerObject(object $object, string $identifier): void
    {
        $this->objectMap[$object] = $identifier;
        $this->identifierMap[$this->getClassIdentifier(get_class($object))][$identifier] = $object;
    }

    /**
     * Unregister an object
     */
    public function unregisterObject(object $object): void
    {
        unset($this->identifierMap[$this->getClassIdentifier(get_class($object))][$this->objectMap[$object]]);
        $this->objectMap->detach($object);
    }

    /**
     * Destroy the state of the persistence session and reset
     * all internal data.
     */
    public function destroy(): void
    {
        $this->identifierMap = [];
        $this->objectMap = new ObjectStorage();
        $this->reconstitutedEntities = new ObjectStorage();
    }

    /**
     * Objects are stored in the cache with their implementation class name
     * to allow reusing instances of different classes that point to the same implementation
     * Returns a unique class identifier respecting configured implementation class names
     *
     * @param class-string $className
     * @return non-empty-string
     */
    protected function getClassIdentifier(string $className): string
    {
        return strtolower($className);
    }
}
