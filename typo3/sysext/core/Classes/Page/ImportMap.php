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

namespace TYPO3\CMS\Core\Page;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Page\Event\ResolveJavaScriptImportEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashValue;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyRegistry;
use TYPO3\CMS\Core\SystemResource\Publishing\UriGenerationOptions;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @internal
 */
class ImportMap
{
    protected array $extensionsToLoad = [];

    private ?array $importMaps = null;

    /**
     * @param list<PackageInterface> $packages
     */
    public function __construct(
        protected readonly HashService $hashService,
        protected readonly array $packages,
        protected readonly ?PolicyRegistry $policyRegistry = null,
        protected readonly ?FrontendInterface $cache = null,
        protected readonly string $cacheIdentifier = '',
        protected readonly ?EventDispatcherInterface $eventDispatcher = null,
        protected readonly bool $bustSuffix = true
    ) {}

    /**
     * HEADS UP: Do only use in authenticated mode as this discloses as installed extensions
     */
    public function includeAllImports(): void
    {
        $this->extensionsToLoad['*'] = true;
    }

    public function includeTaggedImports(string $tag): void
    {
        if (isset($this->extensionsToLoad['*'])) {
            return;
        }

        foreach ($this->getImportMaps() as $package => $config) {
            $tags = $config['tags'] ?? [];
            if (in_array($tag, $tags, true)) {
                $this->loadDependency($package);
            }
        }
    }

    public function includeImportsFor(string $specifier): void
    {
        if (!isset($this->extensionsToLoad['*'])) {
            $this->resolveImport($specifier, true);
        } else {
            $this->dispatchResolveJavaScriptImportEvent($specifier, true);
        }
    }

    public function resolveImport(
        string $specifier,
        bool $loadImportConfiguration = true
    ): ?string {
        $resolution = $this->dispatchResolveJavaScriptImportEvent($specifier, $loadImportConfiguration);
        if ($resolution !== null) {
            return $resolution;
        }

        foreach (array_reverse($this->getImportMaps()) as $package => $config) {
            $imports = $config['imports'] ?? [];
            if (isset($imports[$specifier])) {
                if ($loadImportConfiguration) {
                    $this->loadDependency($package);
                }
                return $imports[$specifier];
            }

            $specifierParts = explode('/', $specifier);
            $specifierPartCount = count($specifierParts);
            for ($i = 1; $i < $specifierPartCount; ++$i) {
                $prefix = implode('/', array_slice($specifierParts, 0, $i)) . '/';
                if (isset($imports[$prefix])) {
                    if ($loadImportConfiguration) {
                        $this->loadDependency($package);
                    }
                    return $imports[$prefix] . implode('/', array_slice($specifierParts, $i));
                }
            }
        }

        return null;
    }

    public function render(
        string $urlPrefix,
        null|string|ConsumableNonce $nonce
    ): string {
        if (count($this->extensionsToLoad) === 0 || count($this->getImportMaps()) === 0) {
            return '';
        }

        $html = [];

        $importMap = $this->composeImportMap($urlPrefix);
        $json = json_encode(
            $importMap,
            JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_THROW_ON_ERROR
        );
        $attributes = [
            'type' => 'importmap',
        ];
        if ($nonce !== null) {
            $attributes['nonce'] = (string)$nonce;
        } else {
            $this->policyRegistry?->appendMutationCollection(
                new MutationCollection(
                    new Mutation(MutationMode::Extend, Directive::ScriptSrc, HashValue::hash($json))
                )
            );
        }
        $html[] = sprintf(
            '<script %s>%s</script>',
            GeneralUtility::implodeAttributes($attributes, true),
            $json
        );

        return implode(PHP_EOL, $html) . PHP_EOL;
    }

    public function warmupCaches(): void
    {
        $this->computeImportMaps();
    }

    protected function getImportMaps(): array
    {
        return $this->importMaps ?? $this->getFromCache() ?? $this->computeImportMaps();
    }

    protected function getFromCache(): ?array
    {
        if ($this->cache === null) {
            return null;
        }
        if (!$this->cache->has($this->cacheIdentifier)) {
            return null;
        }
        $importMaps = $this->cache->get($this->cacheIdentifier);
        if ($importMaps === false) {
            // Cache entry has been removed in the meantime
            return null;
        }
        if (!is_array($importMaps)) {
            // An invalid result is to be ignored (cache will be recreated)
            return null;
        }
        $this->importMaps = $importMaps;
        return $importMaps;
    }

    protected function computeImportMaps(): array
    {
        $extensionVersions = [];
        $importMaps = [];
        foreach ($this->packages as $package) {
            $configurationFile = $package->getPackagePath() . 'Configuration/JavaScriptModules.php';
            if (!is_readable($configurationFile)) {
                continue;
            }
            $extensionVersions[$package->getPackageKey()] = implode(':', [
                $package->getPackageKey(),
                $package->getPackageMetadata()->getVersion(),
            ]);
            $packageConfiguration = require($configurationFile);
            $importMaps[$package->getPackageKey()] = $packageConfiguration ?? [];
        }

        $isDevelopment = Environment::getContext()->isDevelopment();
        if ($isDevelopment) {
            $bust = (string)$GLOBALS['EXEC_TIME'];
        } else {
            $bust = $this->hashService->hmac(
                Environment::getProjectPath() . implode('|', $extensionVersions),
                self::class
            );
        }

        foreach ($importMaps as $packageName => $config) {
            $importMaps[$packageName]['imports'] = $this->resolvePaths(
                $config['imports'] ?? [],
                $this->bustSuffix ? $bust : null
            );
        }

        $this->importMaps = $importMaps;
        if ($this->cache !== null) {
            $this->cache->set($this->cacheIdentifier, $importMaps);
        }
        return $importMaps;
    }

    protected function resolveRecursiveImportMap(
        string $prefix,
        string $path,
        array $exclude,
        string $bust
    ): array {
        $absolutePath = GeneralUtility::getFileAbsFileName($path);
        if (!$absolutePath || @!is_dir($absolutePath)) {
            return [];
        }
        $exclude = array_map(
            static fn(string $excludePath): string => GeneralUtility::getFileAbsFileName($excludePath),
            $exclude
        );

        $fileIterator = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absolutePath)
            ),
            '#^' . preg_quote($absolutePath, '#') . '(.+\.js)$#',
            \RegexIterator::GET_MATCH
        );

        $map = [];
        foreach ($fileIterator as $match) {
            $fileName = $match[0];
            $specifier = $prefix . ($match[1] ?? '');
            $resourceIdentifier = $path . ($match[1] ?? '');

            // @todo: Abstract into an iterator?
            foreach ($exclude as $excludedPath) {
                if (str_starts_with($fileName, $excludedPath)) {
                    continue 2;
                }
            }

            $url = ltrim((string)PathUtility::getSystemResourceUri($resourceIdentifier, null, new UriGenerationOptions(uriPrefix: ''))
                ->withQuery('bust=' . $bust), '/');
            $map[$specifier] = $url;
        }

        return $map;
    }

    protected function resolvePaths(
        array $imports,
        ?string $bust = null
    ): array {
        $cacheBustingSpecifiers = [];
        foreach ($imports as $specifier => $address) {
            if (str_ends_with($specifier, '/')) {
                $path = is_array($address) ? ($address['path'] ?? '') : $address;
                $exclude = is_array($address) ? ($address['exclude'] ?? []) : [];
                $uri = PathUtility::getSystemResourceUri($path, null, new UriGenerationOptions(uriPrefix: ''))
                    ->withQuery('');
                $cacheBusted = preg_match('#[^/]@#', $path) === 1;
                if ($bust !== null && !$cacheBusted) {
                    // Resolve recursive importmap in order to add a bust suffix
                    // to each file.
                    $cacheBustingSpecifiers[] = $this->resolveRecursiveImportMap($specifier, $path, $exclude, $bust);
                }
            } else {
                $uri = PathUtility::getSystemResourceUri($address, null, new UriGenerationOptions(uriPrefix: ''))
                    ->withQuery('');
                $cacheBusted = preg_match('#[^/]@#', $address) === 1;
                if ($bust !== null && !$cacheBusted) {
                    $uri = $uri->withQuery('bust=' . $bust);
                }
            }
            $imports[$specifier] = ltrim((string)$uri, '/');
        }

        return $imports + array_merge(...$cacheBustingSpecifiers);
    }

    protected function loadDependency(string $packageName): void
    {
        if (isset($this->extensionsToLoad[$packageName])) {
            return;
        }

        $this->extensionsToLoad[$packageName] = true;
        $dependencies = $this->getImportMaps()[$packageName]['dependencies'] ?? [];
        foreach ($dependencies as $dependency) {
            $this->loadDependency($dependency);
        }
    }

    protected function composeImportMap(string $urlPrefix): array
    {
        $importMaps = $this->getImportMaps();

        if (!isset($this->extensionsToLoad['*'])) {
            $importMaps = array_intersect_key($importMaps, $this->extensionsToLoad);
        }

        $importMap = [];
        foreach ($importMaps as $singleImportMap) {
            ArrayUtility::mergeRecursiveWithOverrule($importMap, $singleImportMap);
        }
        unset($importMap['dependencies']);
        unset($importMap['tags']);

        foreach ($importMap['imports'] ?? [] as $specifier => $url) {
            $importMap['imports'][$specifier] = $urlPrefix . $url;
        }

        return $importMap;
    }

    protected function dispatchResolveJavaScriptImportEvent(
        string $specifier,
        bool $loadImportConfiguration = true
    ): ?string {
        if ($this->eventDispatcher === null) {
            return null;
        }

        return $this->eventDispatcher->dispatch(
            new ResolveJavaScriptImportEvent($specifier, $loadImportConfiguration, $this)
        )->resolution;
    }

    /**
     * @internal
     */
    public function updateState(array $state): void
    {
        $this->extensionsToLoad = $state['extensionsToLoad'] ?? [];
    }

    /**
     * @internal
     */
    public function getState(): array
    {
        return [
            'extensionsToLoad' => $this->extensionsToLoad,
        ];
    }
}
