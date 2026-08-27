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

namespace TYPO3\CMS\Core\Package\Resource;

use TYPO3\CMS\Core\Package\Resource\Definition\PublicResourceDefinition;
use TYPO3\CMS\Core\Package\Resource\Definition\ResourceDefinition;
use TYPO3\CMS\Core\Package\Resource\Definition\ResourceDefinitionInterface;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceDefinitionNotFoundException;

/**
 * @internal This is subject to change during v14 development. Do not use.
 */
final readonly class ResourceCollection implements ResourceCollectionInterface
{
    /**
     * @param list<ResourceDefinitionInterface> $resourceDefinitions
     */
    public function __construct(
        private array $resourceDefinitions = [],
        private ?string $iconIdentifier = null,
        private bool $createResourcesOnTheFly = true,
    ) {}

    public function definitionForPath(string $relativePath): ResourceDefinitionInterface
    {
        foreach ($this->resourceDefinitions as $config) {
            if ($config->matches($relativePath)) {
                return $config;
            }
        }
        if ($this->createResourcesOnTheFly) {
            trigger_error('Resource identifiers outside ouf Resources/Private, Resources/Public or Configuration folder of extensions are deprecated. Define custom resources if required. Used resource: ' . $relativePath, E_USER_DEPRECATED);
            return new ResourceDefinition($relativePath);
        }
        throw new SystemResourceDefinitionNotFoundException(sprintf('Project path "%s" is not allowed. Define custom resources if required.', $relativePath), 1763381519);
    }

    public function isPublicPath(string $relativePath): bool
    {
        return $this->definitionForPath($relativePath) instanceof PublicResourceDefinition;
    }

    public function getPublicResourceDefinitions(): array
    {
        return array_filter(
            array_map(
                static fn(ResourceDefinitionInterface $definition) => $definition instanceof PublicResourceDefinition ? $definition : null,
                $this->resourceDefinitions,
            )
        );
    }

    public function getPackageIcon(): ?string
    {
        return $this->iconIdentifier;
    }
}
