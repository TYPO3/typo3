<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Repository;

use TYPO3\CMS\Resources\RepositoryInterface;
use TYPO3\CMS\Resources\ResourceInterface;

/**
 * Interface for generic CRUD operations on a repository for a specific resource type.
 *
 * @template T of ResourceInterface
 * @implements RepositoryInterface<T>
 */
interface CrudRepositoryInterface extends RepositoryInterface
{

    /**
     * Returns the number of resources available.
     */
    public function count(): int;

    /**
     * Deletes a given resource.
     * @param T $resource
     */
    public function delete(ResourceInterface $resource): void;

    /**
     * Deletes all entities managed by the repository.
     * @param T[] $resources
     */
    public function deleteAll(ResourceInterface ...$resources): void;

    /**
     * Deletes all resources with the given IDs.
     */
    public function deleteAllById(string|\Stringable ...$ids): void;

    /**
     * Returns whether a resource with the given id exists.
     */
    public function existsById(string|\Stringable $id): bool;

    /**
     * Returns all resources.
     * @return T[]
     */
    public function findAll(): iterable;

    /**
     * Returns all resources with the given ids.
     * @return T[]
     */
    public function findAllById(string|\Stringable ...$ids): iterable;

    /**
     * Retrieves an entity by its id.
     * @return T|null
     */
    public function findById(string|\Stringable $id): ?ResourceInterface;

    /**
     * Saves a given resource.
     * @param T $resource
     * @return T
     */
    public function save(ResourceInterface $resource): ResourceInterface;

    /**
     * Saves all given entities.
     * @param T[] $resources
     * @return T[]
     */
    public function saveAll(ResourceInterface ...$resources): iterable;

}
