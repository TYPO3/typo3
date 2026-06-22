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

namespace TYPO3\CMS\Extensionmanager\Domain\Model;

/**
 * Immutable identity of a remote package: the package key, a concrete version
 * and the remote it originates from. Replaces the database uid as the way the
 * backend addresses a package to be downloaded or installed, so packages can be
 * resolved independently of whether they are persisted locally.
 *
 * @internal This class is a specific domain model implementation and is not part of the Public TYPO3 API.
 */
final readonly class PackageIdentifier
{
    public function __construct(
        public string $packageKey,
        public string $version,
        public string $remote,
    ) {}
}
