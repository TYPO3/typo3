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

namespace TYPO3\CMS\Frontend\Page;

/**
 * Contains a list of all reasons that TYPO3 internally uses when a page cannot be found/rendered etc.
 * See TypoScriptFrontendController for more details and the usage.
 */
final class PageAccessFailureReasons
{
    // Page resolving issues
    public const NO_PAGES_FOUND = 'page.database.empty';
    public const PAGE_NOT_FOUND = 'page';
    public const ROOTLINE_BROKEN = 'page.rootline';

    // Page configuration issues
    public const RENDERING_INSTRUCTIONS_NOT_FOUND = 'rendering_instructions';
    public const RENDERING_INSTRUCTIONS_NOT_CONFIGURED = 'rendering_instructions.type';

    // Validation errors
    public const INVALID_PAGE_ARGUMENTS = 'page.invalid_arguments';
    public const CACHEHASH_COMPARISON_FAILED = 'cache_hash.comparison';
    public const CACHEHASH_EMPTY = 'cache_hash.empty';

    // Language-related issues
    public const LANGUAGE_NOT_AVAILABLE = 'language';
    public const LANGUAGE_NOT_AVAILABLE_STRICT_MODE = 'language.strict';
    public const LANGUAGE_AND_FALLBACKS_NOT_AVAILABLE = 'language.fallbacks';
    public const LANGUAGE_DEFAULT_NOT_AVAILABLE = 'language.default';

    // Access restrictions
    public const ACCESS_DENIED_GENERAL = 'access';
    public const ACCESS_DENIED_PAGE_NOT_RESOLVED = 'access.page';
    public const ACCESS_DENIED_SUBSECTION_NOT_RESOLVED = 'access.subsection';
    public const ACCESS_DENIED_HOST_PAGE_MISMATCH = 'access.host_mismatch';
    public const ACCESS_DENIED_INVALID_PAGETYPE = 'access.pagetype';

    // System errors
    public const DATABASE_CONNECTION_FAILED = 'system.database';

    /**
     * Labels for the status codes
     *
     * @var string[]
     */
    protected $messages = [
        self::NO_PAGES_FOUND => 'No page on rootlevel found',
        self::PAGE_NOT_FOUND => 'The requested page does not exist',

        self::RENDERING_INSTRUCTIONS_NOT_FOUND => 'No TypoScript template found',
        self::RENDERING_INSTRUCTIONS_NOT_CONFIGURED => 'The page is not configured',

        self::INVALID_PAGE_ARGUMENTS => 'Page Arguments could not be resolved',
        self::CACHEHASH_COMPARISON_FAILED => 'Request parameters could not be validated (&cHash comparison failed)',
        self::CACHEHASH_EMPTY => 'Request parameters could not be validated (&cHash empty)',

        self::LANGUAGE_NOT_AVAILABLE => 'Page is not available in the requested language',
        self::LANGUAGE_NOT_AVAILABLE_STRICT_MODE => 'Page is not available in the requested language (strict)',
        self::LANGUAGE_AND_FALLBACKS_NOT_AVAILABLE => 'Page is not available in the requested language (fallbacks did not apply)',
        self::LANGUAGE_DEFAULT_NOT_AVAILABLE => 'Page is not available in default language',

        self::ACCESS_DENIED_GENERAL => 'The requested page was not accessible',
        self::ACCESS_DENIED_PAGE_NOT_RESOLVED => 'ID was not an accessible page',
        self::ACCESS_DENIED_SUBSECTION_NOT_RESOLVED => 'Subsection was found and not accessible',
        self::ACCESS_DENIED_HOST_PAGE_MISMATCH => 'ID was outside the domain',
        self::ACCESS_DENIED_INVALID_PAGETYPE => 'The requested page type cannot be rendered',

        self::DATABASE_CONNECTION_FAILED => 'Database Connection failed',
        self::ROOTLINE_BROKEN => 'The requested page did not have a proper connection to the tree-root',
    ];

    /**
     * @param string $reasonCode a valid reason code (see above)
     * @return string
     */
    public function getMessageForReason(string $reasonCode): string
    {
        if (!isset($this->messages[$reasonCode])) {
            throw new \InvalidArgumentException('No message for page access reason code "' . $reasonCode . '" found.', 1529299833);
        }
        return $this->messages[$reasonCode];
    }
}
