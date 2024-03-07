<?php

namespace TYPO3\CMS\Resources\Routing;

use TYPO3\CMS\Resources\Definition\Metadata\VersionInterface;
use TYPO3\CMS\Resources\Definition\MetadataInterface;
use TYPO3\CMS\Resources\Message\Numerus;

interface ResourceRouteOptionsInterface
{

    public function getNumerus(): Numerus;

    public function getVersion(): VersionInterface;

    public function getDefinition(): MetadataInterface;
}
