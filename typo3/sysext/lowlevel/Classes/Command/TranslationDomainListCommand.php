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

namespace TYPO3\CMS\Lowlevel\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Localization\LabelFileResolver;
use TYPO3\CMS\Core\Localization\LanguagePackService;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Localization\TranslationDomainMapper;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Command for listing all translation domains and their corresponding label resource file locations.
 *
 * This command scans all active packages (or all available packages with --all option)
 * for language resource files (XLF/XML) and displays the translation domain name alongside
 * the file location in EXT: syntax.
 * @internal only for development purposes
 */
#[AsCommand('language:domain:list', 'Lists all translation domains and their label resource file locations.')]
#[AsNonSchedulableCommand]
class TranslationDomainListCommand extends Command
{
    public function __construct(
        private readonly PackageManager $packageManager,
        private readonly TranslationDomainMapper $translationDomainMapper,
        private readonly LocalizationFactory $localizationFactory,
        private readonly LabelFileResolver $labelFileResolver,
        private readonly LanguagePackService $languagePackService,
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
        $this
            ->addOption(
                'deprecated',
                'd',
                InputOption::VALUE_NONE,
                'Include deprecated translation domains.'
            );
        $this
            ->addOption(
                'show-overrides',
                'o',
                InputOption::VALUE_NONE,
                'List resource override files, configured via resourceOverrides for each domain.'
            );
    }

    /**
     * Lists all translation domains and their file locations
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $specificExtension = $input->getOption('extension');
        $includeDeprecated = $input->getOption('deprecated');
        $showOverrides = $input->getOption('show-overrides');

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

            // Get 'default' resources (base files)
            $resources = $resourcesByLocale['default'] ?? [];
            foreach ($resources as $domain => $resource) {
                $labelCount = $this->countLabelsInResource($resource);
                $deprecated = $this->localizationFactory->isLanguageFileDeprecated($resource);

                if (!$deprecated || $includeDeprecated) {
                    $labelData[] = [
                        'domain' => $domain,
                        'resource' => $resource,
                        'labelCount' => $labelCount,
                        'deprecated' => $deprecated,
                        'overrides' => $showOverrides ? $this->resolveOverridesForResource($resource) : [],
                    ];
                }
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
            'Status',
        ]);

        foreach ($labelData as $data) {
            // Base file first, followed by one line per override; the label counts in the
            // "# Labels" column are rendered in parallel so each count aligns with its file.
            $labelResourceLines = [$data['resource']];
            $labelCountLines = [(string)$data['labelCount']];
            $statusLines = [$data['deprecated'] ? 'deprecated' : 'active'];
            foreach ($data['overrides'] as $override) {
                $labelResourceLines[] = $override['label'];
                $labelCountLines[] = (string)$override['count'];
                $statusLines[] = 'override: ' . $override['locale'];
            }
            $table->addRow([
                $data['domain'],
                implode("\n", $labelResourceLines),
                implode("\n", $labelCountLines),
                implode("\n", $statusLines),
            ]);
        }

        $table->render();

        $io->success('Found ' . count($labelData) . ' label resource file(s).');

        return Command::SUCCESS;
    }

    /**
     * Resolve the resource override files configured via
     * $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides'] for a given base resource.
     *
     * @return list<array{label: string, count: int, locale: string}>
     */
    protected function resolveOverridesForResource(string $resource): array
    {
        // "default" is evaluated always
        $defaultLocale = 'default';
        $defaultOverrides = $this->labelFileResolver->getOverrideFilePaths($resource, $defaultLocale);

        $locales = array_merge([$defaultLocale], $this->languagePackService->getActiveLanguages());
        $entries = [];
        foreach ($locales as $locale) {
            $overrides = $this->labelFileResolver->getOverrideFilePaths($resource, $locale);
            if ($overrides === []) {
                continue;
            }
            // Skip active languages that only inherit the general overrides, otherwise the same
            // file would be listed again for every active language.
            if ($locale !== $defaultLocale
                && array_values($overrides) === array_values($defaultOverrides)
            ) {
                continue;
            }
            $languageKey = $locale === $defaultLocale ? 'en' : $locale;
            foreach ($overrides as $override) {
                $entries[] = [
                    'label' => ' + ' . $override,
                    'count' => $this->countLabelsInResource($override, $languageKey),
                    'locale' => $locale,
                ];
            }
        }

        return $entries;
    }

    protected function countLabelsInResource(string $fileReference, string $languageKey = 'en'): int
    {
        try {
            $labels = $this->localizationFactory->getParsedData($fileReference, $languageKey);
            return count($labels);
        } catch (\Exception) {
            return 0;
        }
    }
}
