<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Routing;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Contains all resolved parameters when a page is resolved from a page path segment plus all fragments.
 */
class PageArguments implements RouteResultInterface
{
    /**
     * @var int
     */
    protected $pageId;

    /**
     * @var string
     */
    protected $pageType;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @var array
     */
    protected $staticArguments;

    /**
     * @var array
     */
    protected $dynamicArguments;

    /**
     * @var array
     */
    protected $routeArguments;

    /**
     * @var array
     */
    protected $queryArguments = [];

    /**
     * @var bool
     */
    protected $dirty = false;

    /**
     * @param int $pageId
     * @param string $pageType
     * @param array $routeArguments
     * @param array $staticArguments
     * @param array $remainingArguments
     */
    public function __construct(int $pageId, string $pageType, array $routeArguments, array $staticArguments = [], array $remainingArguments = [])
    {
        $this->pageId = $pageId;
        $this->pageType = $pageType;
        $this->routeArguments = $this->sort($routeArguments);
        $this->staticArguments = $this->sort($staticArguments);
        $this->arguments = $this->routeArguments;
        $this->updateDynamicArguments();
        if (!empty($remainingArguments)) {
            $this->updateQueryArguments($remainingArguments);
        }
    }

    /**
     * @return bool
     */
    public function areDirty(): bool
    {
        return $this->dirty;
    }

    /**
     * @return array
     */
    public function getRouteArguments(): array
    {
        return $this->routeArguments;
    }

    /**
     * @return int
     */
    public function getPageId(): int
    {
        return $this->pageId;
    }

    /**
     * @return string
     */
    public function getPageType(): string
    {
        return $this->pageType;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function get(string $name)
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return array
     */
    public function getStaticArguments(): array
    {
        return $this->staticArguments;
    }

    /**
     * @return array
     */
    public function getDynamicArguments(): array
    {
        return $this->dynamicArguments;
    }

    /**
     * @return array
     */
    public function getQueryArguments(): array
    {
        return $this->queryArguments;
    }

    /**
     * @param array $queryArguments
     * @return static
     * @internal this is internal due to the issue that a PageArgument should not be modified, but must be within TYPO3 Core currently.
     */
    public function withQueryArguments(array $queryArguments): self
    {
        $queryArguments = $this->sort($queryArguments);
        if ($this->queryArguments === $queryArguments) {
            return $this;
        }
        // in case query arguments would override route arguments,
        // the state is considered as dirty (since it's not distinct)
        // thus, route arguments take precedence over query arguments
        $additionalQueryArguments = $this->diff($queryArguments, $this->routeArguments);
        $dirty = $additionalQueryArguments !== $queryArguments;
        // apply changes
        $target = clone $this;
        $target->dirty = $this->dirty || $dirty;
        $target->queryArguments = $queryArguments;
        $target->arguments = array_replace_recursive($target->arguments, $additionalQueryArguments);
        $target->updateDynamicArguments();
        return $target;
    }

    /**
     * @param array $queryArguments
     */
    protected function updateQueryArguments(array $queryArguments)
    {
        $queryArguments = $this->sort($queryArguments);
        if ($this->queryArguments === $queryArguments) {
            return;
        }
        // in case query arguments would override route arguments,
        // the state is considered as dirty (since it's not distinct)
        // thus, route arguments take precedence over query arguments
        $additionalQueryArguments = $this->diff($queryArguments, $this->routeArguments);
        $dirty = $additionalQueryArguments !== $queryArguments;
        $this->dirty = $this->dirty || $dirty;
        $this->queryArguments = $queryArguments;
        $this->arguments = array_replace_recursive($this->arguments, $additionalQueryArguments);
        $this->updateDynamicArguments();
    }

    /**
     * Updates dynamic arguments based on definitions for static arguments.
     */
    protected function updateDynamicArguments(): void
    {
        $this->dynamicArguments = $this->diff(
            $this->arguments,
            $this->staticArguments
        );
    }

    /**
     * Cleans empty array recursively.
     *
     * @param array $array
     * @return array
     */
    protected function clean(array $array): array
    {
        foreach ($array as $key => &$item) {
            if (!is_array($item)) {
                continue;
            }
            if (!empty($item)) {
                $item = $this->clean($item);
            }
            if (empty($item)) {
                unset($array[$key]);
            }
        }
        return $array;
    }

    /**
     * Sorts array keys recursively.
     *
     * @param array $array
     * @return array
     */
    protected function sort(array $array): array
    {
        $array = $this->clean($array);
        ArrayUtility::naturalKeySortRecursive($array);
        return $array;
    }

    /**
     * Removes keys that are defined in $second from $first recursively.
     *
     * @param array $first
     * @param array $second
     * @return array
     */
    protected function diff(array $first, array $second): array
    {
        return ArrayUtility::arrayDiffAssocRecursive($first, $second);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $offset === 'pageId' || $offset === 'pageType' || isset($this->arguments[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if ($offset === 'pageId') {
            return $this->getPageId();
        }
        if ($offset === 'pageType') {
            return $this->getPageType();
        }
        return $this->arguments[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new \InvalidArgumentException('PageArguments cannot be modified.', 1538152266);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        throw new \InvalidArgumentException('PageArguments cannot be modified.', 1538152269);
    }
}
