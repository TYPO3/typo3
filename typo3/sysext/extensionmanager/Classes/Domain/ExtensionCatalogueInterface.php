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

use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;

/**
 * The catalogue of extensions offered for installation, as the "Get Extensions"
 * module browses it: one page of the list at a time, the search, the versions of
 * a single extension and the distributions.
 *
 * Resolving a specific package for download is deliberately not part of this
 * contract - that is a different concern with a different set of callers.
 *
 * Implementations do not filter extensions marked as insecure, this has to
 * happen on a different level when needed.
 *
 * @internal This interface is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
interface ExtensionCatalogueInterface
{
    /**
     * The number of extensions {@see findAll} pages through.
     */
    public function countAll(): int;

    /**
     * One page of the catalogue, newest extension first.
     *
     * @return Extension[]
     */
    public function findAll(int $offset, int $limit): array;

    /**
     * One page of the extensions matching a search term, most relevant first.
     *
     * @return Extension[]
     */
    public function findByTitleOrAuthorNameOrExtensionKey(string $searchString, int $offset, int $limit): array;

    /**
     * The number of extensions {@see findByTitleOrAuthorNameOrExtensionKey} pages through.
     */
    public function countByTitleOrAuthorNameOrExtensionKey(string $searchString): int;

    /**
     * All versions of a single extension, highest version first.
     *
     * @return Extension[]
     */
    public function findByExtensionKeyOrderedByVersion(string $extensionKey): array;

    /**
     * The version of an extension that is currently offered for installation,
     * or null if the catalogue does not know the extension.
     */
    public function findOneByCurrentVersionByExtensionKey(string $extensionKey): ?Extension;

    /**
     * The distributions published by the TYPO3 CMS team.
     *
     * @return Extension[]
     */
    public function findAllOfficialDistributions(bool $showUnsuitableDistributions = false): array;

    /**
     * The distributions published by anyone else.
     *
     * @return Extension[]
     */
    public function findAllCommunityDistributions(bool $showUnsuitableDistributions = false): array;
}
