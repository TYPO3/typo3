<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Definition;

use TYPO3\CMS\Resources\Repository\InMemoryRepository;
use TYPO3\CMS\Resources\ResourceInterface;

/**
 * @extends  InMemoryRepository<MetadataInterface>
 */
final class Registry extends InMemoryRepository implements RegistryInterface
{
    public function findById(\Stringable|string $id): ?ResourceInterface
    {
        $id = (string)$id;
        if (null !== $definitionMetadata = parent::findById($id)) {
            return $definitionMetadata;
        }
        foreach ($this->findAll() as $definitionMetadata) {
            if ($id === $definitionMetadata->getNames()->getPlural()
                || $id === $definitionMetadata->getNames()->getSingular()
                || $id === $definitionMetadata->getNames()->getKind()
                || in_array($id, $definitionMetadata->getNames()->getShortnames())
            ) {
                return $definitionMetadata;
            }
        }
        return null;
    }

    public function existsById(\Stringable|string $id): bool
    {
        return null !== parent::findById($id);
    }

    public function findAllById(string|\Stringable ...$ids): iterable
    {
        return \array_filter(\array_map(fn($id) => $this->findById($id), $ids));

    }


}
