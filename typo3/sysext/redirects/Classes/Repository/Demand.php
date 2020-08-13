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

namespace TYPO3\CMS\Redirects\Repository;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Demand Object for filtering redirects in the backend module
 * @internal
 */
class Demand
{
    protected const ORDER_DESCENDING = 'desc';
    protected const ORDER_ASCENDING = 'asc';
    protected const DEFAULT_ORDER_FIELD = 'source_host';
    protected const DEFAULT_SECONDARY_ORDER_FIELD = 'source_host';

    /**
     * @var string
     */
    protected $orderField;

    /**
     * @var string
     */
    protected $orderDirection;

    /**
     * @var string
     */
    protected $sourceHost;

    /**
     * @var string
     */
    protected $sourcePath;

    /**
     * @var string
     */
    protected $target;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var int
     */
    protected $limit = 50;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var string
     */
    protected $secondaryOrderField;

    public function __construct(
        int $page = 1,
        string $orderField = self::DEFAULT_ORDER_FIELD,
        string $orderDirection = self::ORDER_ASCENDING,
        string $sourceHost = '',
        string $sourcePath = '',
        string $target = '',
        int $statusCode = 0
    ) {
        $this->page = $page;
        $this->orderField = $orderField;
        if (!in_array($orderDirection, [self::ORDER_DESCENDING, self::ORDER_ASCENDING])) {
            $orderDirection = self::ORDER_ASCENDING;
        }
        $this->orderDirection = $orderDirection;
        $this->sourceHost = $sourceHost;
        $this->sourcePath = $sourcePath;
        $this->target = $target;
        $this->statusCode = $statusCode;
        $this->secondaryOrderField = $this->orderField === self::DEFAULT_ORDER_FIELD ? self::DEFAULT_SECONDARY_ORDER_FIELD : '';
    }

    /**
     * Creates a Demand object from the current request.
     */
    public static function createFromRequest(ServerRequestInterface $request): Demand
    {
        $page = (int)($request->getQueryParams()['page'] ?? $request->getParsedBody()['page'] ?? 1);
        $orderField = $request->getQueryParams()['orderField'] ?? $request->getParsedBody()['orderField'] ?? self::DEFAULT_ORDER_FIELD;
        $orderDirection = $request->getQueryParams()['orderDirection'] ?? $request->getParsedBody()['orderDirection'] ?? self::ORDER_ASCENDING;
        $demand = $request->getQueryParams()['demand'] ?? $request->getParsedBody()['demand'];
        if (empty($demand)) {
            return new self($page, $orderField, $orderDirection);
        }
        $sourceHost = $demand['source_host'] ?? '';
        $sourcePath = $demand['source_path'] ?? '';
        $statusCode = (int)($demand['target_statuscode'] ?? 0);
        $target = $demand['target'] ?? '';
        return new self($page, $orderField, $orderDirection, $sourceHost, $sourcePath, $target, $statusCode);
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
        return $this->orderDirection === self::ORDER_ASCENDING ? self::ORDER_DESCENDING : self::ORDER_ASCENDING;
    }

    public function hasSecondaryOrdering(): bool
    {
        return $this->secondaryOrderField !== '';
    }

    public function getSecondaryOrderField(): string
    {
        return $this->secondaryOrderField;
    }

    public function getSourceHost(): string
    {
        return $this->sourceHost;
    }

    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function hasSourceHost(): bool
    {
        return $this->sourceHost !== '';
    }

    public function hasSourcePath(): bool
    {
        return $this->sourcePath !== '';
    }

    public function hasTarget(): bool
    {
        return $this->target !== '';
    }

    public function hasStatusCode(): bool
    {
        return $this->statusCode !== 0;
    }

    public function hasConstraints(): bool
    {
        return $this->hasSourcePath()
            || $this->hasSourceHost()
            || $this->hasTarget();
    }

    /**
     * The current Page of the paginated redirects
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * Offset for the current set of records
     */
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    public function getParameters(): array
    {
        $parameters = [];
        if ($this->hasSourcePath()) {
            $parameters['source_path'] = $this->getSourcePath();
        }
        if ($this->hasSourceHost()) {
            $parameters['source_host'] = $this->getSourceHost();
        }
        if ($this->hasTarget()) {
            $parameters['target'] = $this->getTarget();
        }
        if ($this->hasStatusCode()) {
            $parameters['target_statuscode'] = $this->getStatusCode();
        }
        return $parameters;
    }
}
