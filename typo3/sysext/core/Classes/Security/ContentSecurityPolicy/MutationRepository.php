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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\DispositionConfiguration;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\DispositionMapFactory;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ResolutionRepository;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Map;

/**
 * @internal
 */
final class MutationRepository
{
    /**
     * @var Map<Scope, Map<Disposition, Map<MutationOrigin, MutationCollection>>>
     */
    private ?Map $resolvedMutations;

    /**
     * @param Map<Scope, Map<MutationOrigin, MutationCollection>> $staticMutations
     *        (from DI, declared in `Configuration/ContentSecurityPolicies.php`)
     */
    public function __construct(
        private readonly Map $staticMutations,
        private readonly SiteFinder $siteFinder,
        private readonly ModelService $modelService,
        private readonly ScopeRepository $scopeRepository,
        private readonly ResolutionRepository $resolutionRepository,
        private readonly DispositionMapFactory $dispositionMapFactory,
    ) {
        $this->resolvedMutations = null;
    }

    /**
     * @return Map<Scope, Map<Disposition, Map<MutationOrigin, MutationCollection>>>
     */
    public function findAll(): Map
    {
        if ($this->resolvedMutations === null) {
            $this->resolveMutations();
        }
        return $this->resolvedMutations;
    }

    /**
     * @return Map<MutationOrigin, MutationCollection>
     */
    public function findByScope(Scope $scope, Disposition $disposition = Disposition::enforce): Map
    {
        if ($this->resolvedMutations === null) {
            $this->resolveMutations();
        }
        $scope = $this->reduceScope($scope);
        return $this->resolvedMutations[$scope][$disposition] ?? new Map();
    }

    private function resolveMutations(): void
    {
        if ($this->resolvedMutations !== null) {
            return;
        }

        $this->resolvedMutations = new Map();
        $allScopes = $this->scopeRepository->findAll();
        // fetch resolutions from the database & assign them later to the resolved mutations map
        $resolutions = new Map();
        foreach ($this->resolutionRepository->findAll() as $resolution) {
            // only for existing scopes (e.g. ignore scopes for sites, that are not existing anymore)
            if (in_array($resolution->scope, $allScopes, true)) {
                $mutationOrigin = new MutationOrigin(MutationOriginType::resolution, $resolution->summary);
                $scopedTarget = $this->provideScopeInMap($resolution->scope, $resolutions);
                $scopedTarget[$mutationOrigin] = $resolution->mutationCollection;
            }
        }
        // assign generic backend and frontend scopes
        foreach ([Scope::backend(), Scope::frontend()] as $scope) {
            $scopedTarget = $this->provideScopeInMap($scope, $this->resolvedMutations);
            $dispositions = $scope === Scope::frontend()
                ? $this->dispositionMapFactory->resolveFallbackDispositions()
                : [Disposition::enforce];
            foreach ($dispositions as $disposition) {
                $disposedTarget = $this->provideDispositionInMap($disposition, $scopedTarget);
                if (isset($this->staticMutations[$scope])) {
                    $disposedTarget->assign($this->staticMutations[$scope]);
                }
                if (isset($resolutions[$scope])) {
                    $disposedTarget->assign($resolutions[$scope]);
                }
            }
        }
        // fetch and assign site-specific mutations
        foreach ($this->scopeRepository->findAllFrontendSites() as $scope) {
            $site = $this->resolveSite($scope);
            $scopedTarget = $this->provideScopeInMap($scope, $this->resolvedMutations);
            // fetch site-specific `enforce` and/or `report` disposition configuration
            $dispositionMap = $this->dispositionMapFactory->buildDispositionMap(
                $site->getConfiguration()['contentSecurityPolicies']
            );
            /**
             * @var Disposition $disposition
             * @var DispositionConfiguration $dispositionConfiguration
             */
            foreach ($dispositionMap as $disposition => $dispositionConfiguration) {
                $disposedTarget = $this->provideDispositionInMap($disposition, $scopedTarget);
                $disposedTarget->assign($this->resolveStaticMutations($scope, $dispositionConfiguration));
                if ($dispositionConfiguration->includeResolutions && isset($resolutions[$scope])) {
                    $disposedTarget->assign($resolutions[$scope]);
                }
                $mutationCollection = $this->resolveFrontendSiteMutationCollection($dispositionConfiguration);
                if ($mutationCollection !== null) {
                    $mutationOrigin = new MutationOrigin(MutationOriginType::site, $scope->siteIdentifier);
                    $disposedTarget[$mutationOrigin] = $mutationCollection;
                }
            }
        }
    }

    /**
     * Resolves site-specific static mutations, applies `inheritDefault` configuration
     * and filters generic static mutations based on the `packages` configuration.
     *
     * @return Map<MutationOrigin, MutationCollection>
     */
    private function resolveStaticMutations(Scope $scope, DispositionConfiguration $dispositionConfiguration): Map
    {
        $target = new Map();
        $scope = $this->reduceScope($scope);

        if ($dispositionConfiguration->inheritDefault && isset($this->staticMutations[Scope::frontend()])) {
            // mutations from `ContentSecurityPolicies.php` for generic frontend scope
            $target->assign($this->staticMutations[Scope::frontend()]);
        }
        // mutations from `ContentSecurityPolicies.php` for a specific site identifier
        if (isset($this->staticMutations[$scope])) {
            $target->assign($this->staticMutations[$scope]);
        }

        // filter mutation origins by effective package names
        $packageOrigins = array_filter(
            $target->keys(),
            static fn(MutationOrigin $origin) => $origin->type === MutationOriginType::package
        );
        $packageNames = array_map(static fn(MutationOrigin $origin) => $origin->value, $packageOrigins);
        $effectivePackageNames = $dispositionConfiguration->resolveEffectivePackages(...$packageNames);
        foreach ($packageOrigins as $mutationOrigin) {
            if (!in_array($mutationOrigin->value, $effectivePackageNames, true)) {
                unset($target[$mutationOrigin]);
            }
        }
        return $target;
    }

    private function resolveFrontendSiteMutationCollection(DispositionConfiguration $dispositionConfiguration): ?MutationCollection
    {
        if ($dispositionConfiguration->mutations === []) {
            return null;
        }
        $mutations = array_map(
            fn(array $array) => $this->modelService->buildMutationFromArray($array),
            $dispositionConfiguration->mutations
        );
        return new MutationCollection(...$mutations);
    }

    private function provideDispositionInMap(Disposition $disposition, Map $map): Map
    {
        if (!isset($map[$disposition])) {
            $map[$disposition] = new Map();
        }
        return $map[$disposition];
    }

    /**
     * @return Map<MutationOrigin, MutationCollection>
     */
    private function provideScopeInMap(Scope $scope, Map $map): Map
    {
        $reducedScope = $this->reduceScope($scope);
        if (!isset($map[$reducedScope])) {
            $map[$reducedScope] = new Map();
        }
        return $map[$reducedScope];
    }

    /**
     * Returns a reduce representation of the current object.
     * In case a `Site` object was given, it will be reduced to just contain the site identifier.
     */
    private function reduceScope(Scope $scope): Scope
    {
        if ($scope->isFrontendSite()) {
            return Scope::frontendSiteIdentifier($scope->siteIdentifier);
        }
        return $scope;
    }

    private function resolveSite(Scope $scope): Site
    {
        return $scope->site ?? $this->siteFinder->getSiteByIdentifier($scope->siteIdentifier);
    }
}
