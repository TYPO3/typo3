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

namespace TYPO3\CMS\Core\Configuration\Tca;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeTcaOverridesEvent;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * @internal Bootstrap related base TCA loading. Extensions must not use this.
 */
#[Autoconfigure(public: true)]
final readonly class TcaFactory
{
    public function __construct(
        private PackageManager $packageManager,
        private EventDispatcherInterface $eventDispatcher,
        #[Autowire(service: 'cache.core')]
        private PhpFrontend $codeCache,
    ) {}

    /**
     * The main production worker method.
     */
    public function get(): array
    {
        $cacheData = $this->codeCache->require($this->getTcaCacheIdentifier());
        if ($cacheData) {
            $tca = $cacheData['tca'];
        } else {
            $tca = $this->create();
            $this->createBaseTcaCacheFile($tca);
        }

        return $tca;
    }

    /**
     * This is (indirectly) used by extension manager when loading
     * extensions, by install tool bootstrap and cache warmup.
     */
    public function create(): array
    {
        $tca = $this->loadConfigurationTcaFiles();
        $tca = $this->dispatchBeforeTcaOverridesEvent($tca);
        $tca = $this->enrichTca($tca);
        $tca = $this->loadConfigurationTcaOverridesFiles($tca);
        $tca = $this->migrateTca($tca);
        $tca = $this->prepareTca($tca);
        return $this->dispatchAfterTcaCompilationEvent($tca);
    }

    /**
     * This is used by install tool LoadTcaService to check certain aspects of TCA
     */
    public function createNotMigrated(): array
    {
        $tca = $this->loadConfigurationTcaFiles();
        $tca = $this->dispatchBeforeTcaOverridesEvent($tca);
        $tca = $this->enrichTca($tca);
        return $this->loadConfigurationTcaOverridesFiles($tca);
    }

    /**
     * Public since it's also used by CacheWarmupCommand
     */
    public function createBaseTcaCacheFile(array $tca): void
    {
        $this->codeCache->set(
            $this->getTcaCacheIdentifier(),
            'return '
            . var_export(['tca' => $tca], true)
            . ';'
        );
    }

    private function getTcaCacheIdentifier(): string
    {
        return (new PackageDependentCacheIdentifier($this->packageManager))->withPrefix('tca_base')->toString();
    }

    private function loadConfigurationTcaFiles(): array
    {
        // To require TCA in a safe scoped environment avoiding local variable clashes.
        // Note: Return type 'mixed' is intended, otherwise broken TCA files with missing "return [];" statement would
        //       emit a "return value must be of type array, int returned" PHP TypeError. This is mitigated by an array
        //       check below.
        $scopedReturnRequire = static function (string $filename): mixed {
            return require $filename;
        };
        // First load "full table" files from Configuration/TCA
        $GLOBALS['TCA'] = [];
        $activePackages = $this->packageManager->getActivePackages();
        foreach ($activePackages as $package) {
            try {
                $finder = Finder::create()->files()->sortByName()->depth(0)->name('*.php')->in($package->getPackagePath() . 'Configuration/TCA');
            } catch (\InvalidArgumentException) {
                // No such directory in this package
                continue;
            }
            foreach ($finder as $fileInfo) {
                $tcaOfTable = $scopedReturnRequire($fileInfo->getPathname());
                if (is_array($tcaOfTable)) {
                    $tcaTableName = substr($fileInfo->getBasename(), 0, -4);
                    $GLOBALS['TCA'][$tcaTableName] = $tcaOfTable;
                }
            }
        }
        $tca = $GLOBALS['TCA'];
        unset($GLOBALS['TCA']);
        return $tca;
    }

    private function enrichTca(array $tca): array
    {
        return (new TcaEnrichment())->enrich($tca);
    }

    private function loadConfigurationTcaOverridesFiles(array $tca): array
    {
        // To require TCA Overrides in a safe scoped environment avoiding local variable clashes.
        $scopedRequire = static function (string $filename): void {
            require $filename;
        };
        // Execute override files from Configuration/TCA/Overrides
        $GLOBALS['TCA'] = $tca;
        $activePackages = $this->packageManager->getActivePackages();
        foreach ($activePackages as $package) {
            try {
                $finder = Finder::create()->files()->sortByName()->depth(0)->name('*.php')->in($package->getPackagePath() . 'Configuration/TCA/Overrides');
            } catch (\InvalidArgumentException) {
                // No such directory in this package
                continue;
            }
            foreach ($finder as $fileInfo) {
                $scopedRequire($fileInfo->getPathname());
            }
        }
        $tca = $GLOBALS['TCA'];
        unset($GLOBALS['TCA']);
        return $tca;
    }

    private function migrateTca(array $tca): array
    {
        // Call the TcaMigration and log any deprecations.
        $tcaMigration = new TcaMigration();
        $tcaProcessingResult = $tcaMigration->migrate($tca);
        $messages = $tcaProcessingResult->getMessages();
        if (!empty($messages)) {
            $context = 'Automatic TCA migration done during bootstrap. Please adapt TCA accordingly, these migrations'
                . ' will be removed. The backend module "Configuration -> TCA" shows the modified values.'
                . ' Please adapt these areas:';
            array_unshift($messages, $context);
            trigger_error(implode(LF, $messages), E_USER_DEPRECATED);
        }
        return $tcaProcessingResult->getTca();
    }

    private function prepareTca(array $tca): array
    {
        return (new TcaPreparation())->prepare($tca);
    }

    private function dispatchBeforeTcaOverridesEvent($tca): array
    {
        return $this->eventDispatcher->dispatch(new BeforeTcaOverridesEvent($tca))->getTca();
    }

    private function dispatchAfterTcaCompilationEvent($tca): array
    {
        $GLOBALS['TCA'] = $tca;
        $tca = $this->eventDispatcher->dispatch(new AfterTcaCompilationEvent($tca))->getTca();
        unset($GLOBALS['TCA']);
        return $tca;
    }
}
