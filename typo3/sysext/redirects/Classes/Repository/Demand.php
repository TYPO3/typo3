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
use Symfony\Component\Console\Input\InputInterface;

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
    protected const ORDER_FIELDS = ['source_host', 'source_path', 'lasthiton', 'hitcount', 'protected'];

    protected string $orderField;
    protected string $orderDirection;

    /**
     * @var string[]
     */
    protected array $sourceHosts;
    protected string $sourcePath;
    protected string $target;

    /**
     * @var int[]
     */
    protected array $statusCodes = [];
    protected int $limit = 50;
    protected int $page;
    protected string $secondaryOrderField;
    private int $maxHits;
    private ?\DateTimeInterface $olderThan;
    protected ?int $creationType = -1;

    public function __construct(
        int $page = 1,
        string $orderField = self::DEFAULT_ORDER_FIELD,
        string $orderDirection = self::ORDER_ASCENDING,
        array $sourceHosts = [],
        string $sourcePath = '',
        string $target = '',
        array $statusCodes = [],
        int $maxHits = 0,
        \DateTimeInterface $olderThan = null,
        ?int $creationType = -1
    ) {
        $this->page = $page;
        if (!in_array($orderField, self::ORDER_FIELDS, true)) {
            $orderField = self::DEFAULT_ORDER_FIELD;
        }
        $this->orderField = $orderField;
        if (!in_array($orderDirection, [self::ORDER_DESCENDING, self::ORDER_ASCENDING], true)) {
            $orderDirection = self::ORDER_ASCENDING;
        }
        $this->orderDirection = $orderDirection;
        $this->sourceHosts = $sourceHosts;
        $this->sourcePath = $sourcePath;
        $this->target = $target;
        $this->statusCodes = $statusCodes;
        $this->secondaryOrderField = $this->orderField === self::DEFAULT_ORDER_FIELD ? self::DEFAULT_SECONDARY_ORDER_FIELD : '';
        $this->maxHits = $maxHits;
        $this->olderThan = $olderThan;
        $this->creationType = $creationType;
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        $page = (int)($request->getQueryParams()['page'] ?? $request->getParsedBody()['page'] ?? 1);
        $orderField = $request->getQueryParams()['orderField'] ?? $request->getParsedBody()['orderField'] ?? self::DEFAULT_ORDER_FIELD;
        $orderDirection = $request->getQueryParams()['orderDirection'] ?? $request->getParsedBody()['orderDirection'] ?? self::ORDER_ASCENDING;
        $demand = $request->getQueryParams()['demand'] ?? $request->getParsedBody()['demand'] ?? [];
        if (empty($demand)) {
            return new self($page, $orderField, $orderDirection);
        }
        $sourceHost = $demand['source_host'] ?? '';
        $sourceHosts = $sourceHost ? [$sourceHost] : [];
        $sourcePath = $demand['source_path'] ?? '';
        $statusCode = (int)($demand['target_statuscode'] ?? 0);
        $statusCodes = $statusCode > 0 ? [$statusCode] : [];
        $target = $demand['target'] ?? '';
        $maxHits = (int)($demand['max_hits'] ?? 0);
        $creationType = isset($demand['creation_type']) ? ((int)$demand['creation_type']) : -1;
        return new self($page, $orderField, $orderDirection, $sourceHosts, $sourcePath, $target, $statusCodes, $maxHits, null, $creationType);
    }

    public static function fromCommandInput(InputInterface $input): self
    {
        return new self(
            1,
            self::DEFAULT_ORDER_FIELD,
            self::ORDER_ASCENDING,
            (array)$input->getOption('domain'),
            (string)$input->getOption('path'),
            '',
            (array)$input->getOption('statusCode'),
            $input->hasOption('hitCount') ? (int)$input->getOption('hitCount') : 0,
            $input->getOption('days')
                ? new \DateTimeImmutable($input->getOption('days') . ' days ago')
                : new \DateTimeImmutable(
                    '90 days ago'
                ),
            $input->getOption('creationType') !== null ? (int)($input->getOption('creationType')) : null
        );
    }

    public function getMaxHits(): int
    {
        return $this->maxHits;
    }

    public function hasMaxHits(): bool
    {
        return $this->maxHits > 0;
    }

    public function getOlderThan(): ?\DateTimeInterface
    {
        return $this->olderThan;
    }

    public function hasOlderThan(): bool
    {
        return $this->olderThan instanceof \DateTimeInterface;
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

    public function getFirstSourceHost(): string
    {
        return $this->sourceHosts[0] ?? '';
    }

    public function getSourceHosts(): ?array
    {
        return $this->sourceHosts === [] ? null : $this->sourceHosts;
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

    public function getCreationType(): ?int
    {
        return $this->creationType;
    }

    public function getFirstStatusCode(): int
    {
        return $this->statusCodes[0] ?? 0;
    }

    public function getStatusCodes(): array
    {
        return $this->statusCodes;
    }

    public function hasStatusCodes(): bool
    {
        return !empty($this->statusCodes);
    }

    public function hasSourceHosts(): bool
    {
        return !empty($this->sourceHosts);
    }

    public function hasSourcePath(): bool
    {
        return $this->sourcePath !== '';
    }

    public function hasTarget(): bool
    {
        return $this->target !== '';
    }

    public function hasCreationType(): bool
    {
        return $this->creationType !== null && $this->creationType !== -1;
    }

    public function hasConstraints(): bool
    {
        return $this->hasSourcePath()
            || $this->hasSourceHosts()
            || $this->hasTarget()
            || $this->hasStatusCodes()
            || $this->hasMaxHits()
            || $this->hasCreationType();
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
        if ($this->hasSourceHosts()) {
            $parameters['source_host'] = $this->getFirstSourceHost();
        }
        if ($this->hasTarget()) {
            $parameters['target'] = $this->getTarget();
        }
        if ($this->hasStatusCodes()) {
            $parameters['target_statuscode'] = $this->getFirstStatusCode();
        }
        if ($this->hasMaxHits()) {
            $parameters['max_hits'] = $this->getMaxHits();
        }
        if ($this->hasCreationType()) {
            $parameters['creation_type'] = $this->getCreationType();
        }
        return $parameters;
    }
}
