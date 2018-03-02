<?php
declare(strict_types=1);
namespace TYPO3\CMS\Frontend\Tests\Unit\View\Fixtures;

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
use TYPO3\CMS\Frontend\AdminPanel\AdminPanelModuleInterface;

class AdminPanelDisabledModuleFixture implements AdminPanelModuleInterface
{

    /**
     * Additional JavaScript code for this module
     * (you should only use vanilla JS here, as you cannot
     * rely on the web site providing a specific framework)
     *
     * @return string
     */
    public function getAdditionalJavaScriptCode(): string
    {
        return '';
    }

    /**
     * Module content as rendered HTML
     *
     * @return string
     */
    public function getContent(): string
    {
        return '';
    }

    /**
     * Identifier for this module,
     * for example "preview" or "cache"
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return '';
    }

    /**
     * Module label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return '';
    }

    /**
     * Initialize the module - runs early in a TYPO3 request
     */
    public function initializeModule(): void
    {
        throw new \RuntimeException('I should not be initialized.', 1519999375);
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
        return false;
    }

    /**
     * Module is open
     * -> module is enabled
     * -> module panel is shown and open
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return true;
    }

    /**
     * Module is shown
     * -> module is enabled
     * -> module panel should be displayed
     *
     * @return bool
     */
    public function isShown(): bool
    {
        return true;
    }

    /**
     * Executed on saving / submit of the configuration form
     * Can be used to react to changed settings
     * (for example: clearing a specific cache)
     *
     * @param array $input
     */
    public function onSubmit(array $input): void
    {
    }

    /**
     * Does this module need a form submit?
     *
     * @return bool
     */
    public function showFormSubmitButton(): bool
    {
        return true;
    }
}
