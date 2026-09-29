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

namespace TYPO3\CMS\Reactions\Reaction;

/**
 * The record a create-record reaction composes from a payload, before anything is written.
 *
 * @internal This is a specific reaction implementation and is not considered part of the Public TYPO3 API.
 */
final readonly class CreateRecordResolution
{
    /**
     * @param array<string, string> $fields the values the record would be written with
     * @param array<string, string> $skippedFields a SkippedFieldReason value per field left out
     * @param string|null $error why no record would be written at all
     */
    public function __construct(
        public string $table,
        public int $storagePid,
        public array $fields,
        public array $skippedFields,
        public ?string $error = null,
    ) {}
}
