<?php

declare(strict_types=1);

namespace TYPO3\CMS\Backend;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Type\Map;

/**
 * \TYPO3\CMS\IndexedSearch\ViewHelpers\PageBrowsingViewHelper::providePageSelectorJavaScript
 * adds inline JavaScript for the corresponding pagination feature - this configuration adds
 * the required CSP `nonce-proxy` for the frontend scope.
 */
return Map::fromEntries([
    Scope::frontend(),
    new MutationCollection(
        new Mutation(MutationMode::Extend, Directive::ScriptSrc, SourceKeyword::nonceProxy),
    ),
]);
