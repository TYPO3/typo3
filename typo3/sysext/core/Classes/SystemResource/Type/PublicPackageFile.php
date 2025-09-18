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

namespace TYPO3\CMS\Core\SystemResource\Type;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourceUriGeneratorInterface;

/**
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
final class PublicPackageFile extends PackageResource implements PublicResourceInterface
{
    public function getPublicUri(SystemResourceUriGeneratorInterface $uriGenerator): UriInterface
    {
        return $uriGenerator->generateForPublicResourceBasedOnAbsolutePath($this, $this->package->getPackagePath() . $this->relativePath);
    }

    public function isPublished(): bool
    {
        return $this->package->getResources()->isPublicPath($this->relativePath);
    }

    /**
     * @internal This API is only meant for very limited use cases,
     * e.g. for building URIs for Vite dev server, where the dev server actually
     * publishes (exposes) all (private) source files, so that they can be processed on the fly
     * Do *not* use within TYPO3 core or other third party extensions
     */
    public static function fromPackageResource(PackageResource $packageResource): self
    {
        if ($packageResource instanceof PublicResourceInterface) {
            throw new \LogicException('It is pointless to create a public resource from an already public resource', 1761217630);
        }
        return new self(
            $packageResource->package,
            $packageResource->relativePath,
            $packageResource->identifier,
        );
    }
}
