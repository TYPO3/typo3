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

namespace TYPO3\CMS\Install\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\LanguagePackService;
use TYPO3\CMS\Install\Service\LateBootService;

/**
 * Core function for updating language packs
 */
class LanguagePackCommand extends Command
{
    /**
     * @var LateBootService
     */
    private $lateBootService;

    public function __construct(
        string $name,
        LateBootService $lateBootService
    ) {
        $this->lateBootService = $lateBootService;
        parent::__construct($name);
    }
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setDescription('Update the language files of all activated extensions')
            ->addArgument(
                'locales',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Provide iso codes separated by space to update only selected language packs. Example `bin/typo3 language:update de ja`.',
                []
            )
            ->addOption(
                'no-progress',
                null,
                InputOption::VALUE_NONE,
                'Disable progress bar.'
            )
            ->addOption(
                'fail-on-warnings',
                null,
                InputOption::VALUE_NONE,
                'Fail command when translation was not found on the server.'
            )
            ->addOption(
                'skip-extension',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Skip extension. Useful for e.g. for not public extensions, which don\'t have language packs.',
                []
            );
    }

    /**
     * Update language packs of all active languages for all active extensions
     *
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container = $this->lateBootService->loadExtLocalconfDatabaseAndExtTables(false, true);
        $languagePackService = $container->get(LanguagePackService::class);
        $noProgress = $input->getOption('no-progress') || $output->isVerbose();
        $isos = (array)$input->getArgument('locales');
        $skipExtensions = (array)$input->getOption('skip-extension');
        $failOnWarnings = (bool)$input->getOption('fail-on-warnings');
        $status = Command::SUCCESS;

        // Condition for the scheduler command, e.g. "de fr pt"
        if (count($isos) === 1 && str_contains($isos[0], ' ')) {
            $isos = GeneralUtility::trimExplode(' ', $isos[0], true);
        }
        if (empty($isos)) {
            $isos = $languagePackService->getActiveLanguages();
        }

        $output->writeln('<info>Updating language packs</info>');

        $extensions = $languagePackService->getExtensionLanguagePackDetails();

        if ($noProgress) {
            $progressBarOutput = new NullOutput();
        } else {
            $progressBarOutput = $output;
        }

        $downloads = [];
        $packageCount = 0;
        foreach ($extensions as $extensionKey => $extension) {
            if (in_array($extensionKey, $skipExtensions, true)) {
                continue;
            }
            $downloads[$extensionKey] = [];
            foreach ($extension['packs'] as $iso => $pack) {
                if (!in_array($iso, $isos, true)) {
                    continue;
                }
                $downloads[$extensionKey][] = $iso;
                $packageCount++;
            }

            if (empty($downloads[$extensionKey])) {
                unset($downloads[$extensionKey]);
            }
        }
        $progressBar = new ProgressBar($progressBarOutput, $packageCount);
        foreach ($downloads as $extension => $extensionLanguages) {
            foreach ($isos as $iso) {
                if ($noProgress) {
                    $output->writeln(sprintf('<info>Fetching pack for language "%s" for extension "%s"</info>', $iso, $extension), $output::VERBOSITY_VERY_VERBOSE);
                }
                $result = $languagePackService->languagePackDownload($extension, $iso);
                if ($noProgress) {
                    switch ($result) {
                        case 'failed':
                            $output->writeln(sprintf('<comment>Fetching pack for language "%s" for extension "%s" failed</comment>', $iso, $extension));
                            break;
                        case 'update':
                            $output->writeln(sprintf('<info>Updated pack for language "%s" for extension "%s"</info>', $iso, $extension));
                            break;
                        case 'new':
                            $output->writeln(sprintf('<info>Fetching new pack for language "%s" for extension "%s"</info>', $iso, $extension));
                            break;
                        case 'skipped':
                            $output->writeln(sprintf('<info>Skipped pack for language "%s" for extension "%s"</info>', $iso, $extension));
                            break;
                    }
                }

                // Fail only if --fail-on-warnings is set and a language pack was not found.
                if ($failOnWarnings && $result === 'failed') {
                    $status = Command::FAILURE;
                }

                $progressBar->advance();
            }
        }
        $languagePackService->setLastUpdatedIsoCode($isos);
        $progressBar->finish();
        $output->writeln('');
        // Flush language cache
        GeneralUtility::makeInstance(CacheManager::class)->getCache('l10n')->flush();

        return $status;
    }
}
