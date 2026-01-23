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
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Lowlevel\Localization\Dto\DomainSearchResult;
use TYPO3\CMS\Lowlevel\Localization\Dto\LabelSearchResult;
use TYPO3\CMS\Lowlevel\Localization\LabelFinder;

/**
 * Command for listing all translation domains and their corresponding label resource file locations.
 *
 * This command scans all active packages (or all available packages with --all option)
 * for language resource files (XLF/XML) and displays the translation domain name alongside
 * the file location in EXT: syntax.
 * @internal only for development purposes
 */
#[AsCommand('language:domain:search', 'Search for translation domain labels and their references (only for development purpose)')]
#[AsNonSchedulableCommand]
class TranslationDomainSearchCommand extends Command
{
    public function __construct(
        private readonly PackageManager $packageManager,
        private readonly LabelFinder $labelFinder,
    ) {
        parent::__construct();
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure(): void
    {
        $this->setHelp('The command scans all language resource files (XLIFF) within active or
specific extensions and lists matching labels by domain, based on a
given search string or regular expression.

This tool is especially useful during the ongoing consolidation and relocation
of language labels in Core and extension development, allowing developers to easily
find labels and identify replacements when refactoring or cleaning up
translations.

Examples
========

Search all active extensions for labels containing the word "cache":

    vendor/bin/typo3 language:domain:search --search cache

Search only in the `core` extension using a regular expression:

    vendor/bin/typo3 language:domain:search --extension core --regex "/cache|clear/i"

');

        $this
            ->addOption(
                'extension',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Show translations domains only for the specified extension key.',
                ''
            );
        $this
            ->addOption(
                'locale',
                'l',
                InputOption::VALUE_OPTIONAL,
                'The locale to search in.',
                'en'
            );
        $this
            ->addOption(
                'search',
                's',
                InputOption::VALUE_OPTIONAL,
                'Search for translation labels and label references containing a word.',
                ''
            );
        $this
            ->addOption(
                'regex',
                'r',
                InputOption::VALUE_OPTIONAL,
                'Search for translation labels and label references by regular expression.',
                ''
            );
        $this
            ->addOption(
                'idonly',
                null,
                InputOption::VALUE_NONE,
                'Only search in identifier, ignore label content.',
            );
        $this
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Limit results to the first X results. Set to zero to show all labels. ',
            );
        $this
            ->addOption(
                'crop',
                null,
                InputOption::VALUE_OPTIONAL,
                'Crop labels to a maximum of x chars. Set to zero to disable cropping. ',
                50,
            );
        $this
            ->addOption(
                'flat',
                null,
                InputOption::VALUE_NONE,
                'Output a flat list.'
            );
        $this
            ->addOption(
                'json',
                null,
                InputOption::VALUE_NONE,
                'Output result as JSON (instead of table or SymfonyStyle messages).'
            );
    }

    /**
     * Lists all translation domains and their file locations
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $specificExtension = (string)$input->getOption('extension');
        $locale = (string)$input->getOption('locale');
        $searchString = (string)$input->getOption('search');
        $crop = (int)$input->getOption('crop');
        $regex = (string)$input->getOption('regex');
        $searchInIdentifierOnly = (bool)$input->getOption('idonly');
        $limit = (int)$input->getOption('limit');
        $asJson = (bool)$input->getOption('json');
        $flatList = (bool)$input->getOption('flat');
        if ($regex !== '') {
            if (!$this->isValidRegex($regex)) {
                $io->warning('Regular expression "' . $regex . '" is not valid. ');
                return Command::FAILURE;
            }
        }

        // Determine which packages to scan
        if ($specificExtension) {
            try {
                $package = $this->packageManager->getPackage($specificExtension);
                $packages = [$package];
                $io->title('Labels in extension: ' . $specificExtension);
            } catch (\Exception $e) {
                $io->error('Extension "' . $specificExtension . '" not found.');
                return Command::FAILURE;
            }
        } else {
            $packages = $this->packageManager->getActivePackages();
            $io->title('Labels in active extensions');
        }
        $searchResult = $this->labelFinder->findLabels($packages, $locale, $searchString, $regex, $searchInIdentifierOnly, $limit, $flatList);

        if (empty($searchResult)) {
            $io->warning('No language resource files found.');
            return Command::SUCCESS;
        }
        if ($asJson) {
            $this->composeJSON($io, $specificExtension, $searchString, $regex, $limit, $searchResult);

            return Command::SUCCESS;
        }
        if ($flatList) {
            $this->printTableFlat($io, $searchResult, $crop, true, $locale);
        } else {
            $this->printTable($io, $searchResult, $crop, $locale);
        }

        if ($limit <= 0 || count($searchResult) < $limit) {
            $io->success('Found ' . count($searchResult) . ' label references.');
        } else {

            $io->warning(sprintf('Displayed the first %s label references. There are probably more.', $limit));
        }

        return Command::SUCCESS;
    }

    private function printTable(SymfonyStyle $io, array $labelInDomain, int $crop, string $locale): void
    {
        /** @var DomainSearchResult $domain */
        foreach ($labelInDomain as $domain) {
            $io->writeln('');
            $io->title($domain->domain . ' file ' . $domain->resource);
            $this->printTableFlat($io, $domain->labels, $crop, false, $locale);
        }
    }

    private function printTableFlat(SymfonyStyle $io, array $labelData, int $crop, bool $includeDomain, string $locale): void
    {
        // Sort by domain name and reference
        usort($labelData, static function ($a, $b): int {
            $domainCompare = strcmp((string)$a->domain, (string)$b->domain);
            return $domainCompare !== 0
                ? $domainCompare
                : strcmp((string)$a->reference, (string)$b->reference);
        });

        // Display as table
        $table = new Table($io);
        $headers = [
            'Domain',
            'Label Reference',
            'Label Content (' . $locale . ')',
        ];
        if (!$includeDomain) {
            array_shift($headers);
        }
        $table->setHeaders($headers);

        /** @var LabelSearchResult $data */
        foreach ($labelData as $data) {
            $label = $data->label;
            if ($crop > 0 && strlen($label) > $crop) {
                $label = substr($label, 0, $crop) . '...';
            }
            $row = [
                $data->domain,
                $data->reference,
                $label,
            ];
            if (!$includeDomain) {
                array_shift($row);
            }
            $table->addRow($row);
        }

        $table->render();
    }

    private function composeJSON(SymfonyStyle $io, string $specificExtension, string $searchString, string $regex, int $limit, array $totalLabelData): void
    {
        $payload = [
            'ok' => true,
            'filters' => [
                'extension' => $specificExtension ?: null,
                'search' => $searchString ?: null,
                'regex' => $regex ?: null,
                'limit' => $limit,
            ],
            'count' => count($totalLabelData),
            'items' => $totalLabelData,
        ];

        // Pretty JSON, keep unicode and slashes readable
        $io->writeln(json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));
    }

    private function isValidRegex(mixed $regex): bool
    {
        return @preg_match($regex, '') !== false;
    }

}
