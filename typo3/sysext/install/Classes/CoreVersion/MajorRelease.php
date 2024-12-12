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

namespace TYPO3\CMS\Install\CoreVersion;

class MajorRelease
{
    public function __construct(
        protected readonly string $version,
        protected readonly ?string $lts,
        protected readonly string $title,
        protected readonly MaintenanceWindow $maintenanceWindow
    ) {}

    public static function fromApiResponse(array $response): self
    {
        $maintenanceWindow = MaintenanceWindow::fromApiResponse($response);
        $ltsVersion = isset($response['lts']) ? (string)$response['lts'] : null;

        return new self((string)($response['version'] ?? ''), $ltsVersion, (string)($response['title'] ?? ''), $maintenanceWindow);
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getLts(): ?string
    {
        return $this->lts;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMaintenanceWindow(): MaintenanceWindow
    {
        return $this->maintenanceWindow;
    }
}
