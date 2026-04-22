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

namespace TYPO3\CMS\Backend\Tests\Functional\Module;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Module\ModuleFactory;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests that the backend module cache warmer produces the same result as the
 * normal (non-warmed) load path, specifically regarding alias adaptation for
 * legacy module identifiers used in parent/position references.
 */
final class ModuleCacheWarmupTest extends FunctionalTestCase
{
    #[Test]
    public function modulesWithLegacyParentIdentifierAreVisibleAfterCacheWarmup(): void
    {
        $cache = $this->get('cache.core');
        $cacheIdentifier = $this->get(PackageDependentCacheIdentifier::class)
            ->withPrefix('BackendModules')
            ->toString();

        // Register a module that uses the legacy alias 'web' as its parent.
        // In core, 'web' is an alias for the 'content' module.
        // Extensions still using the old identifier as parent should remain visible.
        $backendModules = $this->get('backend.modules');
        $backendModules['test_extension_module'] = [
            'parent' => 'web',
            'path' => '/module/test-extension',
            'packageName' => 'typo3/testing',
            'absolutePackagePath' => '',
        ];

        // Clear any existing cache so the warmer actually writes a fresh entry.
        $cache->remove($cacheIdentifier);

        // Invoke the module cache warmer (simulating `cache:warmup --group system`).
        $warmer = $this->get('backend.modules.warmer');
        $warmer(new CacheWarmupEvent(['system']));

        // Read what the warmer cached.
        $cachedModules = $cache->require($cacheIdentifier);

        self::assertIsArray($cachedModules, 'BackendModules cache must contain an array after warmup.');
        self::assertArrayHasKey('test_extension_module', $cachedModules);

        // After the fix, the legacy alias 'web' must have been resolved to the
        // real identifier 'content' before being stored in the cache.
        self::assertSame(
            'content',
            $cachedModules['test_extension_module']['parent'],
            'The legacy parent alias "web" must be adapted to the real identifier "content" in the cache.'
        );

        // Verify that the ModuleRegistry built from the warmed cache correctly
        // places the module as a submodule of 'content'.
        $moduleFactory = $this->get(ModuleFactory::class);
        $modules = [];
        foreach ($cachedModules as $identifier => $configuration) {
            $modules[$identifier] = $moduleFactory->createModule($identifier, $configuration);
        }
        $registry = new ModuleRegistry($modules);

        self::assertTrue(
            $registry->hasModule('test_extension_module'),
            'Module registered with legacy parent identifier must be accessible via ModuleRegistry after cache warmup.'
        );

        $contentModule = $registry->getModule('content');
        self::assertTrue(
            $contentModule->hasSubModule('test_extension_module'),
            'Module with legacy parent alias must appear as submodule of the resolved parent after cache warmup.'
        );
    }

    #[Test]
    public function modulesWithLegacyPositionIdentifierAreOrderedAfterCacheWarmup(): void
    {
        $cache = $this->get('cache.core');
        $cacheIdentifier = $this->get(PackageDependentCacheIdentifier::class)
            ->withPrefix('BackendModules')
            ->toString();

        // Register a module that uses a legacy alias in its position definition.
        // 'web_list' is a real module in core; extensions may reference legacy aliases.
        $backendModules = $this->get('backend.modules');
        $backendModules['test_position_module'] = [
            'parent' => 'content',
            'position' => ['after' => 'web'],
            'path' => '/module/test-position',
            'packageName' => 'typo3/testing',
            'absolutePackagePath' => '',
        ];

        $cache->remove($cacheIdentifier);

        $warmer = $this->get('backend.modules.warmer');
        $warmer(new CacheWarmupEvent(['system']));

        $cachedModules = $cache->require($cacheIdentifier);

        self::assertIsArray($cachedModules);
        self::assertArrayHasKey('test_position_module', $cachedModules);

        // After the fix, the legacy alias in position must also be resolved.
        self::assertSame(
            'content',
            $cachedModules['test_position_module']['position']['after'],
            'The legacy position alias "web" must be adapted to the real identifier "content" in the cache.'
        );
    }
}
