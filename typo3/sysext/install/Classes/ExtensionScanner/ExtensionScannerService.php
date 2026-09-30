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

namespace TYPO3\CMS\Install\ExtensionScanner;

use PhpParser\Error as PhpParserError;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\ExtensionScanner\Php\CodeStatistics;
use TYPO3\CMS\Install\ExtensionScanner\Php\GeneratorClassesResolver;
use TYPO3\CMS\Install\ExtensionScanner\Php\MatcherFactory;
use TYPO3\CMS\Install\ExtensionScanner\Php\MatcherRegistry;
use TYPO3\CMS\Install\ExtensionScanner\Result\Indicator;
use TYPO3\CMS\Install\ExtensionScanner\Result\ScanMatch;
use TYPO3\CMS\Install\ExtensionScanner\Result\ScanResult;

/**
 * Scans PHP files for usages of removed or deprecated TYPO3 Core code.
 *
 * This is the engine behind the "Extension scanner" of the install tool. It reports what
 * static analysis found and where it came from; it deliberately does not resolve the
 * referenced changelog files to documents, and it does not read the matched source lines.
 * Both are presentation concerns of the caller.
 *
 * Not declared final only because the unit tests of EXT:install replace it with a test double;
 * substitutability is not a promise of this API.
 *
 * @todo When opening up the API for future TYPO3 versions, class should be made final and tests adjusted.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class ExtensionScannerService
{
    /**
     * Matcher configurations with their files already loaded, see getResolvedMatcherConfigurations().
     *
     * @var list<array<string, mixed>>|null
     */
    private ?array $resolvedMatcherConfigurations = null;

    /**
     * @internal Both arguments are internal to EXT:install. Obtain the service from the
     *           dependency injection container instead of constructing it.
     */
    public function __construct(
        private readonly MatcherRegistry $matcherRegistry,
        private readonly MatcherFactory $matcherFactory,
    ) {}

    /**
     * Collect PHP files below the given directory that can be handed to scanFile(), sorted by name.
     *
     * @return list<string> absolute file paths
     * @throws \RuntimeException if the path is not a directory
     */
    public function findScannableFiles(string $basePath): array
    {
        if (!is_dir($basePath)) {
            throw new \RuntimeException(
                'Path ' . $basePath . ' does not exist or is no directory.',
                1790540101
            );
        }

        $files = new Finder()
            ->files()
            ->ignoreUnreadableDirs()
            ->in($basePath)
            ->name('*.php')
            ->sortByName();

        $absoluteFilePaths = [];
        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            $absoluteFilePaths[] = GeneralUtility::fixWindowsFilePath($file->getPathname());
        }

        return $absoluteFilePaths;
    }

    /**
     * Scan a single file. A file that can not be parsed does not raise an exception: the
     * result carries the parser message instead, so that scanning many files is not aborted
     * by a single broken one.
     *
     * @throws \RuntimeException if the path is not a readable file
     */
    public function scanFile(string $absoluteFilePath): ScanResult
    {
        if (!is_file($absoluteFilePath) || !is_readable($absoluteFilePath)) {
            throw new \RuntimeException(
                'File ' . $absoluteFilePath . ' does not exist or is not readable.',
                1790540102
            );
        }

        $parser = new ParserFactory()->createForVersion(PhpVersion::fromComponents(8, 5));
        try {
            $statements = $parser->parse((string)file_get_contents($absoluteFilePath));
        } catch (PhpParserError $error) {
            return new ScanResult($absoluteFilePath, [], $error->getMessage(), false, 0, 0);
        }
        if ($statements === null) {
            return new ScanResult($absoluteFilePath, [], 'File could not be parsed.', false, 0, 0);
        }

        // The built in NameResolver translates class names shortened with 'use' to fully
        // qualified class names at all places. IMPORTANT: first process completely to resolve
        // fully qualified names of arguments, otherwise GeneratorClassesResolver will NOT get
        // reliable results.
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $statements = $traverser->traverse($statements);

        // IMPORTANT: second process to actually work on the pre-resolved statements
        $traverser = new NodeTraverser();
        // Understand GeneralUtility::makeInstance('My\\Package\\Foo\\Bar') as fqdn class name
        // in first argument
        $traverser->addVisitor(new GeneratorClassesResolver());
        // Count ignored lines, effective code lines, ...
        $statistics = new CodeStatistics();
        $traverser->addVisitor($statistics);

        // Fresh matcher instances per file, but the rule set is read from disk only once:
        // AbstractCoreMatcher keeps a file level ignore flag in instance state, so instances
        // must not be reused, while the configuration behind them never changes.
        $matchers = $this->matcherFactory->createAll($this->getResolvedMatcherConfigurations());
        foreach ($matchers as $matcher) {
            $traverser->addVisitor($matcher);
        }
        $traverser->traverse($statements);

        $matches = [];
        foreach ($matchers as $matcher) {
            /** @var CodeScannerInterface $matcher */
            $matcherName = $this->getShortClassName($matcher::class);
            foreach ($matcher->getMatches() as $match) {
                $matches[] = new ScanMatch(
                    (int)$match['line'],
                    Indicator::from((string)$match['indicator']),
                    $matcherName,
                    (string)$match['message'],
                    array_values($match['restFiles']),
                );
            }
        }

        return new ScanResult(
            $absoluteFilePath,
            $matches,
            null,
            $statistics->isFileIgnored(),
            $statistics->getNumberOfEffectiveCodeLines(),
            $statistics->getNumberOfIgnoredLines(),
        );
    }

    /**
     * The registry declares each matcher with a configuration file. Requiring those 23 files
     * for every scanned file costs seconds over a whole extension and buys nothing, so the
     * loaded configurations are kept and handed to the factory as configurationArray.
     *
     * @return list<array<string, mixed>>
     * @throws \RuntimeException if a declared configuration file is missing or malformed
     */
    private function getResolvedMatcherConfigurations(): array
    {
        if ($this->resolvedMatcherConfigurations !== null) {
            return $this->resolvedMatcherConfigurations;
        }

        $resolved = [];
        foreach ($this->matcherRegistry->getMatcherConfigurations() as $configuration) {
            if (!isset($configuration['configurationFile']) || isset($configuration['configurationArray'])) {
                // Nothing to load, or already loaded: let the factory validate it.
                $resolved[] = $configuration;
                continue;
            }
            $absolutePath = GeneralUtility::getFileAbsFileName($configuration['configurationFile']);
            if ($absolutePath === '' || !is_file($absolutePath)) {
                throw new \RuntimeException(
                    'Configuration file ' . $configuration['configurationFile'] . ' not found',
                    1790540105
                );
            }
            $matcherConfiguration = require $absolutePath;
            if (!is_array($matcherConfiguration)) {
                throw new \RuntimeException(
                    'Configuration file ' . $configuration['configurationFile'] . ' must return an array',
                    1790540106
                );
            }
            $resolved[] = [
                'class' => $configuration['class'],
                'configurationArray' => $matcherConfiguration,
            ];
        }

        return $this->resolvedMatcherConfigurations = $resolved;
    }

    private function getShortClassName(string $fullyQualifiedClassName): string
    {
        $separatorPosition = strrpos($fullyQualifiedClassName, '\\');

        return $separatorPosition === false
            ? $fullyQualifiedClassName
            : substr($fullyQualifiedClassName, $separatorPosition + 1);
    }
}
