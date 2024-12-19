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

namespace TYPO3\CMS\Impexp\Initialization;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Event\PackageInitializationEvent;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Import;

/**
 * Listener to import site configurations after package initialization
 */
final class ImportSiteConfigurationsOnPackageInitialization implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Registry $registry,
        private readonly SiteConfiguration $siteConfiguration,
        private readonly SiteWriter $siteWriter,
    ) {}

    #[AsEventListener(after: ImportContentOnPackageInitialization::class)]
    public function __invoke(PackageInitializationEvent $event): void
    {
        if (!$event->hasStorageEntry(ImportContentOnPackageInitialization::class)
            || !($import = $event->getStorageEntry(ImportContentOnPackageInitialization::class)->getResult()['import'] ?? null) instanceof Import
        ) {
            return;
        }

        $extensionKey = $event->getExtensionKey();
        $importAbsFolder = $event->getPackage()->getPackagePath() . 'Initialisation/Site';
        if (!is_dir($importAbsFolder)) {
            return;
        }
        $destinationFolder = Environment::getConfigPath() . '/sites';
        GeneralUtility::mkdir($destinationFolder);
        $existingSites = $this->siteConfiguration->resolveAllExistingSites(false);
        // @todo: Get rid of symfony finder here: We should use low level tools
        //        here to locate such files.
        $finder = GeneralUtility::makeInstance(Finder::class);
        $finder->directories()->ignoreUnreadableDirs()->in($importAbsFolder);
        if ($finder->hasResults()) {
            foreach ($finder as $siteConfigDirectory) {
                $siteIdentifier = $siteConfigDirectory->getBasename();
                if (isset($existingSites[$siteIdentifier])) {
                    $this->logger->warning('Skipped importing site configuration from {key} due to existing site identifier {site}', [
                        'key' => $extensionKey,
                        'site' => $siteIdentifier,
                    ]);
                    continue;
                }
                $targetDir = $destinationFolder . '/' . $siteIdentifier;
                if (!$this->registry->get('siteConfigImport', $siteIdentifier) && !is_dir($targetDir)) {
                    GeneralUtility::mkdir($targetDir);
                    GeneralUtility::copyDirectory($siteConfigDirectory->getPathname(), $targetDir);
                    $this->registry->set('siteConfigImport', $siteIdentifier, 1);
                }
            }
        }
        $newSites = array_diff_key($this->siteConfiguration->resolveAllExistingSites(false), $existingSites);

        $importedPages = $import->getImportMapId()['pages'] ?? [];
        $newSiteIdentifierList = [];
        foreach ($newSites as $newSite) {
            $exportedPageId = $newSite->getRootPageId();
            $siteIdentifier = $newSite->getIdentifier();
            $newSiteIdentifierList[] = $siteIdentifier;
            $importedPageId = $importedPages[$exportedPageId] ?? null;
            if ($importedPageId === null) {
                $this->logger->warning('Imported site configuration with identifier {site} could not be mapped to imported page id', [
                    'site' => $siteIdentifier,
                ]);
                continue;
            }
            $configuration = $this->siteConfiguration->load($siteIdentifier);
            $configuration['rootPageId'] = $importedPageId;
            try {
                $this->siteWriter->write($siteIdentifier, $configuration);
            } catch (SiteConfigurationWriteException $e) {
                $this->logger->warning(
                    sprintf(
                        'Imported site configuration with identifier %s could not be written: %s',
                        $newSite->getIdentifier(),
                        $e->getMessage()
                    )
                );
                continue;
            }
        }
        $event->addStorageEntry(__CLASS__, $newSiteIdentifierList);
    }
}
