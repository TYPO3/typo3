<?php

declare(strict_types=1);

namespace TYPO3\CMS\Backend;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Type\Map;

/**
 * Provides a simple basic Content-Security-Policy for the generic backend scope.
 */
return Map::fromEntries([
    Scope::backend(),
    new MutationCollection(
        new Mutation(MutationMode::Extend, Directive::DefaultSrc, SourceKeyword::self),
        // script-src 'nonce-...' required for importmaps
        new Mutation(MutationMode::Extend, Directive::ScriptSrc, SourceKeyword::nonceProxy),
        // `style-src 'unsafe-inline'` required for lit in safari and firefox to allow inline <style> tags
        // (for browsers that do not support https://caniuse.com/mdn-api_shadowroot_adoptedstylesheets)
        new Mutation(MutationMode::Extend, Directive::StyleSrc, SourceKeyword::unsafeInline),
        // `style-src-attr 'unsafe-inline'` required for remaining inline styles, which is okay for color & dimension
        // (e.g. `<div style="color: #000">` - but NOT having the possibility to use any other assets/files/URIs)
        new Mutation(MutationMode::Set, Directive::StyleSrcAttr, SourceKeyword::unsafeInline),
        // allow `data:` images
        new Mutation(MutationMode::Extend, Directive::ImgSrc, SourceScheme::data),
        // muuri.js is creating workers from `blob:` (?!?)
        new Mutation(MutationMode::Set, Directive::WorkerSrc, SourceKeyword::self, SourceScheme::blob),
        // `frame-src blob:` required for es-module-shims blob: URLs
        new Mutation(MutationMode::Extend, Directive::FrameSrc, SourceScheme::blob),
        // deny `<base>` element which might be used for cross-origin targets
        new Mutation(MutationMode::Set, Directive::BaseUri, SourceKeyword::none),
        // deny `<object>` and `<embed>` elements
        new Mutation(MutationMode::Set, Directive::ObjectSrc, SourceKeyword::none),

        // Allows to fetch media assets from YouTube and Vimeo and their associated CDNs,
        // to be embedded in an `<iframe>` of the corresponding info modal in the file list
        // backend module.
        new Mutation(
            MutationMode::Extend,
            Directive::FrameSrc,
            new UriValue('*.youtube-nocookie.com'),
            new UriValue('*.youtube.com'),
            new UriValue('*.vimeo.com')
        ),
        new Mutation(
            MutationMode::Extend,
            Directive::ImgSrc,
            new UriValue('*.ytimg.com'),
            new UriValue('*.vimeocdn.com')
        ),
    ),
]);
