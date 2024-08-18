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

use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Disposition;
use TYPO3\CMS\Core\Type\Map;

/**
 * Transforms a `csp.yaml` site configuration into a configuration model.
 *
 * @internal
 */
final class DispositionMapFactory
{
    public function __construct(private readonly Features $features) {}

    /**
     * @return list<Disposition>
     */
    public function resolveFallbackDispositions(): array
    {
        $dispositions = [];
        if ($this->features->isFeatureEnabled('security.frontend.enforceContentSecurityPolicy')) {
            $dispositions[] = Disposition::enforce;
        }
        if ($this->features->isFeatureEnabled('security.frontend.reportContentSecurityPolicy')) {
            $dispositions[] = Disposition::report;
        }
        return $dispositions;
    }

    /**
     * @return Map<Disposition, DispositionConfiguration>
     */
    public function buildDispositionMap(array $siteConfiguration): Map
    {
        $activeAssignment = (bool)($siteConfiguration['active'] ?? true);
        // @todo future TYPO3 v14 should explicitly require `active: true` to get rid of the feature fallbacks
        if ($activeAssignment === false) {
            return new Map();
        }

        $dispositions = new Map();
        // assign site-specific dispositions
        foreach (Disposition::cases() as $disposition) {
            $assignment = $siteConfiguration[$disposition->value] ?? null;
            if ($this->isActive($assignment)) {
                $dispositions[$disposition] = $this->buildDispositionConfiguration(
                    $assignment,
                    $siteConfiguration
                );
            }
        }
        // in case there is no site-specific configuration, use the fallbacks as defined by top-level features
        if (count($dispositions) === 0) {
            foreach ($this->resolveFallbackDispositions() as $fallbackDisposition) {
                // skip fallbacks in case the disposition was disabled explicitly (e.g. `enforce: false`)
                if (($siteConfiguration[$fallbackDisposition->value] ?? null) !== false) {
                    $dispositions[$fallbackDisposition] = $this->buildDispositionConfiguration(
                        true,
                        $siteConfiguration
                    );
                }
            }
        }
        return $dispositions;
    }

    private function isActive(mixed $assignment): bool
    {
        return $assignment === true || is_array($assignment);
    }

    private function buildDispositionConfiguration(
        bool|array $assignment,
        array $siteConfiguration = []
    ): DispositionConfiguration {
        if ($assignment === false) {
            throw new \LogicException('Disposition assignment cannot be false', 1724840231);
        }
        if ($assignment === true) {
            // take from top-level configuration
            // (`includeResolutions` and `packages` are ignored on purpose)
            $inheritDefault = $siteConfiguration['inheritDefault'] ?? true;
            $includeResolutions = true;
            $mutations = $siteConfiguration['mutations'] ?? [];
            $packages = [];
        } else {
            $inheritDefault = $assignment['inheritDefault'] ?? true;
            $includeResolutions = $assignment['includeResolutions'] ?? true;
            $mutations = $assignment['mutations'] ?? [];
            $packages = $assignment['packages'] ?? [];
        }
        return new DispositionConfiguration(
            (bool)$inheritDefault,
            (bool)$includeResolutions,
            is_array($mutations) ? $mutations : [],
            is_array($packages) ? $packages : [],
        );
    }
}
