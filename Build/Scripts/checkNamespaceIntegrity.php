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

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

if (PHP_SAPI !== 'cli') {
    die('Script must be called from command line.' . chr(10));
}

require __DIR__ . '/../../vendor/autoload.php';

/**
 * Class to scan for invalid namespaces.
 */
class CheckNamespaceIntegrity
{
    public function scan(): int
    {
        $ignoreFiles = [
            // ignored, pure fixture file
            'typo3/sysext/core/Tests/Unit/Configuration/TypoScript/ConditionMatching/Fixtures/ConditionMatcherUserFuncs.php',
            // ignored, pure fixture file
            'typo3/sysext/install/Tests/Unit/ExtensionScanner/Php/Matcher/Fixtures/PropertyExistsStaticMatcherFixture.php',
        ];
        $ignoreNamespaceParts = ['Classes'];
        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $files = $this->createFinder();
        $invalidNamespaces = [];
        foreach ($files as $file) {
            /** @var $file SplFileInfo */
            $fullFilename = $file->getRealPath();
            preg_match('/.*typo3\/sysext\/(.*)$/', $fullFilename, $matches);
            $relativeFilenameFromRoot = 'typo3/sysext/' . $matches[1];
            if (in_array($relativeFilenameFromRoot, $ignoreFiles, true)) {
                continue;
            }
            $parts = explode('/', $matches[1]);
            $sysExtName = $parts[0];
            unset($parts[0]);
            if (in_array($parts[1], $ignoreNamespaceParts, true)) {
                unset($parts[1]);
            }

            $relativeFilenameWithoutSystemExtensionRoot = substr($relativeFilenameFromRoot, (mb_strlen('typo3/sysext/' . $sysExtName . '/')));
            $expectedFullQualifiedObjectNamespace = $this->determineExpectedFullQualifiedNamespace($sysExtName, $relativeFilenameWithoutSystemExtensionRoot);
            $ast = $parser->parse($file->getContents());
            $traverser = new NodeTraverser();
            $visitor = new NameResolver();
            $traverser->addVisitor($visitor);
            $visitor = new NamespaceValidationVisitor();
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            $fileObjectType = $visitor->getType();
            $fileObjectFullQualifiedObjectNamespace = $visitor->getFullQualifiedObjectNamespace();
            if ($fileObjectType !== ''
                && $expectedFullQualifiedObjectNamespace !== $fileObjectFullQualifiedObjectNamespace
            ) {
                $invalidNamespaces[$sysExtName][] = [
                    'file' => $relativeFilenameFromRoot,
                    'shouldBe' => $expectedFullQualifiedObjectNamespace,
                    'actualIs' => $fileObjectFullQualifiedObjectNamespace,
                ];
            }
        }

        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $output->writeln('');
        if ($invalidNamespaces !== []) {
            $output->writeln(' ❌ Namespace integrity broken.');
            $output->writeln('');
            $table = new \Symfony\Component\Console\Helper\Table($output);
            $table->setHeaders([
                'EXT',
                'File',
                'should be',
                'actual is',
            ]);
            foreach ($invalidNamespaces as $extKey => $results) {
                foreach ($results as $result) {
                    $table->addRow([
                        $extKey,
                        $result['file'],
                        $result['shouldBe'] ?: '❌ no proper registered PSR-4 namespace',
                        $result['actualIs'],
                    ]);
                }
            }
            $table->render();
            $output->writeln('');
            $output->writeln('');
            return 1;
        }
        $output->writeln(' ✅ Namespace integrity is in good shape.');
        $output->writeln('');
        return 0;
    }

    protected function determineExpectedFullQualifiedNamespace(
        string $systemExtensionKey,
        string $relativeFilename,
    ): string {
        $namespace = '';
        if (str_starts_with($relativeFilename, 'Classes/')) {
            $namespace = $this->getExtensionClassesNamespace($systemExtensionKey, $relativeFilename);
        } elseif (str_starts_with($relativeFilename, 'Tests/')) {
            $namespace = $this->getExtensionTestsNamespaces($systemExtensionKey, $relativeFilename);
        }
        $ignorePartValues= ['Classes', 'Tests'];
        if ($namespace !== '') {
            $parts = explode('/', $relativeFilename);
            if (in_array($parts[0], $ignorePartValues, true)) {
                unset($parts[0]);
            }
            foreach ($parts as $part) {
                if (str_ends_with($part, '.php')) {
                    $namespace .= mb_substr($part, 0, -4);
                    break;
                }
                $namespace .= $part . '\\';
            }
        }
        return $namespace;
    }

    protected function getExtensionClassesNamespace(
        string $systemExtensionKey,
        string $relativeFilename
    ): string {
        return $this->getPSR4NamespaceFromComposerJson(
            $systemExtensionKey,
            __DIR__ . '/../../typo3/sysext/' . $systemExtensionKey . '/composer.json',
            $relativeFilename
        );
    }

    protected function getExtensionTestsNamespaces(
        string $systemExtensionKey,
        string $relativeFilename
    ): string {
        return $this->getPSR4NamespaceFromComposerJson(
            $systemExtensionKey,
            __DIR__ . '/../../composer.json',
            $relativeFilename,
            true
        );
    }

    protected function getPSR4NamespaceFromComposerJson(
        string $systemExtensionKey,
        string $fullComposerJsonFilePath,
        string $relativeFileName,
        bool $autoloadDev=false
    ): string {
        $autoloadKey = 'autoload';
        if ($autoloadDev) {
            $autoloadKey .= '-dev';
        }
        if (file_exists($fullComposerJsonFilePath)) {
            $composerInfo = \json_decode(
                file_get_contents($fullComposerJsonFilePath),
                true
            );
            if (is_array($composerInfo)) {
                $autoloadPSR4 = $composerInfo[$autoloadKey]['psr-4'] ?? [];

                $pathBasedAutoloadInformation = [];
                foreach ($autoloadPSR4 as $namespace => $relativePath) {
                    $pathBasedAutoloadInformation[trim($relativePath, '/') . '/'] = $namespace;
                }
                $keys = array_map('mb_strlen', array_keys($pathBasedAutoloadInformation));
                array_multisort($keys, SORT_DESC, $pathBasedAutoloadInformation);

                foreach ($pathBasedAutoloadInformation as $relativePath => $namespace) {
                    if ($autoloadDev && str_starts_with('typo3/sysext/' . $systemExtensionKey . '/' . $relativeFileName, $relativePath)) {
                        return $namespace;
                    }
                    if (str_starts_with($relativeFileName, $relativePath)) {
                        return $namespace;
                    }
                }
            }
        }
        return '';
    }

    protected function createFinder(): Finder
    {
        return (new Finder())
            ->files()
            ->in(
                dirs: [
                    __DIR__ . '/../../typo3/sysext/*/Classes',
                    __DIR__ . '/../../typo3/sysext/*/Tests/Unit',
                    __DIR__ . '/../../typo3/sysext/*/Tests/UnitDeprecated',
                    __DIR__ . '/../../typo3/sysext/*/Tests/Functional',
                    __DIR__ . '/../../typo3/sysext/*/Tests/FunctionalDeprecated',
                    __DIR__ . '/../../typo3/sysext/core/Tests/Acceptance',
                ]
            )
            ->notPath('typo3/sysext/core/Tests/Acceptance/Support/_generated')
            // @todo remove fixture extensions exclude and handle properly after fixture extensions has been streamlined
            ->notPath([
                'Fixtures/Extensions',
                'Fixtures/Extension',
                'Fixture/Extensions',
                'Fixture/Extension',
                'Core/Fixtures/test_extension',
            ])
            ->name('*.php')
            ->sortByName();
    }
}

/**
 * nikic/php-parser node visitor fo find namespace information
 */
class NamespaceValidationVisitor extends NodeVisitorAbstract
{
    private string $type = '';
    private string $fullQualifiedObjectNamespace = '';

    public function enterNode(Node $node)
    {
        if ($this->type === '') {
            if ($node instanceof Node\Stmt\Class_
                && !$node->isAnonymous()
            ) {
                $this->type = 'class';
                $this->fullQualifiedObjectNamespace = (string)$node->namespacedName;
            }
            if ($node instanceof Node\Stmt\Interface_) {
                $this->type = 'interface';
                $this->fullQualifiedObjectNamespace = (string)$node->namespacedName;
            }
            if ($node instanceof Node\Stmt\Enum_) {
                $this->type = 'enum';
                $this->fullQualifiedObjectNamespace = (string)$node->namespacedName;
            }
            if ($node instanceof Node\Stmt\Trait_) {
                $this->type = 'trait';
                $this->fullQualifiedObjectNamespace = (string)$node->namespacedName;
            }
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFullQualifiedObjectNamespace(): string
    {
        return $this->fullQualifiedObjectNamespace;
    }
}

// execute scan and return corresponding exit code.
// 0: everything ok
// 1: failed, one or more files has invalid namespace declaration
exit((new CheckNamespaceIntegrity())->scan());
