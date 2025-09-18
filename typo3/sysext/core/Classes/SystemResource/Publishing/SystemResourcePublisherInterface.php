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

namespace TYPO3\CMS\Core\SystemResource\Publishing;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\SystemResource\Type\PublicResourceInterface;

/**
 * Implementations of this interface can publish public extension resources (once implemented)
 * and therefore also generate URIs to those published resources.
 * E.g. publish resources directly to a CDN and then generate CDS URIs
 * to those resources.
 */
interface SystemResourcePublisherInterface
{
    public function publishResources(PackageInterface $package): void;

    public function generateUri(PublicResourceInterface $publicResource, ?ServerRequestInterface $request, ?UriGenerationOptions $options = null): UriInterface;

}
