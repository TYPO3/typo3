<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Resource\Search;

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

use TYPO3\CMS\Core\Resource\Folder;

/**
 * Immutable value object that represents a search demand for files.
 */
class FileSearchDemand
{
    /**
     * @var string|null
     */
    private $searchTerm;

    /**
     * @var Folder|null
     */
    private $folder;

    /**
     * @var int|null
     */
    private $firstResult;

    /**
     * @var int|null
     */
    private $maxResults;

    /**
     * @var array|null
     */
    private $searchFields;

    /**
     * @var array|null
     */
    private $orderings;

    /**
     * @var bool
     */
    private $recursive = false;

    /**
     * Only factory methods are allowed to be used to create this object
     *
     * @param string|null $searchTerm
     */
    private function __construct(string $searchTerm = null)
    {
        $this->searchTerm = $searchTerm;
    }

    public static function create(): self
    {
        return new self();
    }

    public static function createForSearchTerm(string $searchTerm): self
    {
        return new self($searchTerm);
    }

    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function getFirstResult(): ?int
    {
        return $this->firstResult;
    }

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    public function getSearchFields(): ?array
    {
        return $this->searchFields;
    }

    public function getOrderings(): ?array
    {
        return $this->orderings;
    }

    public function isRecursive(): bool
    {
        return $this->recursive;
    }

    public function withSearchTerm(string $searchTerm): self
    {
        $demand = clone $this;
        $demand->searchTerm = $searchTerm;

        return $demand;
    }

    public function withFolder(Folder $folder): self
    {
        $demand = clone $this;
        $demand->folder = $folder;

        return $demand;
    }

    /**
     * Requests the position of the first result to retrieve (the "offset").
     * Same as in QueryBuilder it is the index of the result set, with 0 being the first result.
     *
     * @param int $firstResult
     * @return FileSearchDemand
     */
    public function withStartResult(int $firstResult): self
    {
        $demand = clone $this;
        $demand->firstResult = $firstResult;

        return $demand;
    }

    public function withMaxResults(int $maxResults): self
    {
        $demand = clone $this;
        $demand->maxResults = $maxResults;

        return $demand;
    }

    public function addSearchField(string $tableName, string $field): self
    {
        $demand = clone $this;
        $demand->searchFields[$tableName] = $field;

        return $demand;
    }

    public function addOrdering(string $tableName, string $fieldName, string $direction = 'ASC'): self
    {
        $demand = clone $this;
        $demand->orderings[] = [$tableName, $fieldName, $direction];

        return $demand;
    }

    public function withRecursive(): self
    {
        $demand = clone $this;
        $demand->recursive = true;

        return $demand;
    }
}
