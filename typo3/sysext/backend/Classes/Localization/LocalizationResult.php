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

use TYPO3\CMS\Backend\Localization\Finisher\LocalizationFinisherInterface;

/**
 * Result object for localization operations
 *
 * Contains the outcome of a localization operation and optional finisher
 * that tells the frontend what to do next (e.g., redirect, load a module, reload).
 *
 * @internal
 */
final readonly class LocalizationResult implements \JsonSerializable
{
    /**
     * @param bool $success Whether the localization was successful
     * @param LocalizationFinisherInterface|null $finisher Finisher to execute after localization (required for success)
     * @param array<string> $errors Array of error messages (if any)
     */
    public function __construct(
        public bool $success = true,
        public ?LocalizationFinisherInterface $finisher = null,
        public array $errors = []
    ) {}

    /**
     * Create a successful result
     */
    public static function success(
        LocalizationFinisherInterface $finisher
    ): self {
        return new self(
            success: true,
            finisher: $finisher
        );
    }

    /**
     * Create a failed result with error messages
     *
     * @param array<string> $errors
     */
    public static function error(array $errors): self
    {
        return new self(
            success: false,
            errors: $errors
        );
    }

    /**
     * Check if the result has errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if the result is successful
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Convert to array for JSON serialization
     */
    public function jsonSerialize(): array
    {
        $data = [
            'success' => $this->success,
        ];

        if ($this->finisher !== null) {
            $data['finisher'] = $this->finisher->jsonSerialize();
        }

        if (!empty($this->errors)) {
            $data['errors'] = $this->errors;
        }

        return $data;
    }
}
