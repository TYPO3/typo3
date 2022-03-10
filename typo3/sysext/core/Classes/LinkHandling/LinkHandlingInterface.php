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

namespace TYPO3\CMS\Core\LinkHandling;

/**
 * Interface for classes which are transforming a tag link hrefs to records or resources
 * basically any URLs that should not be saved directly in the database on as is basis
 * since they might be moved, changed by admin working in backend
 */
interface LinkHandlingInterface
{
    /**
     * @var non-empty-string will be used for links without a scheme if no default scheme is configured
     *
     * @internal Do not use directly; please use `UrlLinkHandler::getDefaultScheme()` instead to also take the
     *           configured default scheme into account.
     */
    public const DEFAULT_SCHEME = 'http';

    /**
     * Returns a string interpretation of the link href query from objects, something like
     *
     *  - t3://page?uid=23&my=value#cool
     *  - https://www.typo3.org/
     *  - t3://file?uid=13
     *  - t3://folder?storage=2&identifier=/my/folder/
     *  - mailto:mac@safe.com
     *
     * array of data -> string
     *
     * @param array $parameters
     * @return string
     */
    public function asString(array $parameters): string;

    /**
     * Returns an array with data interpretation of the link href from parsed query parameters of urn
     * representation.
     *
     * array of strings -> array of data
     *
     * @param array $data
     * @return array
     */
    public function resolveHandlerData(array $data): array;
}
