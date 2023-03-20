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

namespace TYPO3\CMS\Filelist\Matcher;

use TYPO3\CMS\Core\Resource\ResourceInterface;

/**
 * @internal
 */
class ResourceMatcher implements MatcherInterface
{
    /**
     * @var ResourceInterface[]
     */
    protected array $resources = [];

    /**
     * @param ResourceInterface[] $resources
     */
    public function setResources(array $resources): self
    {
        $this->resources = $resources;

        return $this;
    }

    public function addResource(ResourceInterface $resource): self
    {
        $this->resources[] = $resource;

        return $this;
    }

    public function supports(mixed $item): bool
    {
        return $item instanceof ResourceInterface;
    }

    public function match(mixed $item): bool
    {
        return in_array($item, $this->resources);
    }
}
