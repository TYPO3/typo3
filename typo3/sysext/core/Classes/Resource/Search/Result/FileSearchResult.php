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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Resource\Search\FileSearchQuery;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Represents a search result for a given search query
 * being an iterable and countable list of file objects.
 */
class FileSearchResult implements FileSearchResultInterface
{
    /**
     * @var FileSearchDemand
     */
    private $searchDemand;

    /**
     * @var array
     */
    private $result;

    /**
     * @var int
     */
    private $resultCount;

    public function __construct(FileSearchDemand $searchDemand)
    {
        $this->searchDemand = $searchDemand;
    }

    /**
     * @return int
     * @see Countable::count()
     */
    public function count(): int
    {
        if ($this->resultCount !== null) {
            return $this->resultCount;
        }

        $this->resultCount = (int)FileSearchQuery::createCountForSearchDemand($this->searchDemand)->execute()->fetchOne();

        return $this->resultCount;
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

    /**
     * Perform the SQL query and apply filters on the resulting identifiers
     */
    private function initialize(): void
    {
        if ($this->result !== null) {
            return;
        }
        $this->result = FileSearchQuery::createForSearchDemand($this->searchDemand)->execute()->fetchAllAssociative();
        $this->resultCount = count($this->result);
        $this->result = array_map(
            static function (array $fileRow) {
                return GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($fileRow['uid'], $fileRow);
            },
            $this->result
        );
    }
}
