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

namespace TYPO3\CMS\Backend\Localization\Finisher;

/**
 * Interface for finishers after localization
 *
 * Finishers tell the frontend what to do after a successful localization,
 * such as redirecting to a page, loading a JavaScript module, or executing custom logic.
 *
 * @internal This API is not yet stable and may change before the LTS release
 */
interface LocalizationFinisherInterface extends \JsonSerializable
{
    /**
     * Get the finisher type identifier
     *
     * This is used by the frontend to determine which handler to use
     * (e.g., 'redirect', 'noop', 'reload')
     */
    public function getIdentifier(): string;

    /**
     * Get the JavaScript module path for this finisher
     *
     * This module will be dynamically loaded by the frontend to handle
     * the finisher's rendering and execution logic.
     *
     * @return string The module path (e.g., '@typo3/backend/localization/finisher/redirect-finisher.js')
     */
    public function getModule(): string;

    /**
     * Get the finisher data as an array
     *
     * This data will be passed to the frontend handler
     */
    public function getData(): array;

    /**
     * Get all labels needed by this finisher's JavaScript module
     *
     * Implementations should provide pre-processed UI strings that will be passed through
     * to the frontend without any server-side resolution. The finisher implementation is
     * responsible for translating and formatting these strings.
     *
     * The array should be a simple key-value map where keys are arbitrary identifiers
     * used by the JavaScript module, and values are the ready-to-display strings.
     *
     * @return array<string, string> Label identifier => Ready-to-display string
     */
    public function getLabels(): array;
}
