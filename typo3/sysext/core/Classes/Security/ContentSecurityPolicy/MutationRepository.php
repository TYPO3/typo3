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

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ResolutionRepository;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Map;

/**
 * @internal
 */
final class MutationRepository
{
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
    ) {
        $this->resolvedMutations = null;
    }

    /**
     * @param bool $resolved whether to include resolved mutations (resolutions)
     * @return Map<Scope, Map<MutationOrigin, MutationCollection>>
     */
    public function findAll(bool $resolved = true): Map
    {
        if ($resolved) {
            $this->resolveMutations();
            return $this->resolvedMutations;
        }
        return $this->staticMutations;
    }

    /**
     * @param bool $resolved whether to include resolved mutations (resolutions)
     * @return Map<MutationOrigin, MutationCollection>
     */
    public function findByScope(Scope $scope, bool $resolved = true): Map
    {
        if ($resolved) {
            $this->resolveMutations();
            return $this->resolvedMutations[$scope] ?? new Map();
        }
        return $this->staticMutations[$scope] ?? new Map();
    }

    private function resolveMutations(): void
    {
        if ($this->resolvedMutations !== null) {
            return;
        }
        $this->resolvedMutations = clone $this->staticMutations;
        $allScopes = $this->scopeRepository->findAll();
        // fetch resolution mutations from the database
        foreach ($this->resolutionRepository->findAll() as $resolution) {
            // only for existing scopes (e.g. ignore scopes for sites, that are not existing anymore)
            if (in_array($resolution->scope, $allScopes, true)) {
                $mutationOrigin = new MutationOrigin(MutationOriginType::resolution, $resolution->summary);
                $target = $this->provideScopeInResolvedMutations($resolution->scope);
                $target[$mutationOrigin] = $resolution->mutationCollection;
            }
        }
        // fetch site-specific mutations
        foreach ($this->scopeRepository->findAllFrontendSites() as $scope) {
            $site = $this->resolveSite($scope);
            $target = $this->provideScopeInResolvedMutations($scope);
            $shallInheritDefault = (bool)($site->getConfiguration()['contentSecurityPolicies']['inheritDefault'] ?? true);
            if ($shallInheritDefault && $scope->isFrontendSite()) {
                foreach ($this->resolvedMutations[Scope::frontend()] ?? [] as $existingOrigin => $existingCollection) {
                    $target[$existingOrigin] = clone $existingCollection;
                }
            }
            $mutationCollection = $this->resolveFrontendSiteMutationCollection($site);
            if ($mutationCollection !== null) {
                $mutationOrigin = new MutationOrigin(MutationOriginType::site, $scope->siteIdentifier);
                $target[$mutationOrigin] = $mutationCollection;
            }
        }
    }

    private function resolveFrontendSiteMutationCollection(Site $site): ?MutationCollection
    {
        $mutationConfigurations = $site->getConfiguration()['contentSecurityPolicies']['mutations'] ?? [];
        if (empty($mutationConfigurations) || !is_array($mutationConfigurations)) {
            return null;
        }
        $mutations = array_map(
            fn(array $array) => $this->modelService->buildMutationFromArray($array),
            $mutationConfigurations
        );
        return new MutationCollection(...$mutations);
    }

    /**
     * @return Map<MutationOrigin, MutationCollection>
     */
    private function provideScopeInResolvedMutations(Scope $scope): Map
    {
        $reducedScope = $this->reduceScope($scope);
        if (!isset($this->resolvedMutations[$reducedScope])) {
            $this->resolvedMutations[$reducedScope] = new Map();
        }
        return $this->resolvedMutations[$reducedScope];
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
