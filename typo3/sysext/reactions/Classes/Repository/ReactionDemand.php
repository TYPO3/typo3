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
    protected const ORDER_FIELDS = ['name', 'reaction_type', 'impersonate_user', 'last_called'];
    protected const CALLED = ['ever', 'never'];

    protected int $limit = 15;

    public function __construct(
        protected int $page = 1,
        protected string $orderField = self::DEFAULT_ORDER_FIELD,
        protected string $orderDirection = self::ORDER_ASCENDING,
        protected string $name = '',
        protected string $reactionType = '',
        protected bool $onlyEnabled = false,
        protected int $impersonateUser = 0,
        protected string $called = '',
    ) {
        if (!in_array($orderField, self::ORDER_FIELDS, true)) {
            $orderField = self::DEFAULT_ORDER_FIELD;
        }
        $this->orderField = $orderField;
        if (!in_array($orderDirection, [self::ORDER_DESCENDING, self::ORDER_ASCENDING], true)) {
            $orderDirection = self::ORDER_ASCENDING;
        }
        $this->orderDirection = $orderDirection;
        $this->impersonateUser = max(0, $impersonateUser);
        if (!in_array($called, self::CALLED, true)) {
            $called = '';
        }
        $this->called = $called;
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
        $onlyEnabled = ($demand['only_enabled'] ?? null) === '1';
        $impersonateUser = is_scalar($demand['impersonate_user'] ?? null) ? (int)$demand['impersonate_user'] : 0;
        $called = is_string($demand['called'] ?? null) ? $demand['called'] : '';
        return new self($page, $orderField, $orderDirection, $name, $reactionType, $onlyEnabled, $impersonateUser, $called);
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

    public function isOnlyEnabled(): bool
    {
        return $this->onlyEnabled;
    }

    public function getImpersonateUser(): int
    {
        return $this->impersonateUser;
    }

    public function hasImpersonateUser(): bool
    {
        return $this->impersonateUser > 0;
    }

    public function getCalled(): string
    {
        return $this->called;
    }

    public function hasCalled(): bool
    {
        return $this->called !== '';
    }

    public function hasConstraints(): bool
    {
        return $this->hasName()
            || $this->hasReactionType()
            || $this->isOnlyEnabled()
            || $this->hasImpersonateUser()
            || $this->hasCalled();
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function withPage(int $page): self
    {
        $demand = clone $this;
        $demand->page = $page;
        return $demand;
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
        if ($this->isOnlyEnabled()) {
            $parameters['only_enabled'] = '1';
        }
        if ($this->hasImpersonateUser()) {
            $parameters['impersonate_user'] = $this->getImpersonateUser();
        }
        if ($this->hasCalled()) {
            $parameters['called'] = $this->getCalled();
        }
        return $parameters;
    }
}
