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

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Enum\ExtensionCategory;
use TYPO3\CMS\Extensionmanager\Remote\Dto\TerExtension;
use TYPO3\CMS\Extensionmanager\Remote\TerApiClient;
use TYPO3\CMS\Extensionmanager\Remote\TerExtensionMapper;

/**
 * The TER catalogue, read live through the REST API instead of the weekly
 * extensions.xml.gz dump imported into the database.
 *
 * The extensions handed out keep the "ter" remote identifier, so downloading
 * one still goes through the registered remote for it.
 *
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
readonly class TerExtensionCatalogue implements ExtensionCatalogueInterface
{
    /**
     * The user every distribution published by the TYPO3 CMS team belongs to.
     */
    private const OFFICIAL_DISTRIBUTION_OWNER = 'typo3v4';

    /**
     * The most the API hands out per page.
     */
    private const MAX_PAGE_SIZE = 50;

    public function __construct(
        private TerApiClient $client,
        private TerExtensionMapper $mapper,
        private Typo3Version $typo3Version,
        private string $remoteIdentifier = 'ter',
    ) {}

    public function countAll(): int
    {
        return $this->client->query('', $this->getMajorVersion(), 1, 1)->totalResults;
    }

    public function findAll(int $offset, int $limit): array
    {
        return $this->toExtensions($this->queryPage('', $offset, $limit));
    }

    public function findByTitleOrAuthorNameOrExtensionKey(string $searchString, int $offset, int $limit): array
    {
        return $this->toExtensions($this->queryPage($searchString, $offset, $limit));
    }

    public function countByTitleOrAuthorNameOrExtensionKey(string $searchString): int
    {
        return $this->client->query($searchString, $this->getMajorVersion(), 1, 1)->totalResults;
    }

    public function findByExtensionKeyOrderedByVersion(string $extensionKey): array
    {
        $remoteExtension = $this->client->getExtension($extensionKey);
        if ($remoteExtension === null) {
            return [];
        }
        $extensions = [];
        foreach ($this->client->getVersions($extensionKey) as $version) {
            if ($version->reviewState < 0) {
                continue;
            }
            $extensions[] = $this->mapper->map($remoteExtension, $version, $this->remoteIdentifier);
        }
        return $extensions;
    }

    public function findOneByCurrentVersionByExtensionKey(string $extensionKey): ?Extension
    {
        $remoteExtension = $this->client->getExtension($extensionKey);
        if ($remoteExtension === null || $remoteExtension->currentVersion === null || $remoteExtension->currentVersion->reviewState < 0) {
            return null;
        }
        return $this->mapper->map($remoteExtension, null, $this->remoteIdentifier);
    }

    public function findAllOfficialDistributions(bool $showUnsuitableDistributions = false): array
    {
        return $this->toExtensions(
            $this->queryDistributions($showUnsuitableDistributions, self::OFFICIAL_DISTRIBUTION_OWNER)
        );
    }

    public function findAllCommunityDistributions(bool $showUnsuitableDistributions = false): array
    {
        // The API can filter for an owner but not against one, so the official
        // distributions are taken out here - they have their own listing.
        return $this->toExtensions(array_filter(
            $this->queryDistributions($showUnsuitableDistributions, null),
            static fn(TerExtension $extension): bool => $extension->owner !== self::OFFICIAL_DISTRIBUTION_OWNER
        ));
    }

    /**
     * @return TerExtension[]
     */
    private function queryDistributions(bool $showUnsuitableDistributions, ?string $owner): array
    {
        $distributions = [];
        $page = 1;
        do {
            $result = $this->client->query(
                '',
                $showUnsuitableDistributions ? 0 : $this->getMajorVersion(),
                $page++,
                self::MAX_PAGE_SIZE,
                ExtensionCategory::Distribution->getStringValue(),
                $owner
            );
            $distributions = [...$distributions, ...$result->extensions];
        } while ($result->extensions !== [] && count($distributions) < $result->totalResults);
        return $distributions;
    }

    /**
     * The paginator counts in offsets while the API counts in pages, and it only
     * ever asks for whole pages, so the offset maps onto a page number.
     *
     * @return TerExtension[]
     */
    private function queryPage(string $search, int $offset, int $limit): array
    {
        $limit = max(1, $limit);
        return $this->client->query(
            $search,
            $this->getMajorVersion(),
            intdiv(max(0, $offset), $limit) + 1,
            $limit
        )->extensions;
    }

    /**
     * @param TerExtension[] $remoteExtensions
     * @return Extension[]
     */
    private function toExtensions(array $remoteExtensions): array
    {
        $extensions = [];
        foreach ($remoteExtensions as $remoteExtension) {
            if ($remoteExtension->currentVersion === null || $remoteExtension->currentVersion->reviewState < 0) {
                continue;
            }
            $extensions[] = $this->mapper->map($remoteExtension, null, $this->remoteIdentifier);
        }
        return $extensions;
    }

    /**
     * Early in a major version's life, and on its development branch, TER has
     * nothing published for it yet. Offering what fits the previous major version
     * then beats an empty module. The probe is the same request countAll() sends,
     * so the response cache answers it after the first time.
     */
    private function getMajorVersion(): int
    {
        $majorVersion = $this->typo3Version->getMajorVersion();
        if ($this->client->query('', $majorVersion, 1, 1)->totalResults === 0) {
            return $majorVersion - 1;
        }
        return $majorVersion;
    }
}
