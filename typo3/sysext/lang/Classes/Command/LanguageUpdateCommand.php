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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher as SignalSlotDispatcher;
use TYPO3\CMS\Lang\Domain\Repository\LanguageRepository;
use TYPO3\CMS\Lang\Service\RegistryService;
use TYPO3\CMS\Lang\Service\TranslationService;

/**
 * Core function for updating the language files
 */
class LanguageUpdateCommand extends Command
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param string $name
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \InvalidArgumentException
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setAliases(['lang:language:update']);
        $description = 'Update the language files of all activated extensions';

        $this
            ->setDescription($description)
            ->addArgument(
                'locales',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (substr_count($input->getArgument('command'), ':') === 2) {
            $message = 'bin/typo3 lang:language:update is deprecated, use bin/typo3 language:update instead';
            $output->writeln('<error>' . $message . '</error>');
            trigger_error($message, E_USER_DEPRECATED);
        }

        try {
            $localesToUpdate = $input->getArgument('locales');
        } catch (\Exception $e) {
            $localesToUpdate = [];
        }

        /** @var $translationService \TYPO3\CMS\Lang\Service\TranslationService */
        $translationService = $this->objectManager->get(TranslationService::class);
        /** @var $languageRepository \TYPO3\CMS\Lang\Domain\Repository\LanguageRepository */
        $languageRepository = $this->objectManager->get(LanguageRepository::class);

        $allLocales = array_map(function ($language) {
            /** @var $language \TYPO3\CMS\Lang\Domain\Model\Language */
            return $language->getLocale();
        }, $languageRepository->findSelected());

        $nonUpdatableLocales = array_diff($localesToUpdate, $allLocales);
        $updatableLocales = array_intersect($localesToUpdate, $allLocales);

        $locales = empty($updatableLocales) ? $allLocales : $updatableLocales;

        foreach ($nonUpdatableLocales as $nonUpdatableLocale) {
            $output->writeln(sprintf(
                '<error>Skipping locale "%s" as it is not activated in the backend</error>',
                $nonUpdatableLocale
            ));
        }

        $output->writeln(sprintf(
            '<info>Updating language packs of all activated extensions for locale(s) "%s"</info>',
            implode('", "', $locales)
        ));

        /** @var RegistryService $registryService */
        $registryService = $this->objectManager->get(RegistryService::class);

        /** @var PackageManager $packageManager */
        $packageManager = $this->objectManager->get(PackageManager::class);
        $this->emitPackagesMayHaveChangedSignal();
        $packages = $packageManager->getAvailablePackages();

        $progressBar = new ProgressBar($output, count($locales) * count($packages));
        foreach ($locales as $locale) {
            /** @var PackageInterface $package */
            foreach ($packages as $package) {
                $extensionKey = $package->getPackageKey();
                $result = $translationService->updateTranslation($extensionKey, $locale);
                if (empty($result[$extensionKey][$locale]['error'])) {
                    $registryService->set($locale, $GLOBALS['EXEC_TIME']);
                }
                $progressBar->advance();
            }
        }
        $progressBar->finish();

        // Flush language cache
        GeneralUtility::makeInstance(CacheManager::class)->getCache('l10n')->flush();

        return 0;
    }

    /**
     * Emits packages may have changed signal
     *
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    protected function emitPackagesMayHaveChangedSignal()
    {
        $this->objectManager->get(SignalSlotDispatcher::class)->dispatch('PackageManagement', 'packagesMayHaveChanged');
    }
}
