<?php

declare(strict_types = 1);

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

namespace TYPO3\CMS\Install\SystemEnvironment\ServerResponse;

/**
 * Evaluates a Content-Security-Policy HTTP header.
 *
 * @internal should only be used from within TYPO3 Core
 */
class ContentSecurityPolicyHeader
{
    protected const HEADER_PATTERN = '#(?<directive>default-src|script-src|style-src|object-src)\h+(?<rule>[^;]+)(?:\s*;\s*|$)#';

    /**
     * @var ContentSecurityPolicyDirective[]
     */
    protected $directives = [];

    public function __construct(string $header)
    {
        if (preg_match_all(self::HEADER_PATTERN, $header, $matches)) {
            foreach ($matches['directive'] as $index => $name) {
                $this->directives[$name] = new ContentSecurityPolicyDirective(
                    $name,
                    $matches['rule'][$index]
                );
            }
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->directives);
    }

    public function mitigatesCrossSiteScripting(): bool
    {
        $defaultSrc = isset($this->directives['default-src'])
            ? $this->directiveMitigatesCrossSiteScripting($this->directives['default-src'])
            : null;
        $scriptSrc = isset($this->directives['script-src'])
            ? $this->directiveMitigatesCrossSiteScripting($this->directives['script-src'])
            : null;
        $styleSrc = isset($this->directives['style-src'])
            ? $this->directiveMitigatesCrossSiteScripting($this->directives['style-src'])
            : null;
        $objectSrc = isset($this->directives['object-src'])
            ? $this->directiveMitigatesCrossSiteScripting($this->directives['object-src'])
            : null;
        return ($scriptSrc ?? $defaultSrc ?? false)
            && ($styleSrc ?? $defaultSrc ?? false)
            && ($objectSrc ?? $defaultSrc ?? false);
    }

    protected function directiveMitigatesCrossSiteScripting(ContentSecurityPolicyDirective $directive): bool
    {
        return $directive->hasInstructions('none')
            && !$directive->hasInstructions('unsafe-eval', 'unsafe-inline');
    }
}
