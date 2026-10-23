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
use TYPO3\CMS\Extensionmanager\Domain\Model\PackageIdentifier;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionNotFoundException;
use TYPO3\CMS\Extensionmanager\Remote\ApiRequestFailedException;
use TYPO3\CMS\Extensionmanager\Remote\UnsupportedVersionConstraintException;

/**
 * Asks a resolver that reads a remote live, and falls back to one that reads
 * the imported list whenever the remote cannot answer, does not know the
 * package, or sends a version constraint that cannot be converted.
 *
 * Unlike browsing, resolving falls back on an empty answer as well: a remote
 * document lists a limited number of releases, and a version missing there
 * may well sit in the imported list. Nothing gets mixed - each question is
 * answered by one source. In offline mode the remote is not asked at all.
 *
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
readonly class FallbackPackageResolver implements PackageResolverInterface
{
    public function __construct(
        private PackageResolverInterface $remoteResolver,
        private PackageResolverInterface $localResolver,
        private LoggerInterface $logger,
        private ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function getByPackageIdentifier(PackageIdentifier $packageIdentifier): Extension
    {
        if ($this->isOfflineMode()) {
            return $this->localResolver->getByPackageIdentifier($packageIdentifier);
        }
        try {
            return $this->remoteResolver->getByPackageIdentifier($packageIdentifier);
        } catch (ExtensionNotFoundException) {
            // The remote does not list it, the imported list may
        } catch (ApiRequestFailedException|UnsupportedVersionConstraintException $e) {
            $this->logFailure('getByPackageIdentifier', $e);
        }
        return $this->localResolver->getByPackageIdentifier($packageIdentifier);
    }

    public function findOneByExtensionKeyAndVersion(string $extensionKey, string $version): ?Extension
    {
        return $this->ask(
            'findOneByExtensionKeyAndVersion',
            fn(): ?Extension => $this->remoteResolver->findOneByExtensionKeyAndVersion($extensionKey, $version),
            fn(): ?Extension => $this->localResolver->findOneByExtensionKeyAndVersion($extensionKey, $version)
        );
    }

    public function findHighestAvailableVersion(string $extensionKey): ?Extension
    {
        return $this->ask(
            'findHighestAvailableVersion',
            fn(): ?Extension => $this->remoteResolver->findHighestAvailableVersion($extensionKey),
            fn(): ?Extension => $this->localResolver->findHighestAvailableVersion($extensionKey)
        );
    }

    public function findByVersionRangeAndExtensionKeyOrderedByVersion(string $extensionKey, int $lowestVersion = 0, int $highestVersion = 0, bool $includeCurrentVersion = true): array
    {
        return $this->ask(
            'findByVersionRangeAndExtensionKeyOrderedByVersion',
            fn(): array => $this->remoteResolver->findByVersionRangeAndExtensionKeyOrderedByVersion($extensionKey, $lowestVersion, $highestVersion, $includeCurrentVersion),
            fn(): array => $this->localResolver->findByVersionRangeAndExtensionKeyOrderedByVersion($extensionKey, $lowestVersion, $highestVersion, $includeCurrentVersion)
        );
    }

    /**
     * @template T of Extension|null|array
     * @param \Closure(): T $remote
     * @param \Closure(): T $local
     * @return T
     */
    private function ask(string $question, \Closure $remote, \Closure $local): mixed
    {
        if ($this->isOfflineMode()) {
            return $local();
        }
        try {
            $answer = $remote();
        } catch (ApiRequestFailedException|UnsupportedVersionConstraintException $e) {
            $this->logFailure($question, $e);
            return $local();
        }
        return $answer === null || $answer === [] ? $local() : $answer;
    }

    private function isOfflineMode(): bool
    {
        return (bool)$this->extensionConfiguration->get('extensionmanager', 'offlineMode');
    }

    private function logFailure(string $question, ApiRequestFailedException|UnsupportedVersionConstraintException $e): void
    {
        $this->logger->notice(
            'Answering "{question}" from the imported extension list, the remote could not be asked: {reason}',
            ['question' => $question, 'reason' => $e->getMessage(), 'exception' => $e]
        );
    }
}
