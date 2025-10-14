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

namespace TYPO3\CMS\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Localization\TranslationDomainMapper;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Command for listing all translation domains and their corresponding label resource file locations.
 *
 * This command scans all active packages (or all available packages with --all option)
 * for language resource files (XLF/XML) and displays the translation domain name alongside
 * the file location in EXT: syntax.
 */
class TranslationDomainListCommand extends Command
{
    public function __construct(
        private readonly PackageManager $packageManager,
        private readonly TranslationDomainMapper $translationDomainMapper,
        private readonly LocalizationFactory $localizationFactory,
    ) {
        parent::__construct();
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'extension',
                'e',
                InputOption::VALUE_REQUIRED,
                'Show translations domains only for the specified extension key.'
            );
    }

    /**
     * Lists all translation domains and their file locations
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $specificExtension = $input->getOption('extension');

        // Determine which packages to scan
        if ($specificExtension) {
            try {
                $package = $this->packageManager->getPackage($specificExtension);
                $packages = [$package];
                $io->title('Translation domains in extension: ' . $specificExtension);
            } catch (\Exception $e) {
                $io->error('Extension "' . $specificExtension . '" not found.');
                return Command::FAILURE;
            }
        } else {
            $packages = $this->packageManager->getActivePackages();
            $io->title('Translation domains in active extensions');
        }

        // Collect all language resource files with their translations and label counts
        $labelData = [];
        foreach ($packages as $package) {
            $resourcesByLocale = $this->translationDomainMapper->findLabelResourcesInPackageGroupedByLocale($package->getPackageKey());

            // Get English resources (base files)
            $resources = $resourcesByLocale['en'] ?? [];
            foreach ($resources as $domain => $resource) {
                $labelCount = $this->countLabelsInResource($resource);

                $labelData[] = [
                    'domain' => $domain,
                    'resource' => $resource,
                    'labelCount' => $labelCount,
                ];
            }
        }

        if (empty($labelData)) {
            $io->warning('No language resource files found.');
            return Command::SUCCESS;
        }

        // Sort by domain name
        usort($labelData, fn($a, $b) => strcmp($a['domain'], $b['domain']));

        // Display as table
        $table = new Table($output);
        $table->setHeaders([
            'Translation Domain',
            'Label Resource',
            '# Labels',
        ]);

        foreach ($labelData as $data) {
            $table->addRow([
                $data['domain'],
                $data['resource'],
                (string)$data['labelCount'],
            ]);
        }

        $table->render();

        $io->success('Found ' . count($labelData) . ' label resource file(s).');

        return Command::SUCCESS;
    }

    /**
     * Count the number of labels in a label resource file.
     * Uses LocalizationFactory to parse the file and count entries.
     */
    protected function countLabelsInResource(string $fileReference): int
    {
        try {
            $labels = $this->localizationFactory->getParsedData($fileReference, 'en');
            return count($labels);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
