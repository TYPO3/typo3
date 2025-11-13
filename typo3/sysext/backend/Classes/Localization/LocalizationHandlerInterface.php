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

namespace TYPO3\CMS\Backend\Localization;

/**
 * Interface for localization handlers that perform the actual localization logic
 *
 * Handlers are responsible for executing the localization based on the selected mode
 *
 * @internal This API is not yet stable and may change before the LTS release
 */
interface LocalizationHandlerInterface
{
    /**
     * Get a unique identifier for this handler
     *
     * @return string Unique identifier (e.g., 'manual', 'deepl', 'google_translate')
     */
    public function getIdentifier(): string;

    /**
     * Get a human-readable label for this handler
     *
     * @return string Label that will be displayed in the UI (can be a translation key LLL:...)
     */
    public function getLabel(): string;

    /**
     * Get a description of what this handler does
     *
     * @return string Description shown to help users choose the right handler (can be a translation key LLL:...)
     */
    public function getDescription(): string;

    /**
     * Get the icon identifier for this handler
     *
     * @return string Icon identifier that can be resolved by the IconFactory
     */
    public function getIconIdentifier(): string;

    /**
     * Check if this handler is available for the given localization context
     *
     * This is a pre-flight check before showing the handler to the user.
     * It receives the same context as processLocalization() to determine if
     * the handler can process this specific localization request.
     *
     * Handlers can use this to determine if they support:
     * - Specific record types (e.g., only pages or tt_content)
     * - Specific source/target language combinations
     * - Specific localization modes (copy vs translate)
     * - Other contextual requirements (e.g., API keys configured, specific records)
     *
     * @return bool True if this handler can process the localization in this context
     */
    public function isAvailable(LocalizationInstructions $instructions): bool;

    /**
     * Process the localization for the given records
     *
     * @return LocalizationResult The result of the localization operation
     */
    public function processLocalization(LocalizationInstructions $instructions): LocalizationResult;
}
