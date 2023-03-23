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

namespace TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchProviderInterface;

/**
 * Holds necessary data to query data from a search provider
 *
 * @internal may change in further iterations, do not rely on it
 */
class SearchDemand
{
    public const DEFAULT_LIMIT = 20;

    /**
     * @var DemandProperty[] $demandProperties
     */
    protected array $demandProperties = [];

    /**
     * @param DemandProperty[] $demandProperties
     */
    final public function __construct(array $demandProperties = [])
    {
        $this->demandProperties = array_reduce($demandProperties, static function (array $result, DemandProperty $item) {
            $result[$item->getName()->name] = $item;
            return $result;
        }, []);
    }

    public function getProperty(DemandPropertyName $demandPropertyName): ?DemandProperty
    {
        return $this->demandProperties[$demandPropertyName->name] ?? null;
    }

    /**
     * @return DemandProperty[]
     */
    public function getProperties(): array
    {
        return $this->demandProperties;
    }

    public function getQuery(): string
    {
        return $this->getProperty(DemandPropertyName::query)?->getValue() ?? '';
    }

    public function getLimit(): int
    {
        return (int)($this->getProperty(DemandPropertyName::limit)?->getValue() ?? self::DEFAULT_LIMIT);
    }

    public function getOffset(): int
    {
        return (int)($this->getProperty(DemandPropertyName::offset)?->getValue() ?? 0);
    }

    /**
     * @return class-string<SearchProviderInterface>[]
     */
    public function getSearchProviders(): array
    {
        return $this->getProperty(DemandPropertyName::searchProviders)?->getValue() ?? [];
    }

    public static function fromRequest(ServerRequestInterface $request): static
    {
        $demandProperties = [];
        foreach (DemandPropertyName::cases() as $demandProperty) {
            $demandPropertyName = $demandProperty->name;
            $valueFromRequest = $request->getParsedBody()[$demandPropertyName] ?? $request->getQueryParams()[$demandPropertyName] ?? null;
            if ($valueFromRequest !== null) {
                $demandProperties[] = new DemandProperty($demandProperty, $valueFromRequest);
            }
        }

        return new static($demandProperties);
    }
}
