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

namespace TYPO3\CMS\Core\Resource\PublicUrl;

use Psr\Http\Message\UriInterface;

/**
 * The reverse of a Flysystem PublicUrlGenerator: maps a requested URI back to
 * the path it was generated for. A generator whose URLs can be mapped back
 * implements this interface as well.
 */
interface PublicUrlResolverInterface
{
    /**
     * Returns the Flysystem path of the file or folder the URI points to, a
     * folder path ends with a slash and the root folder is an empty string.
     * Returns null if the URI is not one of the generated URLs.
     *
     * @param string $sitePath The path TYPO3 is installed in, see NormalizedParams::getSitePath()
     */
    public function resolvePath(UriInterface $uri, string $sitePath): ?string;
}
