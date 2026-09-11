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

namespace TYPO3\CMS\Extensionmanager\Domain;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Remote\ApiRequestFailedException;

/**
 * Asks a catalogue that reads a remote live, and falls back to one that reads
 * the locally imported list whenever the remote cannot answer - because the
 * installation is offline, the remote is down, a proxy blocks it or a rate
 * limit was reached. With "offlineMode" enabled, the remote is not asked at all.
 *
 * This is what keeps browsing no worse than it was before the remote was
 * consulted at all: the imported list stays a complete answer, only an older
 * one.
 *
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
readonly class FallbackExtensionCatalogue implements ExtensionCatalogueInterface
{
    public function __construct(
        private ExtensionCatalogueInterface $remoteCatalogue,
        private ExtensionCatalogueInterface $localCatalogue,
        private LoggerInterface $logger,
        private ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function countAll(): int
    {
        return $this->ask(
            'countAll',
            fn(): int => $this->remoteCatalogue->countAll(),
            fn(): int => $this->localCatalogue->countAll()
        );
    }

    public function findAll(int $offset, int $limit): array
    {
        return $this->ask(
            'findAll',
            fn(): array => $this->remoteCatalogue->findAll($offset, $limit),
            fn(): array => $this->localCatalogue->findAll($offset, $limit)
        );
    }

    public function findByTitleOrAuthorNameOrExtensionKey(string $searchString, int $offset, int $limit): array
    {
        return $this->ask(
            'findByTitleOrAuthorNameOrExtensionKey',
            fn(): array => $this->remoteCatalogue->findByTitleOrAuthorNameOrExtensionKey($searchString, $offset, $limit),
            fn(): array => $this->localCatalogue->findByTitleOrAuthorNameOrExtensionKey($searchString, $offset, $limit)
        );
    }

    public function countByTitleOrAuthorNameOrExtensionKey(string $searchString): int
    {
        return $this->ask(
            'countByTitleOrAuthorNameOrExtensionKey',
            fn(): int => $this->remoteCatalogue->countByTitleOrAuthorNameOrExtensionKey($searchString),
            fn(): int => $this->localCatalogue->countByTitleOrAuthorNameOrExtensionKey($searchString)
        );
    }

    public function findByExtensionKeyOrderedByVersion(string $extensionKey): array
    {
        return $this->ask(
            'findByExtensionKeyOrderedByVersion',
            fn(): array => $this->remoteCatalogue->findByExtensionKeyOrderedByVersion($extensionKey),
            fn(): array => $this->localCatalogue->findByExtensionKeyOrderedByVersion($extensionKey)
        );
    }

    public function findOneByCurrentVersionByExtensionKey(string $extensionKey): ?Extension
    {
        return $this->ask(
            'findOneByCurrentVersionByExtensionKey',
            fn(): ?Extension => $this->remoteCatalogue->findOneByCurrentVersionByExtensionKey($extensionKey),
            fn(): ?Extension => $this->localCatalogue->findOneByCurrentVersionByExtensionKey($extensionKey)
        );
    }

    public function findAllOfficialDistributions(bool $showUnsuitableDistributions = false): array
    {
        return $this->ask(
            'findAllOfficialDistributions',
            fn(): array => $this->remoteCatalogue->findAllOfficialDistributions($showUnsuitableDistributions),
            fn(): array => $this->localCatalogue->findAllOfficialDistributions($showUnsuitableDistributions)
        );
    }

    public function findAllCommunityDistributions(bool $showUnsuitableDistributions = false): array
    {
        return $this->ask(
            'findAllCommunityDistributions',
            fn(): array => $this->remoteCatalogue->findAllCommunityDistributions($showUnsuitableDistributions),
            fn(): array => $this->localCatalogue->findAllCommunityDistributions($showUnsuitableDistributions)
        );
    }

    /**
     * @template T
     * @param \Closure(): T $remote
     * @param \Closure(): T $local
     * @return T
     */
    private function ask(string $question, \Closure $remote, \Closure $local): mixed
    {
        if ((bool)$this->extensionConfiguration->get('extensionmanager', 'offlineMode')) {
            return $local();
        }
        try {
            return $remote();
        } catch (ApiRequestFailedException $e) {
            $this->logger->notice(
                'Answering "{question}" from the imported extension list, the remote could not be asked: {reason}',
                ['question' => $question, 'reason' => $e->getMessage(), 'exception' => $e]
            );
            return $local();
        }
    }
}
