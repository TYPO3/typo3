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

namespace TYPO3\CMS\Frontend\Event;

/**
 * A generic PSR 14 Event to allow modifying the incoming (and resolved) page when building a "page link".
 *
 * This event allows Event Listener to change the page to be linked to, or add/remove possible query
 * parameters / fragments to be generated.
 */
final class ModifyPageLinkConfigurationEvent
{
    protected bool $pageWasModified = false;

    public function __construct(
        private array $configuration,
        private readonly array $linkDetails,
        private array $page,
        private array $queryParameters,
        private string $fragment
    ) {
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getLinkDetails(): array
    {
        return $this->linkDetails;
    }

    public function getPage(): array
    {
        return $this->page;
    }

    public function setPage(array $page): void
    {
        $this->page = $page;
        $this->pageWasModified = true;
    }

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    public function setQueryParameters(array $queryParameters): void
    {
        $this->queryParameters = $queryParameters;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function setFragment(string $fragment): void
    {
        $this->fragment = $fragment;
    }

    public function pageWasModified(): bool
    {
        return $this->pageWasModified;
    }
}
