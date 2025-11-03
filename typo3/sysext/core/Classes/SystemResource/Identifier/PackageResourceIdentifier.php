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

namespace TYPO3\CMS\Core\SystemResource\Identifier;

use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\SystemResource\Exception\InvalidSystemResourceIdentifierException;

/**
 * This is subject to change during v14 development. Do not use.
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
final class PackageResourceIdentifier extends SystemResourceIdentifier
{
    public const TYPE = 'PKG';

    public function __construct(
        private readonly PackageInterface $package,
        private readonly string $relativePath,
        string $givenIdentifier,
    ) {
        parent::__construct($givenIdentifier);
    }

    public function getPackage(): PackageInterface
    {
        return $this->package;
    }

    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    /**
     * @throws InvalidSystemResourceIdentifierException
     */
    public function withRelativePath(string $newPath): self
    {
        return new self(
            $this->package,
            $newPath,
            $this->givenIdentifier,
        );
    }

    public function __toString()
    {
        return sprintf(
            '%s:%s:%s',
            self::TYPE,
            $this->package->getValueFromComposerManifest('name') ?? $this->package->getPackageKey(),
            $this->relativePath,
        );
    }
}
