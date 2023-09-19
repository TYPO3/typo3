<?php

declare(strict_types=1);

namespace TYPO3\CMS\Frontend;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Type\Map;

/**
 * Provides a simple basic Content-Security-Policy for the generic frontend scope.
 */
return Map::fromEntries([
    Scope::frontend(),
    new MutationCollection(
        new Mutation(MutationMode::Extend, Directive::DefaultSrc, SourceKeyword::self),
        new Mutation(MutationMode::Extend, Directive::ScriptSrc, SourceKeyword::nonceProxy),
        // `style-src-attr 'unsafe-inline'` required for remaining inline styles, which is okay for color & dimension
        // (e.g. `<div style="color: #000">` - but NOT having the possibility to use any other assets/files/URIs)
        new Mutation(MutationMode::Set, Directive::StyleSrcAttr, SourceKeyword::unsafeInline),
        // allow `data:` images
        new Mutation(MutationMode::Extend, Directive::ImgSrc, SourceScheme::data),
        // limits `<base>` element to be use just for same-origin URIs
        new Mutation(MutationMode::Set, Directive::BaseUri, SourceKeyword::self),
    ),
]);
