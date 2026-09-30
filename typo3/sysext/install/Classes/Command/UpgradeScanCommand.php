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
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Install\ExtensionScanner\ExtensionScannerService;
use TYPO3\CMS\Install\ExtensionScanner\Result\Indicator;
use TYPO3\CMS\Install\ExtensionScanner\Result\ScanMatch;
use TYPO3\CMS\Install\ExtensionScanner\Result\ScanResult;

/**
 * Scan extensions for usages of removed or deprecated TYPO3 Core code on the command line.
 *
 * Reports the same findings as the "Extension scanner" of the install tool, for the same set
 * of extensions, against the rule set of the installed Core version.
 */
final class UpgradeScanCommand extends Command
{
    private const NOTE = 'Static analysis only — dynamically composed calls or runtime class names are out of scope.';
    private const FAIL_ON = ['strong', 'any', 'none'];

    public function __construct(
        string $name,
        private readonly PackageManager $packageManager,
        private readonly ExtensionScannerService $extensionScannerService,
        private readonly Typo3Version $typo3Version,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'extensionKey',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Extension keys to scan. Defaults to all installed extensions that are not part of the Core.'
            )
            ->addOption(
                'json',
                null,
                InputOption::VALUE_NONE,
                'Output results as JSON'
            )
            ->addOption(
                'fail-on',
                null,
                InputOption::VALUE_REQUIRED,
                'Which findings make the exit code non-zero: strong, any or none. Weak findings are'
                . ' probable matches and may be false positives, so they do not fail the run by default.'
                . ' Files that could not be parsed always fail the run, except with "none".',
                'strong'
            )
            ->addOption(
                'indicator',
                null,
                InputOption::VALUE_REQUIRED,
                'List only findings with this indicator: strong or weak. Without it, both are listed.'
                . ' The summary counts and --fail-on always consider all findings.'
            )
            ->setHelp(
                'Scans PHP files for usages of Core code that has been removed or deprecated, and reports'
                . ' where each finding comes from. A "strong" finding is reliable; a "weak" finding is a'
                . ' probable one, usually a bare method or property name, and may be a false positive.'
                . ' With -v the table also lists the changelog files each finding refers to.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // Errors belong on standard error so that piping standard output stays safe.
        $errorOutput = $io->getErrorStyle();

        $failOn = strtolower((string)$input->getOption('fail-on'));
        if (!in_array($failOn, self::FAIL_ON, true)) {
            $errorOutput->error('Unknown value "' . $failOn . '" for --fail-on. Use ' . implode(', ', self::FAIL_ON) . '.');
            return Command::INVALID;
        }

        $indicator = null;
        if ($input->getOption('indicator') !== null) {
            $indicator = Indicator::tryFrom(strtolower((string)$input->getOption('indicator')));
            if ($indicator === null) {
                $errorOutput->error(
                    'Unknown value "' . $input->getOption('indicator') . '" for --indicator. Use '
                    . implode(' or ', $this->getIndicatorValues()) . '.'
                );
                return Command::INVALID;
            }
        }

        $requestedKeys = $input->getArgument('extensionKey');
        $extensionKeys = $requestedKeys === [] ? $this->getScannableExtensionKeys() : array_unique($requestedKeys);
        foreach ($extensionKeys as $extensionKey) {
            if (!$this->packageManager->isPackageAvailable($extensionKey)) {
                $errorOutput->error('Extension "' . $extensionKey . '" is not available.');
                return Command::INVALID;
            }
        }

        $resultsByExtension = [];
        foreach ($extensionKeys as $extensionKey) {
            $basePath = $this->packageManager->getPackage($extensionKey)->getPackagePath();
            foreach ($this->extensionScannerService->findScannableFiles($basePath) as $absoluteFilePath) {
                $resultsByExtension[$extensionKey][] = $this->extensionScannerService->scanFile($absoluteFilePath);
            }
        }
        $summary = $this->buildSummary(array_merge(...array_values($resultsByExtension)));
        $fails = $this->isFailure($summary, $failOn);
        $listedResultsByExtension = $this->limitToIndicator($resultsByExtension, $indicator);

        // Machine readable output stays machine readable, whatever there was to report.
        if ($input->getOption('json')) {
            $this->emitJson($output, $listedResultsByExtension, $summary, $indicator);
        } elseif ($extensionKeys === []) {
            $io->success('Nothing to scan: this installation has no extensions outside the Core.');
        } elseif ($resultsByExtension === []) {
            $io->success('No scannable files found in: ' . implode(', ', $extensionKeys));
        } else {
            $listedResults = array_merge(...array_values($listedResultsByExtension));
            $failsOnHiddenFindings = $fails && !$this->isFailure($this->buildSummary($listedResults), $failOn);
            $this->emitTable($io, $listedResults, $summary, $indicator, $failsOnHiddenFindings);
        }

        return $fails ? Command::FAILURE : Command::SUCCESS;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('extensionKey')) {
            $alreadyGiven = array_diff($input->getArgument('extensionKey'), [$input->getCompletionValue()]);
            $suggestions->suggestValues(array_values(array_diff($this->getScannableExtensionKeys(), $alreadyGiven)));
            return;
        }
        if ($input->mustSuggestOptionValuesFor('fail-on')) {
            $suggestions->suggestValues(self::FAIL_ON);
        } elseif ($input->mustSuggestOptionValuesFor('indicator')) {
            $suggestions->suggestValues($this->getIndicatorValues());
        }
    }

    /**
     * @return list<string>
     */
    private function getIndicatorValues(): array
    {
        return array_map(static fn(Indicator $indicator): string => $indicator->value, Indicator::cases());
    }

    /**
     * @param array<string, mixed> $summary
     * @param value-of<self::FAIL_ON> $failOn
     */
    private function isFailure(array $summary, string $failOn): bool
    {
        // A file the parser could not read has not been cleared, so it counts against the run
        // just like a finding does: otherwise an unreadable extension would report all clear.
        $unreadable = $summary['filesWithParseErrors'] > 0;
        return match ($failOn) {
            'any' => $summary['hits']['total'] > 0 || $unreadable,
            'strong' => $summary['hits']['byIndicator']['strong'] > 0 || $unreadable,
            'none' => false,
        };
    }

    /**
     * Drops the matches with another indicator. Results themselves are kept, so that parse
     * errors stay visible whatever the indicator.
     *
     * @param array<string, list<ScanResult>> $resultsByExtension
     * @return array<string, list<ScanResult>>
     */
    private function limitToIndicator(array $resultsByExtension, ?Indicator $indicator): array
    {
        if ($indicator === null) {
            return $resultsByExtension;
        }
        return array_map(
            static fn(array $results): array => array_map(
                static fn(ScanResult $result): ScanResult => new ScanResult(
                    $result->getFile(),
                    array_values(array_filter(
                        $result->getMatches(),
                        static fn(ScanMatch $match): bool => $match->getIndicator() === $indicator
                    )),
                    $result->getParseError(),
                    $result->isFileIgnored(),
                    $result->getEffectiveCodeLines(),
                    $result->getIgnoredLines()
                ),
                $results
            ),
            $resultsByExtension
        );
    }

    /**
     * The same set of extensions the install tool module offers.
     *
     * @return list<string>
     */
    private function getScannableExtensionKeys(): array
    {
        $extensionKeys = [];
        foreach ($this->packageManager->getAvailablePackages() as $package) {
            $metaData = $package->getPackageMetaData();
            if (!$metaData->isExtensionType() || $metaData->isFrameworkType()) {
                continue;
            }
            $extensionKeys[] = $package->getPackageKey();
        }
        sort($extensionKeys);

        return $extensionKeys;
    }

    /**
     * @param list<ScanResult> $results
     * @return array<string, mixed>
     */
    private function buildSummary(array $results): array
    {
        $byIndicator = ['strong' => 0, 'weak' => 0];
        $totalHits = 0;
        $filesWithHits = 0;
        $filesIgnored = 0;
        $filesWithParseErrors = 0;

        foreach ($results as $result) {
            if ($result->getParseError() !== null) {
                $filesWithParseErrors++;
                continue;
            }
            if ($result->isFileIgnored()) {
                $filesIgnored++;
            }
            if ($result->getMatches() !== []) {
                $filesWithHits++;
            }
            foreach ($result->getMatches() as $match) {
                $totalHits++;
                $byIndicator[$match->getIndicator()->value]++;
            }
        }

        return [
            'filesScanned' => count($results),
            'filesWithHits' => $filesWithHits,
            'filesIgnored' => $filesIgnored,
            'filesWithParseErrors' => $filesWithParseErrors,
            'hits' => [
                'total' => $totalHits,
                'byIndicator' => $byIndicator,
            ],
            'note' => self::NOTE,
        ];
    }

    /**
     * @param array<string, list<ScanResult>> $resultsByExtension
     * @param array<string, mixed> $summary
     */
    private function emitJson(OutputInterface $output, array $resultsByExtension, array $summary, ?Indicator $indicator): void
    {
        $results = [];
        foreach ($resultsByExtension as $extensionKey => $extensionResults) {
            foreach ($extensionResults as $result) {
                if ($result->getMatches() !== [] || $result->getParseError() !== null) {
                    $results[] = [$extensionKey, $result];
                }
            }
        }
        $payload = [
            'typo3Version' => $this->typo3Version->getVersion(),
            'indicator' => $indicator?->value,
            'summary' => $summary,
            'results' => array_map(
                static fn(array $entry): array => [
                    'extensionKey' => (string)$entry[0],
                    'file' => $entry[1]->getFile(),
                    'isFileIgnored' => $entry[1]->isFileIgnored(),
                    'effectiveCodeLines' => $entry[1]->getEffectiveCodeLines(),
                    'ignoredLines' => $entry[1]->getIgnoredLines(),
                    'parseError' => $entry[1]->getParseError(),
                    'hits' => array_map(
                        static fn(ScanMatch $match): array => [
                            'line' => $match->getLine(),
                            'indicator' => $match->getIndicator()->value,
                            'matcher' => $match->getMatcher(),
                            'message' => $match->getMessage(),
                            'restFiles' => $match->getRestFiles(),
                        ],
                        $entry[1]->getMatches()
                    ),
                ],
                $results
            ),
        ];

        // OUTPUT_RAW: the payload must not pass through the console formatter, which would eat
        // style tags that happen to occur in a message or a file path.
        $output->writeln(
            (string)json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            OutputInterface::OUTPUT_RAW
        );
    }

    /**
     * @param list<ScanResult> $results
     * @param array<string, mixed> $summary
     */
    private function emitTable(SymfonyStyle $io, array $results, array $summary, ?Indicator $indicator, bool $failsOnHiddenFindings): void
    {
        $rows = [];
        $parseErrors = [];
        foreach ($results as $result) {
            if ($result->getParseError() !== null) {
                $parseErrors[] = $result->getFile() . ': ' . $result->getParseError();
                continue;
            }
            foreach ($result->getMatches() as $match) {
                $rows[] = [
                    $result->getFile(),
                    (string)$match->getLine(),
                    $match->getIndicator()->value,
                    $match->getMatcher(),
                    $io->isVerbose() && $match->getRestFiles() !== []
                        ? $match->getMessage() . "\n" . implode("\n", $match->getRestFiles())
                        : $match->getMessage(),
                ];
            }
        }

        if ($rows === [] && $summary['hits']['total'] === 0) {
            $io->success('Scanned ' . $summary['filesScanned'] . ' files, no findings.');
        } elseif ($rows === []) {
            $io->writeln(sprintf(
                'No findings match --indicator=%s. %d findings in %d scanned files: %d strong, %d weak.',
                $indicator?->value,
                $summary['hits']['total'],
                $summary['filesScanned'],
                $summary['hits']['byIndicator']['strong'],
                $summary['hits']['byIndicator']['weak']
            ));
        } else {
            $io->table(['File', 'Line', 'Indicator', 'Matcher', 'Message'], $rows);
            $io->writeln(sprintf(
                '%d findings in %d scanned files: %d strong, %d weak.',
                $summary['hits']['total'],
                $summary['filesScanned'],
                $summary['hits']['byIndicator']['strong'],
                $summary['hits']['byIndicator']['weak']
            ));
        }

        if ($parseErrors !== []) {
            $io->warning(count($parseErrors) . ' files could not be parsed:');
            $io->listing($parseErrors);
        }

        if ($failsOnHiddenFindings) {
            $io->warning('Findings hidden by --indicator=' . $indicator?->value . ' make this run fail.');
        }

        $io->writeln(self::NOTE);
    }
}
