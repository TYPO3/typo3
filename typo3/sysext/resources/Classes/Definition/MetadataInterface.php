<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Definition;

use TYPO3\CMS\Resources\Definition\Metadata\GroupInterface;
use TYPO3\CMS\Resources\Definition\Metadata\NamesInterface;
use TYPO3\CMS\Resources\Definition\Metadata\ResourceSpecificationInterface;
use TYPO3\CMS\Resources\Definition\Metadata\ScopeInterface;
use TYPO3\CMS\Resources\Definition\Metadata\VersionCollection;
use TYPO3\CMS\Resources\Definition\Metadata\VersionInterface;
use TYPO3\CMS\Resources\ResourceInterface;

interface MetadataInterface extends ResourceInterface
{
    public function getGroup(): GroupInterface;

    public function getNames(): NamesInterface;

    public function getScope(): ScopeInterface;

    /**
     * @return VersionInterface[]
     */
    public function getVersions(): iterable;

    public function getPreferredVersion(): ?VersionInterface;

}
