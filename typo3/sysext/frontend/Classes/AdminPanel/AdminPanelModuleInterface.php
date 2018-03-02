<?php
declare(strict_types=1);

namespace TYPO3\CMS\Frontend\AdminPanel;

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

/**
 * Interface for admin panel modules registered via EXTCONF
 *
 * @internal until API is stable
 */
interface AdminPanelModuleInterface
{
    /**
     * Identifier for this module,
     * for example "preview" or "cache"
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Module label
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Module content as rendered HTML
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * Does this module need a form submit?
     *
     * @return bool
     */
    public function showFormSubmitButton(): bool;

    /**
     * Additional JavaScript code for this module
     * (you should only use vanilla JS here, as you cannot
     * rely on the web site providing a specific framework)
     *
     * @return string
     */
    public function getAdditionalJavaScriptCode(): string;
}
