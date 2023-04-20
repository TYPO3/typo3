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
use TYPO3\CMS\Core\Domain\ConsumableString;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Page\Event\ResolveJavaScriptImportEvent;
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
        protected readonly array $packages,
        protected readonly ?FrontendInterface $cache = null,
        protected readonly string $cacheIdentifier = '',
        protected readonly ?EventDispatcherInterface $eventDispatcher = null,
        protected readonly bool $bustSuffix = true
    ) {
    }

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
                    return $imports[$prefix] . implode(array_slice($specifierParts, $i));
                }
            }
        }

        return null;
    }

    public function render(
        string $urlPrefix,
        null|string|ConsumableString $nonce,
        bool $includePolyfill = true
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
        $nonceAttr = $nonce !== null ? ' nonce="' . htmlspecialchars((string)$nonce) . '"' : '';
        $html[] = sprintf('<script type="importmap"%s>%s</script>', $nonceAttr, $json);

        if ($includePolyfill) {
            $importmapPolyfill = $urlPrefix . PathUtility::getPublicResourceWebPath(
                'EXT:core/Resources/Public/JavaScript/Contrib/es-module-shims.js',
                false
            );

            $html[] = sprintf(
                '<script src="%s"%s></script>',
                htmlspecialchars($importmapPolyfill),
                $nonceAttr
            );
        }

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
        $this->importMaps = $this->cache->get($this->cacheIdentifier);
        return $this->importMaps;
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
            $bust = GeneralUtility::hmac(
                Environment::getProjectPath() . implode('|', $extensionVersions)
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
        $path = GeneralUtility::getFileAbsFileName($path);
        if (!$path || @!is_dir($path)) {
            return [];
        }
        $exclude = array_map(
            static fn (string $excludePath): string => GeneralUtility::getFileAbsFileName($excludePath),
            $exclude
        );

        $fileIterator = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path)
            ),
            '#^' . preg_quote($path, '#') . '(.+\.js)$#',
            \RegexIterator::GET_MATCH
        );

        $map = [];
        foreach ($fileIterator as $match) {
            $fileName = $match[0];
            $specifier = $prefix . ($match[1] ?? '');

            // @todo: Abstract into an iterator?
            foreach ($exclude as $excludedPath) {
                if (str_starts_with($fileName, $excludedPath)) {
                    continue 2;
                }
            }

            $webPath = PathUtility::getAbsoluteWebPath($fileName, false) . '?bust=' . $bust;

            $map[$specifier] = $webPath;
        }

        return $map;
    }

    protected function resolvePaths(
        array $imports,
        string $bust = null
    ): array {
        $cacheBustingSpecifiers = [];
        foreach ($imports as $specifier => $address) {
            if (str_ends_with($specifier, '/')) {
                $path = is_array($address) ? ($address['path'] ?? '') : $address;
                $exclude = is_array($address) ? ($address['exclude'] ?? []) : [];

                $url = PathUtility::getPublicResourceWebPath($path, false);
                $cacheBusted = preg_match('#[^/]@#', $path) === 1;
                if ($bust !== null && !$cacheBusted) {
                    // Resolve recursive importmap in order to add a bust suffix
                    // to each file.
                    $cacheBustingSpecifiers[] = $this->resolveRecursiveImportMap($specifier, $path, $exclude, $bust);
                }
            } else {
                $url = PathUtility::getPublicResourceWebPath($address, false);
                $cacheBusted = preg_match('#[^/]@#', $address) === 1;
                if ($bust !== null && !$cacheBusted) {
                    $url .= '?bust=' . $bust;
                }
            }
            $imports[$specifier] = $url;
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
