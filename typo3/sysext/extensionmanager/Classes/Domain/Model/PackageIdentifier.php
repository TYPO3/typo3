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

use TYPO3\CMS\Extbase\Attribute\Validate;

/**
 * Immutable identity of a remote package: the package key, a concrete version
 * and the remote it originates from. Replaces the database uid as the way the
 * backend addresses a package to be downloaded or installed, so packages can be
 * resolved independently of whether they are persisted locally.
 *
 * Extbase actions receive this as a typed argument: the generic ObjectConverter
 * maps the request arguments to the constructor, which requires the receiving
 * controllers to allow property mapping for 'packageKey', 'version' and 'remote'.
 *
 * @internal This class is a specific domain model implementation and is not part of the Public TYPO3 API.
 */
final readonly class PackageIdentifier
{
    /**
     * The empty defaults exist for the property mapper only: a request not carrying all three
     * arguments must end up as a validation error, not as a property mapping exception.
     */
    public function __construct(
        #[Validate('NotEmpty')]
        public string $packageKey = '',
        #[Validate('NotEmpty')]
        public string $version = '',
        #[Validate('NotEmpty')]
        public string $remote = '',
    ) {}

    /**
     * The identifier as request arguments, ready to be handed to a URI builder
     * or a forwarded request. The keys match the constructor arguments the
     * property mapper maps back to.
     *
     * @return array{packageKey: string, version: string, remote: string}
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
