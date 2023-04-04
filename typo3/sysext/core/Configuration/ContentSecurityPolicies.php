<?php

declare(strict_types=1);

namespace TYPO3\CMS\Core;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Type\Map;

/**
 * Allows to fetch media assets from YouTube and Vimeo and their associated CDNs,
 * to be embedded in an `<iframe>` of the corresponding info modal in the file list
 * backend module.
 */
$externalMediaCollection = new MutationCollection(
    new Mutation(
        MutationMode::Extend,
        Directive::FrameSrc,
        new UriValue('*.youtube-nocookie.com'),
        new UriValue('*.youtube.com'),
        new UriValue('*.vimeo.com')
    ),
    // @todo this still shows violations like the following when opened in the info modal
    // > Refused to load the image 'https://i.ytimg.com/mqdefault.jpg' because it violates the
    // > following Content Security Policy directive: "img-src 'self' 'self' 'self' data: *.i.ytimg.com
    // + no problem when directly playing video via `/typo3/record/info` in new tab (not in modal)
    // + fine in Safari/macOS, fails in Chrome/macOS, ...
    new Mutation(
        MutationMode::Extend,
        Directive::ImgSrc,
        new UriValue('*.ytimg.com'),
        new UriValue('*.vimeocdn.com')
    ),
);
return Map::fromEntries(
    // add external media collection to both backend and frontend
    [Scope::backend(), $externalMediaCollection],
    [Scope::frontend(), $externalMediaCollection],
);
