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

namespace TYPO3\CMS\Backend\Search\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;

/**
 * PSR-14 event to add, change or remove data for the live search form
 */
final class BeforeLiveSearchFormIsBuiltEvent
{
    private SearchDemand $searchDemand;

    /**
     * @var array<non-empty-string, mixed>
     */
    private array $additionalViewData = [];

    /**
     * @param list<non-empty-string> $hints
     */
    public function __construct(
        private array $hints,
        private readonly ServerRequestInterface $request,
    ) {
        $this->searchDemand = SearchDemand::fromRequest($this->request);
    }

    /**
     * @return list<non-empty-string>
     */
    public function getHints(): array
    {
        return $this->hints;
    }

    /**
     * @param list<non-empty-string> $hints
     */
    public function setHints(array $hints): void
    {
        $this->hints = [];
        $this->addHints(...$hints);
    }

    public function addHint(string $label): void
    {
        $this->addHints($label);
    }

    public function addHints(string ...$labels): void
    {
        foreach ($labels as $label) {
            if ($label === '') {
                continue;
            }
            $this->hints[] = $label;
        }
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getSearchDemand(): SearchDemand
    {
        return $this->searchDemand;
    }

    public function setSearchDemand(SearchDemand $searchDemand): void
    {
        $this->searchDemand = $searchDemand;
    }

    /**
     * @return array<non-empty-string, mixed>
     */
    public function getAdditionalViewData(): array
    {
        return $this->additionalViewData;
    }

    /**
     * @param array<non-empty-string, mixed> $viewData
     */
    public function setAdditionalViewData(array $viewData): void
    {
        $this->additionalViewData = $viewData;
    }
}
