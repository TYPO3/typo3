<?php
namespace TYPO3\CMS\Lang\Command;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Lang\Domain\Repository\LanguageRepository;
use TYPO3\CMS\Lang\Service\RegistryService;
use TYPO3\CMS\Lang\Service\TranslationService;

/**
 * Language command controller updates translation packages
 */
class LanguageCommandController extends CommandController
{
    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var \TYPO3\CMS\Lang\Service\RegistryService
     */
    protected $registryService;

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * @param \TYPO3\CMS\Lang\Service\RegistryService $registryService
     */
    public function injectRegistryService(RegistryService $registryService)
    {
        $this->registryService = $registryService;
    }

    /**
     * Update language file for each extension
     *
     * @param string $localesToUpdate Comma separated list of locales that needs to be updated
     */
    public function updateCommand($localesToUpdate = '')
    {
        /** @var $translationService \TYPO3\CMS\Lang\Service\TranslationService */
        $translationService = $this->objectManager->get(TranslationService::class);
        /** @var $languageRepository \TYPO3\CMS\Lang\Domain\Repository\LanguageRepository */
        $languageRepository = $this->objectManager->get(LanguageRepository::class);
        $locales = [];
        if (!empty($localesToUpdate)) {
            $locales = GeneralUtility::trimExplode(',', $localesToUpdate, true);
        } else {
            $languages = $languageRepository->findSelected();
            foreach ($languages as $language) {
                /** @var $language \TYPO3\CMS\Lang\Domain\Model\Language */
                $locales[] = $language->getLocale();
            }
        }
        /** @var PackageManager $packageManager */
        $packageManager = $this->objectManager->get(PackageManager::class);
        $this->emitPackagesMayHaveChangedSignal();
        $packages = $packageManager->getAvailablePackages();
        $this->outputLine((sprintf('Updating language packs of all activated extensions for locales "%s"', implode(', ', $locales))));
        $this->output->progressStart(count($locales) * count($packages));
        foreach ($locales as $locale) {
            /** @var PackageInterface $package */
            foreach ($packages as $package) {
                $extensionKey = $package->getPackageKey();
                $result = $translationService->updateTranslation($extensionKey, $locale);
                if (empty($result[$extensionKey][$locale]['error'])) {
                    $this->registryService->set($locale, $GLOBALS['EXEC_TIME']);
                }
                $this->output->progressAdvance();
            }
        }
        // Flush language cache
        GeneralUtility::makeInstance(CacheManager::class)->getCache('l10n')->flush();
        $this->output->progressFinish();
    }

    /**
     * Emits packages may have changed signal
     */
    protected function emitPackagesMayHaveChangedSignal()
    {
        $this->signalSlotDispatcher->dispatch('PackageManagement', 'packagesMayHaveChanged');
    }
}
