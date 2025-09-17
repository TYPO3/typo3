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

namespace TYPO3\CMS\Core\SystemResource\Http;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\Uri;

/**
 * @internal
 */
class CacheBustingUri extends Uri
{
    public static function fromFileSystemPath(string $absolutePathToPotentialFile, UriInterface $baseUri, ?ApplicationType $applicationType = null): UriInterface
    {
        try {
            if (!file_exists($absolutePathToPotentialFile)) {
                return $baseUri;
            }
        } catch (\Throwable) {
            // See https://review.typo3.org/75477 for the reason of this try/catch
            // likely we can remove this in the future, but it is kept for now to ensure BC
            return $baseUri;
        }
        $configAccessor = $applicationType?->isFrontend() ? 'FE' : 'BE';
        $rewriteFileName = (bool)($GLOBALS['TYPO3_CONF_VARS'][$configAccessor]['versionNumberInFilename'] ?? false);
        $fileModificationTime = filemtime($absolutePathToPotentialFile);
        if ($rewriteFileName) {
            $nameParts = explode('.', $baseUri->getPath());
            $fileExtension = array_pop($nameParts);
            array_push($nameParts, $fileModificationTime, $fileExtension);
            $uri = $baseUri->withPath(implode('.', $nameParts));
        } else {
            $query = $baseUri->getQuery();
            $uri = $baseUri->withQuery($query . ($query !== '' ? '&' : '') . $fileModificationTime);
        }

        return $uri;
    }
}
