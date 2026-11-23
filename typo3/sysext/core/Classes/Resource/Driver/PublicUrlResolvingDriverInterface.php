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

namespace TYPO3\CMS\Core\Resource\Driver;

use Psr\Http\Message\UriInterface;

/**
 * A driver that can map a requested URI back to the file or folder whose
 * public URL it is, the reverse of DriverInterface::getPublicUrl().
 */
interface PublicUrlResolvingDriverInterface
{
    /**
     * Returns the identifier of the file or folder the URI is the public URL
     * of, a folder identifier ends with a slash. The file or folder is not
     * checked for existence. Returns null if the URI is not a public URL of
     * this driver.
     *
     * @param string $sitePath The path TYPO3 is installed in, see NormalizedParams::getSitePath()
     */
    public function resolvePublicUrl(UriInterface $uri, string $sitePath): ?string;
}
