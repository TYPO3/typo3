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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Processing;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\Report;

trait HandlerTrait
{
    private function resolveBlockedUri(Report $report): ?UriInterface
    {
        try {
            return new Uri($report?->details['blocked-uri'] ?? '');
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * `violatedDirective` is a historical alias of `effectiveDirective`
     * see https://www.w3.org/TR/CSP3/#violation-events
     */
    private function resolveEffectiveDirective(Report $report): ?Directive
    {
        return Directive::tryFrom($report?->details['effective-directive'] ?? '');
    }
}
