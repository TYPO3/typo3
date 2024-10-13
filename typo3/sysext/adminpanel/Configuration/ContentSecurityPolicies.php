<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Type\Map;

/**
 * Allows inline styles (`<span style="...">`) in content dumped in QueryInformation.html.
 *
 * Note: Using `PolicyRegistry` is not possible here, since `AdminPanelRenderer`
 * middleware generates the response(!) after `ContentSecurityPolicyHeaders` middleware.
 */
return Map::fromEntries([
    Scope::frontend(),
    new MutationCollection(
        new Mutation(MutationMode::Extend, Directive::StyleSrcAttr, SourceKeyword::unsafeInline),
    ),
]);
