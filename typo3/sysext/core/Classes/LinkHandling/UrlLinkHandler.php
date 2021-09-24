<?php

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
 * Resolves URLs (simple, no magic needed)
 */
class UrlLinkHandler implements LinkHandlingInterface
{

    /**
     * Returns the URL as given
     *
     * @param array $parameters
     * @return string
     */
    public function asString(array $parameters): string
    {
        return $this->addHttpSchemeAsFallback($parameters['url']);
    }

    /**
     * Returns the URL as is
     *
     * @param array $data (needs 'url') inside
     * @return array
     */
    public function resolveHandlerData(array $data): array
    {
        return ['url' => $this->addHttpSchemeAsFallback($data['url'])];
    }

    /**
     * Ensures that a scheme is always added, if www.typo3.org was added previously
     *
     * @param string $url the URL
     * @return string
     */
    protected function addHttpSchemeAsFallback(string $url): string
    {
        if (!empty($url)) {
            if (str_starts_with($url, '//')) {
                return $url;
            }
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (empty($scheme)) {
                $url = 'http://' . $url;
            // 'java{TAB}script:' is parsed as empty URL scheme, thus not ending up here
            } elseif (in_array(strtolower($scheme), ['javascript', 'data'], true)) {
                // deny using insecure scheme's like `javascript:` or `data:` as URL scheme
                $url = '';
            }
        }
        return $url;
    }
}
