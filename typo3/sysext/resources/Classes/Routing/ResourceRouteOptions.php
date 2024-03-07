<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Routing;

use TYPO3\CMS\Resources\Definition\Metadata\VersionInterface;
use TYPO3\CMS\Resources\Definition\MetadataInterface;
use TYPO3\CMS\Resources\Message\Numerus;

readonly class ResourceRouteOptions implements ResourceRouteOptionsInterface
{

    public function __construct(
        private Numerus $numerus,
        private VersionInterface $version,
        private MetadataInterface $definition
    )
    {}

    public function getNumerus(): Numerus
    {
        return $this->numerus;
    }

    public function getVersion(): VersionInterface
    {
        return $this->version;
    }

    public function getDefinition(): MetadataInterface
    {
        return $this->definition;
    }


}
