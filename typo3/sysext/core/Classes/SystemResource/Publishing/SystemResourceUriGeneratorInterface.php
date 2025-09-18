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

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\SystemResource\Type\PublicResourceInterface;

/**
 * This is an implementation detail to allow not exposing the absolute file path
 * to extension resources directly, but only to the resource publisher
 * when generating URLs to the _assets directory.
 *
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
interface SystemResourceUriGeneratorInterface
{
    public function generateForPublicResourceBasedOnAbsolutePath(PublicResourceInterface $resource, string $absoluteResourcePath): UriInterface;
    public function generateForFile(File $file): UriInterface;
}
