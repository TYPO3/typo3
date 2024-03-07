<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Definition;

use Doctrine\Common\Collections\Collection;
use TYPO3\CMS\Resources\Definition\Metadata\GroupInterface;
use TYPO3\CMS\Resources\Definition\Metadata\NamesInterface;
use TYPO3\CMS\Resources\Definition\Metadata\ScopeInterface;
use TYPO3\CMS\Resources\Definition\Metadata\VersionInterface;

final readonly class Metadata implements MetadataInterface
{

    private readonly ?VersionInterface $preferredVersion;

    public function __construct(
        private string         $id,
        private GroupInterface $group,
        private NamesInterface $names,
        private ScopeInterface $scope,
        private \ArrayObject   $versions
    )
    {
        /** @var VersionInterface $version */
        foreach ($this->versions as $version) {
            if ($version->isServed()) {
                $this->preferredVersion = $version;
                break;
            }
        }
    }

    public function getGroup(): GroupInterface
    {
        return $this->group;
    }

    public function getNames(): NamesInterface
    {
        return $this->names;
    }

    public function getScope(): ScopeInterface
    {
        return $this->scope;
    }

    /**
     * @return \ArrayObject|VersionInterface[]
     */
    public function getVersions(): \ArrayObject
    {
        return $this->versions;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPreferredVersion(): ?VersionInterface
    {
        return $this->preferredVersion;
    }
}
