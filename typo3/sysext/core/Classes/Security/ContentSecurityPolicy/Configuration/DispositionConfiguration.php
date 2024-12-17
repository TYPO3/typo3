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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration;

/**
 * Represents a `csp.yaml` site configuration section for the disposition modes `enforce` and `report.
 *
 * @internal
 */
readonly class DispositionConfiguration
{
    public null|bool|string $reportingUrl;

    public function __construct(
        public bool $inheritDefault,
        public bool $includeResolutions,
        mixed $reportingUrl,
        public array $mutations = [],
        /** @var array<string, bool> $packages */
        public array $packages = [],
    ) {
        $this->reportingUrl = self::normalizeReportingUrl($reportingUrl);
    }

    public static function normalizeReportingUrl(mixed $reportingUrl): null|bool|string
    {
        if ($reportingUrl === null || is_bool($reportingUrl)) {
            return $reportingUrl;
        }
        if (!is_scalar($reportingUrl)) {
            return null;
        }
        if ($reportingUrl === 0 || $reportingUrl === '0') {
            return false;
        }
        if ($reportingUrl === 1 || $reportingUrl === '1') {
            return true;
        }
        return (string)$reportingUrl;
    }

    public function resolveEffectivePackages(string ...$packageNames): array
    {
        if ($this->packages === []) {
            return $packageNames;
        }

        $effectivePackageNames = [];
        if (($this->packages['*'] ?? null) === true) {
            $effectivePackageNames = $packageNames;
        }

        $dropPackageNames = array_filter($packageNames, fn(string $package): bool => ($this->packages[$package] ?? null) === false);
        $effectivePackageNames = array_diff($effectivePackageNames, $dropPackageNames);

        $includePackageNames = array_filter($packageNames, fn(string $package): bool => ($this->packages[$package] ?? null) === true);
        $effectivePackageNames = [...$effectivePackageNames, ...array_diff($includePackageNames, $effectivePackageNames)];

        return $effectivePackageNames;
    }
}
