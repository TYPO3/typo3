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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationSuggestion;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\Report;

/**
 * Event that is dispatched when reports are handled in the
 * CSP backend module to find potential mutations as a resolution.
 */
final class InvestigateMutationsEvent
{
    private bool $stopPropagation = false;

    /**
     * @var list<MutationSuggestion>
     */
    private array $mutationSuggestions = [];

    public function __construct(
        public readonly Policy $policy,
        public readonly Report $report,
    ) {}

    public function isPropagationStopped(): bool
    {
        return $this->stopPropagation;
    }

    public function stopPropagation(): void
    {
        $this->stopPropagation = true;
    }

    /**
     * @return list<MutationSuggestion>
     */
    public function getMutationSuggestions(): array
    {
        return $this->mutationSuggestions;
    }

    /**
     * Overrides all mutation suggestions (use carefully).
     */
    public function setMutationSuggestions(MutationSuggestion ...$mutationSuggestions): void
    {
        $this->mutationSuggestions = $mutationSuggestions;
    }

    public function appendMutationSuggestions(MutationSuggestion ...$mutationSuggestions): void
    {
        if ($mutationSuggestions === []) {
            return;
        }
        $this->mutationSuggestions += $mutationSuggestions;
    }
}
