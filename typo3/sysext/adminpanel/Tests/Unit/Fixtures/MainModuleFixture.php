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

namespace TYPO3\CMS\Adminpanel\Tests\Unit\Fixtures;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ConfigurableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\OnSubmitActorInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\PageSettingsProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ShortInfoProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\SubmoduleProviderInterface;

class MainModuleFixture implements
    ModuleInterface,
    ShortInfoProviderInterface,
    SubmoduleProviderInterface,
    RequestEnricherInterface,
               ResourceProviderInterface,
    PageSettingsProviderInterface,
    OnSubmitActorInterface,
    ConfigurableInterface
{

    /**
     * Identifier for this module,
     * for example "preview" or "cache"
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'example';
    }

    /**
     * Module label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return 'Example Label';
    }

    /**
     * Module Icon identifier - needs to be registered in iconRegistry
     *
     * @return string
     */
    public function getIconIdentifier(): string
    {
        return 'actions-document-info';
    }

    /**
     * Displayed directly in the bar if module has content
     *
     * @return string
     */
    public function getShortInfo(): string
    {
        return 'short info';
    }

    /**
     * @return string
     */
    public function getPageSettings(): string
    {
        return 'example settings';
    }

    /**
     * Module is enabled
     * -> should be initialized
     * A module may be enabled but not shown
     * -> only the initializeModule() method
     * will be called
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Executed on saving / submit of the configuration form
     * Can be used to react to changed settings
     * (for example: clearing a specific cache)
     *
     * @param array $configurationToSave
     * @param ServerRequestInterface $request
     */
    public function onSubmit(array $configurationToSave, ServerRequestInterface $request): void
    {
    }

    /**
     * Returns a string array with javascript files that will be rendered after the module
     *
     * @return array
     */
    public function getJavaScriptFiles(): array
    {
        return [];
    }

    /**
     * Returns a string array with css files that will be rendered after the module
     *
     * @return array
     */
    public function getCssFiles(): array
    {
        return [];
    }

    /**
     * Set SubModules for current module
     *
     * @param ModuleInterface[] $subModules
     */
    public function setSubModules(array $subModules): void
    {
    }

    /**
     * Get SubModules for current module
     *
     * @return ModuleInterface[]
     */
    public function getSubModules(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function hasSubmoduleSettings(): bool
    {
        return false;
    }

    /**
     * Initialize the module - runs in the TYPO3 middleware stack at an early point
     * may manipulate the current request
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    public function enrich(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request;
    }
}
