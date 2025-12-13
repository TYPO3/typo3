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

use TYPO3Fluid\Fluid\Core\Component\ComponentDefinitionProviderInterface;
use TYPO3Fluid\Fluid\Core\Component\ComponentTemplateResolverInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolverDelegateInterface;

/**
 * Event to provide additional variables to a Fluid component's template.
 * These variables should be static and must not have any dependencies on runtime
 * information, such as the request. Think of these variables as something that
 * could be provided as static autocomplete in IDEs: Prefixes based on component
 * name, global design tokens, ...
 *
 * @see RenderComponentEvent
 */
final class ProvideStaticVariablesToComponentEvent
{
    /**
     * @var array<string, mixed>
     */
    private $staticVariables = [];

    public function __construct(
        private readonly ViewHelperResolverDelegateInterface&ComponentDefinitionProviderInterface&ComponentTemplateResolverInterface $componentCollection,
        private readonly string $viewHelperName,
    ) {}

    public function getComponentCollection(): ViewHelperResolverDelegateInterface&ComponentDefinitionProviderInterface&ComponentTemplateResolverInterface
    {
        return $this->componentCollection;
    }

    public function getViewHelperName(): string
    {
        return $this->viewHelperName;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStaticVariables(): array
    {
        return $this->staticVariables;
    }

    /**
     * @param array<string, mixed> $staticVariables
     */
    public function setStaticVariables(array $staticVariables): void
    {
        $this->staticVariables = $staticVariables;
    }
}
