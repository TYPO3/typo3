<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Repository;

use TYPO3\CMS\Resources\ResourceInterface;

/**
 * @template T of ResourceInterface
 * @implements CrudRepositoryInterface<T>
 */
abstract class InMemoryRepository implements CrudRepositoryInterface
{

    /** @var ResourceInterface */
    private array $resources = [];

    public function findAll(): iterable
    {
        return \array_values($this->resources);
    }

    public function findById(string|\Stringable $id): ?ResourceInterface
    {
        return $this->resources[(string) $id] ?? null;
    }

    public function count(): int
    {
        return count($this->resources);
    }

    public function delete(ResourceInterface $resource): void
    {
        unset($this->resources[(string) $resource->getId()]);
    }

    public function deleteAll(ResourceInterface ...$resources): void
    {
        \array_walk($resources,  $this->delete(...));
    }

    public function deleteAllById(string|\Stringable ...$ids): void
    {
        \array_walk($ids, function (string|\Stringable $id) { unset($this->resources[(string) $id]); });
    }

    public function existsById(string|\Stringable $id): bool
    {
        return isset($this->resources[(string) $id]);
    }

    public function findAllById(string|\Stringable ...$ids): iterable
    {
        return \array_values(\array_filter($this->resources, fn(ResourceInterface $resource) => in_array($resource->getId(), $ids)));
    }

    public function save(ResourceInterface $resource): ResourceInterface
    {
        return $this->resources[$resource->getId()] = $resource;
    }

    public function saveAll(ResourceInterface ...$resources): iterable
    {
        return \array_map($this->save(...), $resources);
    }
}
