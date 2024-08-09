#!/usr/bin/env php
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

use Composer\Console\Application;
use Composer\Console\Input\InputOption;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\PhpIntegrityChecks\AbstractPhpIntegrityChecker;
use TYPO3\CMS\PhpIntegrityChecks\AnnotationChecker;
use TYPO3\CMS\PhpIntegrityChecks\ExceptionCodeChecker;
use TYPO3\CMS\PhpIntegrityChecks\NamespaceChecker;
use TYPO3\CMS\PhpIntegrityChecks\NodeResolver\ExceptionConstructorResolver;
use TYPO3\CMS\PhpIntegrityChecks\TestClassFinalChecker;
use TYPO3\CMS\PhpIntegrityChecks\TestMethodPrefixChecker;

require_once __DIR__ . '/../../vendor/autoload.php';

final class PhpIntegrityChecker extends Command
{
    /**
     * @var class-string[]
     */
    private array $registeredVisitors = [
        AnnotationChecker::class,
        NamespaceChecker::class,
        TestMethodPrefixChecker::class,
        TestClassFinalChecker::class,
        ExceptionCodeChecker::class,
    ];

    /**
     * @var string[]
     */
    private array $finderFindIn = [
        __DIR__ . '/../../typo3/sysext/*/Classes',
        __DIR__ . '/../../typo3/sysext/*/Tests/Unit',
        __DIR__ . '/../../typo3/sysext/*/Tests/Functional',
        __DIR__ . '/../../typo3/sysext/*/Tests/FunctionalDeprecated',
        __DIR__ . '/../../typo3/sysext/core/Tests/Acceptance',
    ];

    /**
     * @var string[]
     */
    private array $finderNotPath = [
        'typo3/sysext/core/Tests/Acceptance/Support/_generated',
        // exclude some files not providing classes
        'typo3/sysext/*/Configuration',
    ];

    /**
     * @var string[]
     */
    private array $finderNotName = [
        'ext_emconf.php',
    ];

    /**
     * @var array<class-string, AbstractPhpIntegrityChecker>
     */
    private array $visitors = [];

    /**
     * @var array<class-string, array<string, string[]>>
     */
    private array $issues = [];
    private PhpVersion $phpVersion;

    protected function configure(): void
    {
        $this->addOption('php', 'p', InputOption::VALUE_OPTIONAL, 'the php version to use, like 8.2 or 7.4', '8.2');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = explode('.', $input->getOption('php'));
        $this->phpVersion = PhpVersion::fromComponents((int)$version[0], (int)$version[1]);
        $this->initVisitors();
        $io = new SymfonyStyle($input, $output);
        $parser = $this->getParser();

        foreach ($this->createFinder() as $file) {
            $this->processFile($parser, $file);
        }

        return $this->displayIssues($io);
    }

    /**
     * Display issues, grouped by visitor and return command exit code.
     */
    private function displayIssues(SymfonyStyle $io): int
    {
        $exitCode = Command::SUCCESS;
        foreach ($this->issues as $visitorClassName => $issueCollection) {
            if ($issueCollection !== []) {
                $exitCode = Command::FAILURE;
            }
            // PHP Syntax parsing errors are not a visitor, and thus need adjusted handling here.
            if ($visitorClassName === 'parsing') {
                $io->title('Parsing errors');
                $io->error(' Following files were not checked:');
                foreach ($issueCollection as $file => $issue) {
                    $io->writeln('  > ' . $file . ': ' . $issue);
                }
                continue;
            }
            $this->visitors[$visitorClassName]->outputResult($io, $issueCollection);
            $io->newLine();
        }

        return $exitCode;
    }

    /**
     * Process code integrity checks for specified file.
     */
    private function processFile(Parser $parser, SplFileInfo $file): void
    {
        try {
            $ast = $parser->parse($file->getContents());
        } catch (Error $error) {
            $this->issues['parsing'][$file->getRealPath()] = 'Parse error: ' . $error->getMessage();
            return;
        }

        /** @var array<class-string, AbstractPhpIntegrityChecker> $usedVisitors */
        $usedVisitors = [];
        $ast = $this->enrichAst($ast);
        $this->createTraverser($file, $usedVisitors)->traverse($ast);
        $this->finishUsedVisitorsForFile($file, $usedVisitors);
    }

    /**
     * @param Node[] $ast
     * @return Node[]
     */
    private function enrichAst(array $ast): array
    {
        $nameResolver = new NameResolver();
        $exceptionConstructorResolver = new ExceptionConstructorResolver();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($exceptionConstructorResolver);
        return $traverser->traverse($ast);
    }

    /**
     * @param SplFileInfo $file
     * @param array<class-string, AbstractPhpIntegrityChecker> &$usedVisitors
     * @return NodeTraverser
     */
    private function createTraverser(SplFileInfo $file, array &$usedVisitors): NodeTraverser
    {
        $traverser = new NodeTraverser();
        foreach ($this->visitors as $visitorClassName => $visitor) {
            if (!$visitor->canHandle($file)) {
                continue;
            }
            $usedVisitors[$visitorClassName] = $visitor;
            $visitor->startProcessing($file);
            $traverser->addVisitor($visitor);
        }
        return $traverser;
    }

    /**
     * @param array<class-string, AbstractPhpIntegrityChecker> $usedVisitors
     */
    private function finishUsedVisitorsForFile(SplFileInfo $file, array $usedVisitors): void
    {
        foreach ($usedVisitors as $visitorClassName => $visitor) {
            $visitor->finishProcessing();
            $messages = $visitor->getMessages();
            if ($messages !== []) {
                $this->issues[$visitorClassName] = $messages;
            }
        }
    }

    private function createFinder(): Finder
    {
        return (new Finder())
            ->files()
            ->in($this->finderFindIn)
            ->notPath($this->finderNotPath)
            ->notName($this->finderNotName)
            ->name('*.php')
            ->sortByName();
    }

    private function initVisitors(): void
    {
        foreach ($this->registeredVisitors as $registeredVisitor) {
            $visitorInstance = new $registeredVisitor();
            $this->visitors[$registeredVisitor] = $visitorInstance;
            $this->issues[$registeredVisitor] = [];
        }
    }

    private function getParser(): Parser
    {

        return (new ParserFactory())->createForVersion($this->phpVersion);
    }
}

$application = new Application('Integrity Check');
$name = 'integrity_checker';
$application->add(new PhpIntegrityChecker($name));
$application->setDefaultCommand($name, true);
$application->run();
