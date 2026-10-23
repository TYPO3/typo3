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

namespace TYPO3\CMS\Extensionmanager\Remote;

use TYPO3\CMS\Extensionmanager\Remote\Dto\FairPackage;

/**
 * Reads the FAIR metadata document TER serves for an extension at
 * https://extensions.typo3.org/fair/v1/packages/{extensionKey}.
 *
 * One document carries every release of the extension with its
 * requirements, artifact URL and checksum, which is all that resolving a
 * package for download needs. Browsing is not its business: FAIR defines
 * no listing or search, {@see TerApiClient} answers those.
 *
 * @internal This class is a specific implementation and is not part of the Public TYPO3 API.
 */
class FairPackageClient extends AbstractJsonApiClient
{
    private const BASE_URL = 'https://extensions.typo3.org/fair/v1';
    protected const UNAVAILABLE_CACHE_IDENTIFIER = 'fair_api_unavailable';

    public function getPackage(string $extensionKey): ?FairPackage
    {
        $data = $this->requestJson(self::BASE_URL . '/packages/' . rawurlencode(strtolower($extensionKey)));
        return FairPackage::fromArray($data);
    }
}
