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

namespace TYPO3\CMS\Frontend\Cache;

/**
 * Substitutes a cached nonce value with the actual nonce value that
 * is valid for the current request, and which is issued as HTTP CSP
 * header during the frontend rendering process.
 */
class NonceValueSubstitution
{
    /**
     * @param array{content: string, nonce: string} $context
     */
    public function substituteNonce(array $context): ?string
    {
        $currentNonce = $GLOBALS['TYPO3_REQUEST']?->getAttribute('nonce')?->value ?? '';
        if (empty($currentNonce) || empty($context['content']) || empty($context['nonce'])) {
            return null;
        }
        return str_replace($context['nonce'], $currentNonce, $context['content']);
    }
}
