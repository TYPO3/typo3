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
 * The outcome of CreateRecordDryRun. It wraps the CreateRecordResolution, which only knows
 * the payload and the field map, with the obstacles outside of it, such as a disabled
 * reaction or a missing page permission, and with the username and field labels the
 * backend module displays.
 *
 * @internal This is a specific reaction implementation and is not considered part of the Public TYPO3 API.
 */
final readonly class CreateRecordDryRunResult
{
    /**
     * @param CreateRecordResolution|null $resolution null where there was no payload to resolve
     * @param list<DryRunObstacle> $obstacles
     * @param string $username the impersonated user, empty where there is none
     * @param array<string, string> $fieldLabels the TCA label per field of the resolution
     */
    public function __construct(
        public ?CreateRecordResolution $resolution,
        public array $obstacles,
        public string $username,
        public array $fieldLabels,
    ) {}
}
