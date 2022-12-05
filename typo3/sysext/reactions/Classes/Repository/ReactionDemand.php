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

namespace TYPO3\CMS\Reactions\Repository;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Demand Object for filtering reactions in the backend module
 *
 * @internal
 */
class ReactionDemand
{
    protected const ORDER_DESCENDING = 'desc';
    protected const ORDER_ASCENDING = 'asc';
    protected const DEFAULT_ORDER_FIELD = 'name';
    protected const ORDER_FIELDS = ['name', 'reaction_type'];

    protected int $limit = 15;

    public function __construct(
        protected int $page = 1,
        protected string $orderField = self::DEFAULT_ORDER_FIELD,
        protected string $orderDirection = self::ORDER_ASCENDING,
        protected string $name = '',
        protected string $reactionType = ''
    ) {
        if (!in_array($orderField, self::ORDER_FIELDS, true)) {
            $orderField = self::DEFAULT_ORDER_FIELD;
        }
        $this->orderField = $orderField;
        if (!in_array($orderDirection, [self::ORDER_DESCENDING, self::ORDER_ASCENDING], true)) {
            $orderDirection = self::ORDER_ASCENDING;
        }
        $this->orderDirection = $orderDirection;
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        $page = (int)($request->getQueryParams()['page'] ?? $request->getParsedBody()['page'] ?? 1);
        $orderField = (string)($request->getQueryParams()['orderField'] ?? $request->getParsedBody()['orderField'] ?? self::DEFAULT_ORDER_FIELD);
        $orderDirection = (string)($request->getQueryParams()['orderDirection'] ?? $request->getParsedBody()['orderDirection'] ?? self::ORDER_ASCENDING);
        $demand = $request->getQueryParams()['demand'] ?? $request->getParsedBody()['demand'] ?? [];
        if (!is_array($demand) || $demand === []) {
            return new self($page, $orderField, $orderDirection);
        }
        $name = (string)($demand['name'] ?? '');
        $reactionType = (string)($demand['reaction_type'] ?? '');
        return new self($page, $orderField, $orderDirection, $name, $reactionType);
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

    public function getName(): string
    {
        return $this->name;
    }

    public function hasName(): bool
    {
        return $this->name !== '';
    }

    public function getReactionType(): string
    {
        return $this->reactionType;
    }

    public function hasReactionType(): bool
    {
        return $this->reactionType !== '';
    }

    public function hasConstraints(): bool
    {
        return $this->hasName()
            || $this->hasReactionType();
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    public function getParameters(): array
    {
        $parameters = [];
        if ($this->hasName()) {
            $parameters['name'] = $this->getName();
        }
        if ($this->hasReactionType()) {
            $parameters['reaction_type'] = $this->getReactionType();
        }
        return $parameters;
    }
}
