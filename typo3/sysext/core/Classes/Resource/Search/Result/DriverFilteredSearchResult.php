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

namespace TYPO3\CMS\Core\Resource\Search\Result;

use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Decorator for a search result with files, which filters
 * the result based on given filters.
 */
class DriverFilteredSearchResult implements FileSearchResultInterface
{
    /**
     * @var FileSearchResultInterface
     */
    private $searchResult;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var callable[]
     */
    private $filters;

    /**
     * @var array
     */
    private $result;

    public function __construct(FileSearchResultInterface $searchResult, DriverInterface $driver, array $filters)
    {
        $this->searchResult = $searchResult;
        $this->driver = $driver;
        $this->filters = $filters;
    }

    /**
     * @return int
     * @see Countable::count()
     */
    public function count(): int
    {
        $this->initialize();

        return count($this->result);
    }

    /**
     * @return File
     * @see Iterator::current()
     */
    public function current(): File
    {
        $this->initialize();

        return current($this->result);
    }

    /**
     * @return int
     * @see Iterator::key()
     */
    public function key(): int
    {
        $this->initialize();

        return key($this->result);
    }

    /**
     * @see Iterator::next()
     */
    public function next(): void
    {
        $this->initialize();
        next($this->result);
    }

    /**
     * @see Iterator::rewind()
     */
    public function rewind(): void
    {
        $this->initialize();
        reset($this->result);
    }

    /**
     * @return bool
     * @see Iterator::valid()
     */
    public function valid(): bool
    {
        $this->initialize();

        return current($this->result) !== false;
    }

    private function initialize(): void
    {
        if ($this->result === null) {
            $this->result = $this->applyFilters(...iterator_to_array($this->searchResult));
        }
    }

    /**
     * Filter out identifiers by calling all attached filters
     *
     * @param File ...$files
     * @return array<int, File>
     */
    private function applyFilters(File ...$files): array
    {
        $filteredFiles = [];
        foreach ($files as $file) {
            $itemIdentifier = $file->getIdentifier();
            $itemName = PathUtility::basename($itemIdentifier);
            $parentIdentifier = PathUtility::dirname($itemIdentifier);
            $matches = true;
            foreach ($this->filters as $filter) {
                if (!is_callable($filter)) {
                    continue;
                }
                $result = $filter($itemName, $itemIdentifier, $parentIdentifier, [], $this->driver);
                // We use -1 as the "don't include“ return value, for historic reasons,
                // as call_user_func() used to return FALSE if calling the method failed.
                if ($result === -1) {
                    $matches = false;
                }
                if ($result === false) {
                    throw new \RuntimeException(
                        'Could not apply file/folder name filter ' . $filter[0] . '::' . $filter[1],
                        1543617278
                    );
                }
            }
            if ($matches) {
                $filteredFiles[] = $file;
            }
        }

        return $filteredFiles;
    }
}
