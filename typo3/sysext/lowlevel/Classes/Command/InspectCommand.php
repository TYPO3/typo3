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
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Crypto\HashAlgo;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderRegistry;
use TYPO3\CMS\Lowlevel\Inspector\DataNormalizer;
use TYPO3\CMS\Lowlevel\Inspector\FlatRenderer;
use TYPO3\CMS\Lowlevel\Inspector\LabelResolver;
use TYPO3\CMS\Lowlevel\Inspector\StyledText;
use TYPO3\CMS\Lowlevel\Inspector\TreeRenderer;
use TYPO3\CMS\Lowlevel\Inspector\ValueFormatter;

/**
 * Shows the data of the providers of the Configuration backend module.
 *
 * @internal This class is a specific command implementation and is not part of the TYPO3's Core API.
 */
#[AsCommand('lowlevel:inspect', 'Shows the data of the Configuration module providers')]
#[AsNonSchedulableCommand]
final class InspectCommand extends Command
{
    private const int SIZE_LIMIT = 1_000_000;
    private const int WIDTH_WITHOUT_TERMINAL = 120;
    private const int MIN_WIDTH = 40;
    private const int COMPLETION_LIFETIME = 60;

    public function __construct(
        #[Autowire(lazy: true)]
        private readonly ProviderRegistry $providerRegistry,
        private readonly DataNormalizer $dataNormalizer,
        private readonly LabelResolver $labelResolver,
        private readonly TreeRenderer $treeRenderer,
        private readonly FlatRenderer $flatRenderer,
        private readonly ValueFormatter $valueFormatter,
        private readonly LanguageServiceFactory $languageServiceFactory,
        private readonly Locales $locales,
        #[Autowire(service: 'cache.lowlevel_inspect')]
        private readonly FrontendInterface $completionCache,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Shows what the Configuration backend module shows: configuration, registries and wiring.' . LF . LF
            . 'Without arguments, all providers are listed. Each further argument selects one key level.' . LF
            . 'The tree is an overview of about ' . TreeRenderer::BUDGET . ' lines; flat and json are always complete.' . LF . LF
            . 'Examples:' . LF
            . '  <info>bin/typo3 lowlevel:inspect</info>' . LF
            . '  <info>bin/typo3 lowlevel:inspect tca tt_content columns header</info>' . LF
            . '  <info>bin/typo3 lowlevel:inspect confVars SYS sitename</info>' . LF
            . '  <info>bin/typo3 lowlevel:inspect httpMiddlewareStacks frontend --json</info>' . LF
            . '  <info>bin/typo3 lowlevel:inspect tca --flat | grep renderType</info>' . LF . LF
            . 'Place --resolve-labels after the path segments or write --resolve-labels=<lang>:' . LF
            . 'a following argument without "-" is taken as the language.' . LF . LF
            . 'For filtered or tabular views of single providers see configuration:show, debug:backend:routes,' . LF
            . 'debug:backend:modules, language:domain:list, site:sets:list, site:show and fluid:namespaces.'
        );
        $this->addArgument('provider', InputArgument::OPTIONAL, 'Identifier of the provider, as listed without arguments');
        $this->addArgument('path', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Keys to select, one argument per level');
        $this->addOption('flat', null, InputOption::VALUE_NONE, 'Output one line per value with its path instead of the tree');
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON instead of the tree');
        $this->addOption('depth', 'd', InputOption::VALUE_REQUIRED, 'Tree only: levels to show instead of the line budget, 0 for everything');
        $this->addOption('no-size-limit', null, InputOption::VALUE_NONE, 'flat and json only: allow output above 1 MB');
        $this->addOption('resolve-labels', null, InputOption::VALUE_OPTIONAL, 'Translate label references, in English or the given language', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errorIo = new SymfonyStyle($input, $output)->getErrorStyle();
        $this->registerStyles($output);

        $flat = (bool)$input->getOption('flat');
        $json = (bool)$input->getOption('json');
        $format = match (true) {
            $json => 'json',
            $flat => 'flat',
            default => 'tree',
        };
        $depth = $input->getOption('depth');
        $provider = $input->getArgument('provider');
        $noSizeLimit = (bool)$input->getOption('no-size-limit');
        $languageOption = $input->getOption('resolve-labels');
        $resolveLabels = $languageOption !== false;
        $language = is_string($languageOption) && $languageOption !== '' ? $languageOption : 'en';

        $invalidInput = match (true) {
            $flat && $json => '--flat and --json cannot be combined.',
            $depth !== null && $format !== 'tree' => '--depth only applies to the tree. --flat and --json are always complete; narrow them with path segments.',
            $depth !== null && $provider === null => '--depth needs a provider. The provider list has no depth.',
            $depth !== null && !preg_match('/^\d+$/', (string)$depth) => '--depth expects a number of levels, 0 for everything.',
            $noSizeLimit && $format === 'tree' => '--no-size-limit only applies to --flat and --json. The tree uses a ' . TreeRenderer::BUDGET . '-line budget; pass --depth=0 for everything.',
            !$this->locales->isValidLanguageKey($language) => sprintf('Unknown language "%s". Use a language code such as de, fr, en.', $language),
            default => null,
        };
        if ($invalidInput !== null) {
            $errorIo->error($invalidInput);
            return Command::INVALID;
        }

        $languageService = $this->languageServiceFactory->create($language);
        // Provider and group labels are always translated, in English unless another language is requested.
        $GLOBALS['LANG'] = $languageService;

        if ($provider === null) {
            return $this->listProviders($format, $languageService, $output);
        }
        if (!$this->providerRegistry->hasProvider($provider)) {
            $errorIo->error(sprintf('No provider "%s". Run "lowlevel:inspect" to list all providers.', $provider));
            return Command::INVALID;
        }
        try {
            $data = $this->dataNormalizer->normalize($this->providerRegistry->getProvider($provider)->getConfiguration());
        } catch (\Throwable $e) {
            $errorIo->error(sprintf('Provider "%s" cannot be shown on the command line: %s', $provider, $e->getMessage()));
            return Command::FAILURE;
        }

        $selected = [$provider];
        foreach ($input->getArgument('path') as $segment) {
            if (!is_array($data)) {
                $errorIo->error(sprintf('"%s" is a single value and has no key "%s".', implode(' ', $selected), $segment));
                return Command::INVALID;
            }
            if (!array_key_exists($segment, $data)) {
                $errorIo->error(sprintf('Key "%s" not found in "%s".', $segment, implode(' ', $selected)) . $this->didYouMean($segment, array_keys($data)));
                return Command::INVALID;
            }
            $data = $data[$segment];
            $selected[] = $segment;
        }
        if ($resolveLabels) {
            $data = $this->labelResolver->resolve($data, $languageService);
        }

        return match ($format) {
            'tree' => $this->writeLines($this->treeRenderer->render($data, $this->outputWidth($output), $depth === null ? null : (int)$depth), $output),
            'flat' => $this->writeFlat($this->flatRenderer->render($data), $noSizeLimit, $output, $errorIo),
            'json' => $this->writeJson($this->valueFormatter->toPlainData($data), $noSizeLimit, $output, $errorIo),
        };
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestOptionValuesFor('resolve-labels')) {
            $suggestions->suggestValues($this->locales->getActiveLanguages());
        } elseif ($input->mustSuggestArgumentValuesFor('provider')) {
            $suggestions->suggestValues(array_map('strval', array_keys($this->providerRegistry->getProviders())));
        } elseif ($input->mustSuggestArgumentValuesFor('path')) {
            $path = $input->getArgument('path');
            if ($path !== [] && end($path) === $input->getCompletionValue()) {
                array_pop($path);
            }
            $suggestions->suggestValues($this->keysAt((string)$input->getArgument('provider'), $path));
        }
    }

    private function listProviders(string $format, LanguageService $languageService, OutputInterface $output): int
    {
        $entries = [];
        foreach ($this->providerRegistry->getGroupedProviders() as $group => $providers) {
            $groupLabel = $this->providerRegistry->getGroupLabel((string)$group, $languageService);
            foreach ($providers as $provider) {
                $entries[] = [
                    'identifier' => $provider->getIdentifier(),
                    'label' => $provider->getLabel(),
                    'group' => (string)$group,
                    'groupLabel' => $groupLabel,
                ];
            }
        }
        if ($format === 'json') {
            $output->writeln($this->valueFormatter->json($entries), OutputInterface::OUTPUT_RAW);
            return Command::SUCCESS;
        }
        if ($format === 'flat') {
            $lines = [];
            foreach ($entries as $entry) {
                $lines[] = $this->valueFormatter->keys([$entry['identifier']])
                    ->append(' = ', StyledText::STYLE_DIM)
                    ->concat($this->valueFormatter->value($entry['label']));
            }
            return $this->writeLines($lines, $output);
        }
        $table = new Table($output);
        $table->setHeaders(['Identifier', 'Label']);
        $currentGroup = null;
        foreach ($entries as $entry) {
            if ($entry['group'] !== $currentGroup) {
                if ($currentGroup !== null) {
                    $table->addRow(new TableSeparator());
                }
                $currentGroup = $entry['group'];
                $table->addRow([new TableCell('<options=bold>' . $this->escapeTableCell($entry['groupLabel']) . '</>', ['colspan' => 2])]);
            }
            $table->addRow([
                '<' . StyledText::STYLE_KEY . '>' . $this->escapeTableCell($entry['identifier']) . '</>',
                $this->escapeTableCell($entry['label']),
            ]);
        }
        $table->render();
        return Command::SUCCESS;
    }

    /**
     * Table counts the backslash of an escaped ">" into the cell width, and only "<" opens a tag.
     */
    private function escapeTableCell(string $text): string
    {
        return str_replace('\\>', '>', OutputFormatter::escape($this->valueFormatter->escapeUnsafeCharacters($text)));
    }

    /**
     * @param list<StyledText> $lines
     */
    private function writeLines(array $lines, OutputInterface $output): int
    {
        foreach ($lines as $line) {
            $output->writeln($line->format());
        }
        return Command::SUCCESS;
    }

    /**
     * @param list<StyledText> $lines
     */
    private function writeFlat(array $lines, bool $noSizeLimit, OutputInterface $output, SymfonyStyle $errorIo): int
    {
        $plain = implode(LF, array_map(static fn(StyledText $line): string => $line->plain(), $lines));
        if ($this->exceedsSizeLimit($plain, $noSizeLimit, $errorIo)) {
            return Command::FAILURE;
        }
        return $this->writeLines($lines, $output);
    }

    private function writeJson(mixed $data, bool $noSizeLimit, OutputInterface $output, SymfonyStyle $errorIo): int
    {
        $json = $this->valueFormatter->json($data);
        if ($this->exceedsSizeLimit($json, $noSizeLimit, $errorIo)) {
            return Command::FAILURE;
        }
        $output->writeln($json, OutputInterface::OUTPUT_RAW);
        return Command::SUCCESS;
    }

    private function exceedsSizeLimit(string $content, bool $noSizeLimit, SymfonyStyle $errorIo): bool
    {
        if ($noSizeLimit || strlen($content) <= self::SIZE_LIMIT) {
            return false;
        }
        $errorIo->error(sprintf('Output is %s MB, above the 1 MB limit. Narrow the path or pass --no-size-limit.', number_format(strlen($content) / 1_000_000, 1)));
        return true;
    }

    /**
     * The terminal width, 120 columns without a terminal, COLUMNS overriding both.
     */
    private function outputWidth(OutputInterface $output): int
    {
        $isTerminal = $output instanceof StreamOutput && stream_isatty($output->getStream());
        $width = getenv('COLUMNS') !== false || $isTerminal ? new Terminal()->getWidth() : self::WIDTH_WITHOUT_TERMINAL;
        return max(self::MIN_WIDTH, $width);
    }

    private function registerStyles(OutputInterface $output): void
    {
        $formatter = $output->getFormatter();
        $formatter->setStyle(StyledText::STYLE_KEY, new OutputFormatterStyle('blue', null, ['bold']));
        $formatter->setStyle(StyledText::STYLE_STRING, new OutputFormatterStyle('green'));
        $formatter->setStyle(StyledText::STYLE_NUMBER, new OutputFormatterStyle('yellow'));
        $formatter->setStyle(StyledText::STYLE_NULL, new OutputFormatterStyle('gray'));
        $formatter->setStyle(StyledText::STYLE_DIM, new OutputFormatterStyle('gray'));
        $formatter->setStyle(StyledText::STYLE_ELLIPSIS, new OutputFormatterStyle('yellow', null, ['bold']));
        $formatter->setStyle(StyledText::STYLE_SECRET, new OutputFormatterStyle('red'));
        $formatter->setStyle(StyledText::STYLE_MARKER, new OutputFormatterStyle('magenta'));
    }

    /**
     * @param list<int|string> $keys
     */
    private function didYouMean(string $segment, array $keys): string
    {
        $distances = [];
        foreach ($keys as $key) {
            $distance = levenshtein($segment, (string)$key);
            if ($distance <= max(2, intdiv(strlen($segment), 3))) {
                $distances[(string)$key] = $distance;
            }
        }
        asort($distances);
        return $distances === [] ? '' : ' Did you mean: ' . $this->valueFormatter->escapeUnsafeCharacters(implode(', ', array_slice(array_keys($distances), 0, 3))) . '?';
    }

    /**
     * Keys at a path for shell completion, cached briefly as every TAB is a new process.
     *
     * @param list<string> $path
     * @return list<string>
     */
    private function keysAt(string $provider, array $path): array
    {
        $cacheIdentifier = 'keys_' . HashAlgo::canonicalize()->hash($provider . "\0" . implode("\0", $path));
        $keys = $this->completionCache->get($cacheIdentifier);
        if (is_array($keys)) {
            return $keys;
        }
        try {
            if (!$this->providerRegistry->hasProvider($provider)) {
                return [];
            }
            $data = $this->dataNormalizer->normalize($this->providerRegistry->getProvider($provider)->getConfiguration());
            foreach ($path as $segment) {
                if (!is_array($data) || !array_key_exists($segment, $data)) {
                    return [];
                }
                $data = $data[$segment];
            }
        } catch (\Throwable) {
            return [];
        }
        $keys = is_array($data) ? array_map('strval', array_keys($data)) : [];
        $this->completionCache->set($cacheIdentifier, $keys, [], self::COMPLETION_LIFETIME);
        return $keys;
    }
}
