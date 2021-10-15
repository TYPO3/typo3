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

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

if (PHP_SAPI !== 'cli') {
    die('Script must be called from command line.' . chr(10));
}

require __DIR__ . '/../../vendor/autoload.php';

/**
 * This script is typically executed by runTests.sh.
 *
 * The script expects to be run from the core root:
 * ./Build/Scripts/splitFunctionalTests.php <numberOfChunks>
 *
 * Verbose output with 8 chunks:
 * ./Build/Scripts/splitFunctionalTests.php 8 -v
 *
 * It's purpose is to find all core functional tests and split them into
 * pieces. In CI, there are for example 8 jobs for the functional tests and each
 * picks one chunk of tests. This way, functional tests are run in parallel and
 * thus reduce the overall runtime of the test suite.
 *
 * phpunit .xml config files including their specific set of tests are written to:
 * Build/Scripts/FunctionalTests-Job-<counter>.xml
 */
class SplitFunctionalTests
{
    /**
     * Main entry method
     */
    public function execute(): int
    {
        $input = new ArgvInput($_SERVER['argv'], $this->getInputDefinition());
        $output = new ConsoleOutput();

        // Number of chunks and verbose output
        $numberOfChunks = (int)$input->getArgument('numberOfChunks');

        if ($numberOfChunks < 1 || $numberOfChunks > 99) {
            throw new \InvalidArgumentException(
                'Main argument "numberOfChunks" must be at least 1 and maximum 99',
                1528319388
            );
        }

        if ($input->hasParameterOption('-v', true) || $input->hasParameterOption('--verbose', true)) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }

        // Find functional test files
        $testFiles = (new Finder())
            ->files()
            ->in(__DIR__ . '/../../typo3/sysext/*/Tests/Functional')
            ->name('/Test\.php$/')
            ->sortByName()
        ;

        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $testStats = [];
        foreach ($testFiles as $file) {
            /** @var $file SplFileInfo */
            $relativeFilename = $file->getRealPath();
            preg_match('/.*typo3\/sysext\/(.*)$/', $relativeFilename, $matches);
            $relativeFilename = '../../typo3/sysext/' . $matches[1];

            $ast = $parser->parse($file->getContents());
            $traverser = new NodeTraverser();
            $visitor = new NameResolver();
            $traverser->addVisitor($visitor);
            $visitor = new FunctionalTestCaseVisitor();
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            $fqcn = $visitor->getFqcn();
            $tests = $visitor->getTests();
            if (!empty($tests)) {
                $testStats[$relativeFilename] = 0;
            }

            foreach ($tests as $test) {
                if (isset($test['dataProvider'])) {
                    // Test uses a data provider - get number of data sets
                    $dataProviderMethodName = $test['dataProvider'];
                    $methods = (new $fqcn())->$dataProviderMethodName();
                    if ($methods instanceof Generator) {
                        $numberOfDataSets = iterator_count($methods);
                    } else {
                        $numberOfDataSets = count($methods);
                    }
                    $testStats[$relativeFilename] += $numberOfDataSets;
                } else {
                    // Just a single test
                    $testStats[$relativeFilename] += 1;
                }
            }
        }

        // Sort test files by number of tests, descending
        arsort($testStats);

        $this->createPhpunitXmlHeader($numberOfChunks);

        $numberOfTestsPerChunk = [];
        for ($i = 1; $i <= $numberOfChunks; $i++) {
            $numberOfTestsPerChunk[$i] = 0;
        }

        foreach ($testStats as $testFile => $numberOfTestsInFile) {
            // Sort list of tests per chunk by number of tests, pick lowest as
            // the target of this test file
            asort($numberOfTestsPerChunk);
            reset($numberOfTestsPerChunk);
            $jobFileNumber = key($numberOfTestsPerChunk);

            $content = <<<EOF
            <directory>
                $testFile
            </directory>

EOF;
            file_put_contents(__DIR__ . '/../phpunit/' . 'FunctionalTests-Job-' . $jobFileNumber . '.xml', $content, FILE_APPEND);

            $numberOfTestsPerChunk[$jobFileNumber] = $numberOfTestsPerChunk[$jobFileNumber] + $numberOfTestsInFile;
        }

        $this->createPhpunitXmlFooter($numberOfChunks);

        if ($output->isVerbose()) {
            $output->writeln('Number of test files found: ' . count($testStats));
            $output->writeln('Number of tests found: ' . array_sum($testStats));
            $output->writeln('Number of chunks prepared: ' . $numberOfChunks);
            ksort($numberOfTestsPerChunk);
            foreach ($numberOfTestsPerChunk as $chunkNumber => $testNumber) {
                $output->writeln('Number of tests in chunk ' . $chunkNumber . ': ' . $testNumber);
            }
        }

        return 0;
    }

    /**
     * Allowed script arguments
     *
     * @return InputDefinition argv input definition of symfony console
     */
    private function getInputDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputArgument('numberOfChunks', InputArgument::REQUIRED, 'Number of chunks / jobs to create'),
            new InputOption('--verbose', '-v', InputOption::VALUE_NONE, 'Enable verbose output'),
        ]);
    }

    /**
     * "Header" part of a phpunit.xml functional config file
     *
     * @param int $numberOfChunks
     */
    private function createPhpunitXmlHeader(int $numberOfChunks): void
    {
        $content = <<<EOF
<phpunit
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.3/phpunit.xsd"
    backupGlobals="true"
    bootstrap="FunctionalTestsBootstrap.php"
    colors="true"
    convertErrorsToExceptions="true"
    convertWarningsToExceptions="true"
    convertNoticesToExceptions="true"
    forceCoversAnnotation="false"
    stopOnError="false"
    stopOnFailure="false"
    stopOnIncomplete="false"
    stopOnSkipped="false"
    verbose="false"
    beStrictAboutTestsThatDoNotTestAnything="false"
    failOnWarning="true"
    failOnRisky="true"
>
    <php>
        <const name="TYPO3_TESTING_FUNCTIONAL_REMOVE_ERROR_HANDLER" value="true" />
    </php>
    <testsuites>
        <testsuite name="Core tests">

EOF;
        for ($i = 1; $i <= $numberOfChunks; $i++) {
            file_put_contents(__DIR__ . '/../phpunit/' . 'FunctionalTests-Job-' . $i . '.xml', $content);
        }
    }

    /**
     * "Footer" part of a phpunit.xml functional config file
     *
     * @param int $numberOfChunks
     */
    private function createPhpunitXmlFooter(int $numberOfChunks): void
    {
        $content = <<<EOF
        </testsuite>
    </testsuites>
</phpunit>

EOF;
        for ($i = 1; $i <= $numberOfChunks; $i++) {
            file_put_contents(__DIR__ . '/../phpunit/' . 'FunctionalTests-Job-' . $i . '.xml', $content, FILE_APPEND);
        }
    }
}

/**
 * nikic/php-parser node visitor to find test class namespace,
 * count @test annotated methods and their possible @dataProvider's
 */
class FunctionalTestCaseVisitor extends NodeVisitorAbstract
{
    /**
     * @var array[] An array of arrays with test method names and optionally a data provider name
     */
    private $tests = [];

    /**
     * @var string Fully qualified test class name
     */
    private $fqcn;

    /**
     * Create a list of '@test' annotated methods in a test case
     * file and see if single tests use data providers.
     *
     * @param Node $node
     */
    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_
            && !$node->isAnonymous()
        ) {
            // The test class full namespace
            $this->fqcn = (string)$node->namespacedName;
        }

        if ($node instanceof Node\Stmt\ClassMethod
            && ($docComment = $node->getDocComment()) instanceof Doc
        ) {
            preg_match_all(
                '/\s*\s@(?<annotations>[^\s.].*)\n/',
                $docComment->getText(),
                $matches
            );
            foreach ($matches['annotations'] as $possibleTest) {
                if ($possibleTest === 'test') {
                    // Found a test
                    $test = [
                        'methodName' => $node->name->name,
                    ];
                    foreach ($matches['annotations'] as $possibleDataProvider) {
                        // See if this test has a data provider attached
                        if (strpos($possibleDataProvider, 'dataProvider') === 0) {
                            $test['dataProvider'] = trim(ltrim($possibleDataProvider, 'dataProvider'));
                        }
                    }
                    $this->tests[] = $test;
                }
            }
        }
    }

    /**
     * Return array of found tests and their data providers
     *
     * @return array
     */
    public function getTests(): array
    {
        return $this->tests;
    }

    /**
     * Return Fully qualified class test name
     *
     * @return string
     */
    public function getFqcn(): string
    {
        return $this->fqcn;
    }
}

$splitFunctionalTests = new SplitFunctionalTests();
exit($splitFunctionalTests->execute());
