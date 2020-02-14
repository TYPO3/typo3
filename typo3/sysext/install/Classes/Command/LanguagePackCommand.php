<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Command;

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
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\LanguagePackService;

/**
 * Core function for updating language packs
 */
class LanguagePackCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setAliases(['lang:language:update']);
        $this->setDescription('Update the language files of all activated extensions')
            ->addArgument(
                'locales',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Provide iso codes separated by space to update only selected language packs. Example `bin/typo3 language:update de ja`.'
            );
    }

    /**
     * Update language packs of all active languages for all active extensions
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasArgument('command') && substr_count($input->getArgument('command'), ':') === 2) {
            $message = 'bin/typo3 lang:language:update is deprecated, use bin/typo3 language:update instead';
            $output->writeln('<error>' . $message . '</error>');
            trigger_error($message, E_USER_DEPRECATED);
        }

        $languagePackService = GeneralUtility::makeInstance(LanguagePackService::class);

        try {
            $isos = $input->getArgument('locales');
        } catch (\Exception $e) {
            $isos = [];
        }
        if (empty($isos)) {
            $isos = $languagePackService->getActiveLanguages();
        }

        if ($output->isVerbose()) {
            $output->writeln(sprintf(
                '<info>Updating language packs of all activated extensions for locale(s) "%s"</info>',
                implode('", "', $isos)
            ));
        }

        $extensions = $languagePackService->getExtensionLanguagePackDetails();

        if (!$output->isVerbose()) {
            $progressBarOutput = new NullOutput();
        } else {
            $progressBarOutput = $output;
        }
        $progressBar = new ProgressBar($progressBarOutput, count($isos) * count($extensions));
        $languagePackService->updateMirrorBaseUrl();
        foreach ($isos as $iso) {
            foreach ($extensions as $extension) {
                $languagePackService->languagePackDownload($extension['key'], $iso);
                $progressBar->advance();
            }
        }
        $languagePackService->setLastUpdatedIsoCode($isos);
        $progressBar->finish();

        // Flush language cache
        GeneralUtility::makeInstance(CacheManager::class)->getCache('l10n')->flush();

        return 0;
    }
}
