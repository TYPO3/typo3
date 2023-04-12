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
 * Resolves emails
 */
class EmailLinkHandler implements LinkHandlingInterface
{
    /**
     * Returns the link to an email as a string
     */
    public function asString(array $parameters): string
    {
        $queryParameters = [];
        foreach (['subject', 'cc', 'bcc', 'body'] as $additionalInfo) {
            if (isset($parameters[$additionalInfo])) {
                $queryParameters[$additionalInfo] = rawurldecode(trim($parameters[$additionalInfo]));
            }
        }
        $result = 'mailto:' . trim($parameters['email']);
        if ($queryParameters !== []) {
            // We need to percent-encode additional parameters (RFC 3986)
            $result .= '?' . http_build_query($queryParameters, '', '&', PHP_QUERY_RFC3986);
        }
        return $result;
    }

    /**
     * Returns the email address without the "mailto:" prefix
     * in the 'email' property of the array.
     */
    public function resolveHandlerData(array $data): array
    {
        $linkParts = parse_url($data['email'] ?? '');
        $data['email'] = trim($linkParts['path'] ?? '');
        if (isset($linkParts['query'])) {
            $result = [];
            parse_str($linkParts['query'], $result);
            foreach (['subject', 'cc', 'bcc', 'body'] as $additionalInfo) {
                if (isset($result[$additionalInfo])) {
                    $data[$additionalInfo] = trim($result[$additionalInfo]);
                }
            }
        }
        return $data;
    }
}
