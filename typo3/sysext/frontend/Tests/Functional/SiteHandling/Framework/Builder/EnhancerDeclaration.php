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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder;

class EnhancerDeclaration implements Applicable, HasGenerateParameters, HasResolveArguments
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var array
     */
    private $configuration = [];

    /**
     * @var array
     */
    private $resolveArguments = [];

    /**
     * @var array
     */
    private $generateParameters = [];

    public static function create(string $identifier): self
    {
        return new static($identifier);
    }

    private function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return mixed
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return array
     */
    public function getResolveArguments(): array
    {
        return $this->resolveArguments;
    }

    /**
     * @return array
     */
    public function getGenerateParameters(): array
    {
        return $this->generateParameters;
    }

    public function withConfiguration(array $configuration, bool $merge = false): self
    {
        $target = clone $this;

        $target->configuration = $merge ? $this->applyMergedItems($this->configuration, $configuration) : $configuration;
        return $target;
    }

    public function withResolveArguments(array $resolveArguments, bool $merge = false): self
    {
        $target = clone $this;
        $target->resolveArguments = $merge ? $this->applyMergedItems($this->resolveArguments, $resolveArguments) : $resolveArguments;
        return $target;
    }

    public function withGenerateParameters(array $generateParameters, bool $merge = false): self
    {
        $target = clone $this;
        $target->generateParameters = $merge ? $this->applyMergedItems($this->generateParameters, $generateParameters) : $generateParameters;
        return $target;
    }

    public function describe(): string
    {
        return $this->identifier;
    }

    private function applyMergedItems(array $currentItems, array $additionalItems): array
    {
        if (empty($additionalItems)) {
            return $currentItems;
        }
        if ($this->hasOnlyNumericKeys($additionalItems)) {
            return array_merge($currentItems, $additionalItems);
        }
        return array_replace_recursive($currentItems, $additionalItems);
    }

    private function hasOnlyNumericKeys(array $items): bool
    {
        $numericItems = array_filter($items, 'is_int', ARRAY_FILTER_USE_KEY);
        return !empty($numericItems);
    }
}
