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

namespace TYPO3\CMS\Fluid\Event;

use TYPO3Fluid\Fluid\Core\Component\ComponentDefinition;

/**
 * Event to modify the static definition of a Fluid component before the
 * definition is written to cache. The definition must not have any
 * dependencies on runtime information, such as the request.
 */
final class ModifyComponentDefinitionEvent
{
    public function __construct(
        private readonly string $namespace,
        private ComponentDefinition $componentDefinition
    ) {}

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getComponentDefinition(): ComponentDefinition
    {
        return $this->componentDefinition;
    }

    public function setComponentDefinition(ComponentDefinition $componentDefinition): void
    {
        $this->componentDefinition = $componentDefinition;
    }
}
