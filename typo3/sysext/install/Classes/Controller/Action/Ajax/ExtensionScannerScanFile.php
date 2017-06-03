<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\ExtensionScanner\Php\CodeStatistics;
use TYPO3\CMS\Install\ExtensionScanner\Php\GeneratorClassesResolver;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ArrayDimensionMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ArrayGlobalMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ClassConstantMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ClassNameMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\InterfaceMethodChangedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentDroppedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentDroppedStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentRequiredMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentUnusedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodCallMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodCallStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyProtectedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyPublicMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\MatcherFactory;
use TYPO3\CMS\Install\UpgradeAnalysis\DocumentationFile;

/**
 * Scan a single extension file for breaking / deprecated core code usages
 */
class ExtensionScannerScanFile extends AbstractAjaxAction
{
    /**
     * @var array Node visitors that implement CodeScannerInterface
     */
    protected $matchers = [
        [
            'class' => ArrayDimensionMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ArrayDimensionMatcher.php',
        ],
        [
            'class' => ArrayGlobalMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ArrayGlobalMatcher.php',
        ],
        [
            'class' => ClassConstantMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ClassConstantMatcher.php',
        ],
        [
            'class' => ClassNameMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ClassNameMatcher.php',
        ],
        [
            'class' => InterfaceMethodChangedMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/InterfaceMethodChangedMatcher.php',
        ],
        [
            'class' => MethodArgumentDroppedMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentDroppedMatcher.php',
        ],
        [
            'class' => MethodArgumentDroppedStaticMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentDroppedStaticMatcher.php',
        ],
        [
            'class' => MethodArgumentRequiredMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentRequiredMatcher.php',
        ],
        [
            'class' => MethodArgumentUnusedMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentUnusedMatcher.php',
        ],
        [
            'class' => MethodCallMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodCallMatcher.php',
        ],
        [
            'class' => MethodCallStaticMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodCallStaticMatcher.php',
        ],
        [
            'class' => PropertyProtectedMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/PropertyProtectedMatcher.php',
        ],
        [
            'class' => PropertyPublicMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/PropertyPublicMatcher.php',
        ],
    ];

    /**
     * Find code violations in a single file
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function executeAction(): array
    {
        // Get and validate path and file
        $extension = $this->postValues['extension'];
        $extensionBasePath = PATH_site . 'typo3conf/ext/' . $extension;
        if (empty($extension) || !GeneralUtility::isAllowedAbsPath($extensionBasePath)) {
            throw new \RuntimeException(
                'Path to extension ' . $extension . ' not allowed.',
                1499789246
            );
        }
        if (!is_dir($extensionBasePath)) {
            throw new \RuntimeException(
                'Extension path ' . $extensionBasePath . ' does not exist or is no directory.',
                1499789259
            );
        }
        $file = $this->postValues['file'];
        $absoluteFilePath = $extensionBasePath . '/' . $file;
        if (empty($file) || !GeneralUtility::isAllowedAbsPath($absoluteFilePath)) {
            throw new \RuntimeException(
                'Path to file ' . $file . ' of extension ' . $extension . ' not allowed.',
                1499789384
            );
        }
        if (!is_file($absoluteFilePath)) {
            throw new \RuntimeException(
                'File ' . $file . ' not found or is not a file.',
                1499789433
            );
        }

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        // Parse PHP file to AST and traverse tree calling visitors
        $statements = $parser->parse(file_get_contents($absoluteFilePath));

        $traverser = new NodeTraverser();
        // The built in NameResolver translates class names shortened with 'use' to fully qualified
        // class names at all places. Incredibly useful for us and added as first visitor.
        $traverser->addVisitor(new NameResolver());
        // Understand makeInstance('My\\Package\\Foo\\Bar') as fqdn class name in first argument
        $traverser->addVisitor(new GeneratorClassesResolver());
        // Count ignored lines, effective code lines, ...
        $statistics = new CodeStatistics();
        $traverser->addVisitor($statistics);

        // Add all configured matcher classes
        $matcherFactory = new MatcherFactory();
        $matchers = $matcherFactory->createAll($this->matchers);
        foreach ($matchers as $matcher) {
            $traverser->addVisitor($matcher);
        }

        $traverser->traverse($statements);

        // Gather code matches
        $matches = [];
        foreach ($matchers as $matcher) {
            $matches = array_merge($matches, $matcher->getMatches());
        }

        // Prepare match output
        $restFilesBasePath = PATH_site . ExtensionManagementUtility::siteRelPath('core') . 'Documentation/Changelog';
        $documentationFile = new DocumentationFile();
        $preparedMatches = [];
        foreach ($matches as $match) {
            $preparedHit = [];
            $preparedHit['uniqueId'] = str_replace('.', '', uniqid((string)mt_rand(), true));
            $preparedHit['message'] = $match['message'];
            $preparedHit['line'] = $match['line'];
            $preparedHit['indicator'] = $match['indicator'];
            $preparedHit['lineContent'] = $this->getLineFromFile($absoluteFilePath, $match['line']);
            $preparedHit['restFiles'] = [];
            foreach ($match['restFiles'] as $fileName) {
                $finder = new Finder();
                $restFileLocation = $finder->files()->in($restFilesBasePath)->name($fileName);
                if ($restFileLocation->count() !== 1) {
                    throw new \RuntimeException(
                        'ResT file ' . $fileName . ' not found or multiple files found.',
                        1499803909
                    );
                }
                foreach ($restFileLocation as $restFile) {
                    /** @var SplFileInfo $restFile */
                    $restFileLocation = $restFile->getPathname();
                    break;
                }
                $parsedRestFile = array_pop($documentationFile->getListEntry(realpath($restFileLocation)));
                $version = GeneralUtility::trimExplode('/', $restFileLocation);
                array_pop($version);
                // something like "8.2" .. "8.7" .. "master"
                $parsedRestFile['version'] = array_pop($version);
                $parsedRestFile['uniqueId'] = str_replace('.', '', uniqid((string)mt_rand(), true));
                $preparedHit['restFiles'][] = $parsedRestFile;
            }
            $preparedMatches[] = $preparedHit;
        }

        $this->view->assignMultiple([
            'success' => true,
            'matches' => $preparedMatches,
            'isFileIgnored' => $statistics->isFileIgnored(),
            'effectiveCodeLines' => $statistics->getNumberOfEffectiveCodeLines(),
            'ignoredLines' => $statistics->getNumberOfIgnoredLines(),
        ]);
        return $this->view->render();
    }

    /**
     * Find a code line in a file
     *
     * @param string $file Absolute path to file
     * @param int $lineNumber Find this line in file
     * @return string Code line
     */
    protected function getLineFromFile(string $file, int $lineNumber): string
    {
        $fileContent = file($file, FILE_IGNORE_NEW_LINES);
        $line = '';
        if (isset($fileContent[$lineNumber - 1])) {
            $line = trim($fileContent[$lineNumber - 1]);
        }
        return $line;
    }
}
