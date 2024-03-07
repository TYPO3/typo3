<?php declare(strict_types=1);

namespace TYPO3\CMS\Core\Site\Infrastructure\YamlConfig;

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Resources\ResourceInterface;

/**
 * @implements \TYPO3\CMS\Core\Site\Domain\SiteRepository<Site>
 */
readonly class SiteRepository implements \TYPO3\CMS\Core\Site\Domain\SiteRepository
{
    public function __construct(
        private SiteConfiguration $siteConfiguration
    ) {}

    public function count(): int
    {
        return \count($this->siteConfiguration->getAllExistingSites());
    }

    public function delete(ResourceInterface $resource): void
    {
        $this->siteConfiguration->delete($resource->getId());
    }

    public function deleteAll(ResourceInterface ...$resources): void
    {
        \array_walk($resources, fn(Site $site) => $this->siteConfiguration->delete($site->getId()));
    }

    public function deleteAllById(string|\Stringable ...$ids): void
    {
        \array_walk($ids, fn(string|\Stringable $id) => $this->siteConfiguration->delete((string) $id));
    }

    public function existsById(\Stringable|string $id): bool
    {
        return $this->findById($id) !== null;
    }

    public function findAll(): iterable
    {
        return \array_values($this->siteConfiguration->getAllExistingSites());
    }

    public function findAllById(string|\Stringable ...$ids): iterable
    {
        return \array_filter(
            $this->siteConfiguration->getAllExistingSites(),
            fn(Site $site) => in_array($site->getId(), $ids)
        );
    }

    public function findById(\Stringable|string $id): ?ResourceInterface
    {
        return $this->siteConfiguration->getAllExistingSites()[$id] ?? null;
    }

    public function save(ResourceInterface $resource): ResourceInterface
    {
        // TODO: Implement save() method.
    }

    public function saveAll(ResourceInterface ...$resources): iterable
    {
        // TODO: Implement saveAll() method.
    }
}
