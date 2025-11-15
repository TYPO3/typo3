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

namespace TYPO3\CMS\Form\Domain\DTO;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Search criteria for filtering and sorting form lists
 *
 * Follows TYPO3 Demand pattern naming conventions:
 * - searchTerm: Text to search for in form properties
 * - orderField: Field name to sort by
 * - orderDirection: Sort direction ('asc' or 'desc')
 * - limit: Maximum number of results
 *
 * @internal
 */
final readonly class SearchCriteria
{
    private const ORDER_ASCENDING = 'asc';
    private const ORDER_DESCENDING = 'desc';
    private const DEFAULT_ORDER_FIELD = 'name';
    private const ORDER_FIELDS = ['name', 'identifier', 'persistenceIdentifier', 'prototypeName'];

    public string $orderField;
    public string $orderDirection;

    public function __construct(
        public ?string $searchTerm = null,
        ?string $orderField = null,
        ?string $orderDirection = null,
        public ?int $limit = null,
    ) {
        // Validate and normalize orderField
        $this->orderField = in_array($orderField, self::ORDER_FIELDS, true)
            ? $orderField
            : self::DEFAULT_ORDER_FIELD;

        // Validate and normalize orderDirection
        $this->orderDirection = in_array($orderDirection, [self::ORDER_ASCENDING, self::ORDER_DESCENDING], true)
            ? $orderDirection
            : self::ORDER_ASCENDING;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            searchTerm: $data['searchTerm'] ?? null,
            orderField: $data['orderField'] ?? null,
            orderDirection: $data['orderDirection'] ?? null,
            limit: $data['limit'] ?? null,
        );
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody() ?? [];

        return new self(
            searchTerm: $queryParams['searchTerm'] ?? $parsedBody['searchTerm'] ?? null,
            orderField: $queryParams['orderField'] ?? $parsedBody['orderField'] ?? null,
            orderDirection: $queryParams['orderDirection'] ?? $parsedBody['orderDirection'] ?? null,
            limit: isset($queryParams['limit']) ? (int)$queryParams['limit'] : (isset($parsedBody['limit']) ? (int)$parsedBody['limit'] : null),
        );
    }

    public function getOrderField(): string
    {
        return $this->orderField;
    }

    public function getOrderDirection(): string
    {
        return $this->orderDirection;
    }

    public function getDefaultOrderDirection(): string
    {
        return self::ORDER_ASCENDING;
    }

    public function getReverseOrderDirection(): string
    {
        return $this->orderDirection === self::ORDER_ASCENDING
            ? self::ORDER_DESCENDING
            : self::ORDER_ASCENDING;
    }

    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }

    public function hasSearchTerm(): bool
    {
        return $this->searchTerm !== null && $this->searchTerm !== '';
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function hasLimit(): bool
    {
        return $this->limit !== null && $this->limit > 0;
    }

    /**
     * Check if any filter/search constraints are set
     */
    public function hasConstraints(): bool
    {
        return $this->hasSearchTerm() || $this->hasLimit();
    }

    public function getParameters(): array
    {
        $parameters = [];
        if ($this->hasSearchTerm()) {
            $parameters['searchTerm'] = $this->searchTerm;
        }
        if ($this->hasLimit()) {
            $parameters['limit'] = $this->limit;
        }
        $parameters['orderField'] = $this->orderField;
        $parameters['orderDirection'] = $this->orderDirection;
        return $parameters;
    }
}
