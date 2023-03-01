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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Security\Nonce;

/**
 * Representation of Content-Security-Policy source keywords
 * see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/Sources#sources
 */
enum SourceKeyword: string
{
    case none = 'none';
    case self = 'self';
    case unsafeInline = 'unsafe-inline';
    case unsafeEval = 'unsafe-eval';
    case wasmUnsafeEval = 'wasm-unsafe-eval';
    case reportSample = 'report-sample';
    case strictDynamic = 'strict-dynamic';
    // nonce proxy is substituted when compiling the whole policy
    // (this value does NOT exist in the CSP definition, it's specific to TYPO3 only)
    case nonceProxy = 'nonce-proxy';

    public function vetoes(): bool
    {
        return $this === self::none;
    }

    public function isApplicable(Directive $directive): bool
    {
        // temporary, internal \WeakMap
        $onlyApplicableTo = new \WeakMap();
        $onlyApplicableTo[self::reportSample] = [
            Directive::ScriptSrc, Directive::ScriptSrcAttr, Directive::ScriptSrcElem,
            Directive::StyleSrc, Directive::StyleSrcAttr, Directive::StyleSrcElem,
        ];
        return !isset($onlyApplicableTo[$this]) || in_array($directive, $onlyApplicableTo[$this], true);
    }

    public function applySourceImplications(SourceCollection $sources): ?SourceCollection
    {
        if ($this !== self::strictDynamic) {
            return null;
        }
        // adjust existing directives when using 'strict-dynamic' (e.g. for Google Maps)
        // see https://www.w3.org/TR/CSP3/#strict-dynamic-usage
        $newSources = $sources
            ->without(SourceKeyword::self, SourceKeyword::unsafeInline)
            ->withoutTypes(UriValue::class, SourceScheme::class);
        if (!$sources->contains(self::nonceProxy) && !$sources->containsTypes(Nonce::class)) {
            $newSources = $newSources->with(self::nonceProxy);
        }
        if ($sources === $newSources) {
            return null;
        }
        return $newSources;
    }
}
