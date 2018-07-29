<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Tests\Unit\Fixtures;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\Modules\AdminPanelSubModuleInterface;

class SubModuleFixture implements AdminPanelSubModuleInterface
{

    /**
     * Initialize the module - runs early in a TYPO3 request
     *
     * @param ServerRequestInterface $request
     */
    public function initializeModule(ServerRequestInterface $request): void
    {
    }

    /**
     * Identifier for this Sub-module,
     * for example "preview" or "cache"
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'example-submodule';
    }

    /**
     * Sub-Module label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return 'Example SubModule';
    }

    /**
     * Sub-Module content as rendered HTML
     *
     * @return string
     */
    public function getContent(): string
    {
        return 'content';
    }

    /**
     * Settings as HTML form elements (without wrapping form tag or save button)
     *
     * @return string
     */
    public function getSettings(): string
    {
        return 'settings';
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
}
