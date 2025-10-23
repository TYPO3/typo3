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

namespace TYPO3\CMS\Backend\Template\Components;

/**
 * Common interface for all renderable backend components (buttons, dropdown items, etc.).
 *
 * This interface provides the base contract for components that can be validated,
 * typed, and rendered as HTML. It is extended by more specific interfaces like
 * ButtonInterface and DropDownItemInterface.
 */
interface ComponentInterface extends \Stringable
{
    /**
     * Validates whether the component is properly configured and can be rendered.
     *
     * Each implementing class defines its own validation rules (e.g., required fields).
     *
     * @return bool True if the component is valid and can be rendered, false otherwise
     */
    public function isValid(): bool;

    /**
     * Returns the fully qualified class name as the component type identifier.
     *
     * This is used to identify the specific component type in validation and rendering.
     *
     * @return string The fully qualified class name (e.g., 'TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton')
     */
    public function getType(): string;

    /**
     * Renders the component as an HTML string.
     *
     * This method should only be called after validating the component with isValid().
     * The returned HTML is ready to be output to the browser.
     *
     * @return string The rendered HTML markup
     */
    public function render(): string;
}
