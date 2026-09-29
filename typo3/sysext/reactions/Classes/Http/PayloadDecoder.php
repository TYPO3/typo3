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

namespace TYPO3\CMS\Reactions\Http;

/**
 * Reads the body of a reaction request, for the endpoint and for the dry run of the
 * backend module alike, so both answer the same body the same way.
 *
 * @internal This is a specific reaction implementation and is not considered part of the Public TYPO3 API.
 */
final class PayloadDecoder
{
    /**
     * Null for a body that is no JSON, or JSON of a scalar.
     */
    public static function decode(string $body): ?array
    {
        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        return is_array($payload) ? $payload : null;
    }
}
