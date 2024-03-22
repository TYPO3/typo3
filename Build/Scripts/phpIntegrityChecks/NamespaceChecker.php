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

namespace TYPO3\CMS\PhpIntegrityChecks;

use PhpParser\Node;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * check namespaces in classes comply with PSR-4, report on any not doing so
 */
final class NamespaceChecker extends AbstractPhpIntegrityChecker
{
    /**
     * @var string[]
     */
    protected array $excludedFileNames = [
        'ConditionMatcherUserFuncs.php',
        'PropertyExistsStaticMatcherFixture.php',
    ];

    /**
     * @var string[]
     */
    protected array $excludedDirectories = [
        'typo3/sysext/core/Tests/Unit/Core/Fixtures/test_extension/',
    ];

    protected string $filePathPrefix = 'typo3/sysext/';
    protected string $filePathPrefixRegex = '/.*typo3\/sysext\/(.*)$/';

    private string $type = '';
    private string $fullQualifiedObjectNamespace = '';
    private string $sysExtName;
    private string $expectedFullQualifiedNamespace;
    private string $relativeFilenameFromRoot;

    public function startProcessing(\SplFileInfo $file): void
    {
        parent::startProcessing($file);
        $this->type = '';
        $this->fullQualifiedObjectNamespace = '';
        $this->sysExtName = '';
        $this->expectedFullQualifiedNamespace = '';
        $this->relativeFilenameFromRoot = '';
        $ignoreNamespaceParts = ['Classes'];
        $fullFilename = $file->getRealPath();

        preg_match($this->filePathPrefixRegex, $fullFilename, $matches);
        $this->relativeFilenameFromRoot = $this->filePathPrefix . $matches[1];
        $parts = explode('/', $matches[1]);
        $this->sysExtName = $parts[0];
        unset($parts[0]);
        if (in_array($parts[1], $ignoreNamespaceParts, true)) {
            unset($parts[1]);
        }

        $relativeFilenameWithoutSystemExtensionRoot = substr($this->relativeFilenameFromRoot, (mb_strlen($this->filePathPrefix . $this->sysExtName . '/')));
        $this->expectedFullQualifiedNamespace = $this->determineExpectedFullQualifiedNamespace($this->sysExtName, $relativeFilenameWithoutSystemExtensionRoot);
    }

    public function enterNode(Node $node): void
    {
        if ($this->type !== '') {
            return;
        }
        if ($node instanceof Node\Stmt\Class_ && !$node->isAnonymous()) {
            $this->type = 'class';
            $this->fullQualifiedObjectNamespace = (string)$node->namespacedName;
            return;
        }
        if ($node instanceof Node\Stmt\Interface_) {
            $this->type = 'interface';
            $this->fullQualifiedObjectNamespace = (string)$node->namespacedName;
            return;
        }
        if ($node instanceof Node\Stmt\Enum_) {
            $this->type = 'enum';
            $this->fullQualifiedObjectNamespace = (string)$node->namespacedName;
            return;
        }
        if ($node instanceof Node\Stmt\Trait_) {
            $this->type = 'trait';
            $this->fullQualifiedObjectNamespace = (string)$node->namespacedName;
        }
    }

    public function finishProcessing(): void
    {
        $fileObjectType = $this->getType();
        $fileObjectFullQualifiedObjectNamespace = $this->getFullQualifiedObjectNamespace();
        if ($fileObjectType !== ''
            && $this->expectedFullQualifiedNamespace !== $fileObjectFullQualifiedObjectNamespace
        ) {
            $this->messages[$this->sysExtName][] = [
                'file' => $this->relativeFilenameFromRoot,
                'shouldBe' => $this->expectedFullQualifiedNamespace,
                'actualIs' => $fileObjectFullQualifiedObjectNamespace,
            ];
        }
    }

    public function outputResult(SymfonyStyle $io, array $issueCollection): void
    {
        $io->title('Namespace Checker result');
        if ($issueCollection !== []) {
            $io->error('Namespace integrity broken.');
            $table = new Table($io);
            $table->setHeaders([
                'EXT',
                'File',
                'should be',
                'actual is',
            ]);
            foreach ($issueCollection as $extKey => $issues) {
                foreach ($issues as $result) {
                    $table->addRow([
                        $extKey,
                        $result['file'],
                        $result['shouldBe'] ?: 'no proper registered PSR-4 namespace',
                        $result['actualIs'],
                    ]);
                }
            }
            $table->render();
        } else {
            $io->success(' Namespace integrity is in good shape.');
        }
    }

    protected function getType(): string
    {
        return $this->type;
    }

    protected function getFullQualifiedObjectNamespace(): string
    {
        return $this->fullQualifiedObjectNamespace;
    }

    protected function determineExpectedFullQualifiedNamespace(
        string $systemExtensionKey,
        string $relativeFilename,
    ): string {
        $namespace = '';
        if (str_starts_with($relativeFilename, 'Classes/')) {
            $namespace = $this->getExtensionClassesNamespace($systemExtensionKey, $relativeFilename);
        } elseif (str_starts_with($relativeFilename, 'Tests/')) {
            // for test fixture extensions, the relativeFileName will be shortened by the sysext file path,
            // therefor the variable gets passed as reference here
            $namespace = $this->getExtensionTestsNamespaces($systemExtensionKey, $relativeFilename);
        }
        $ignorePartValues = ['Classes', 'Tests'];
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
            __DIR__ . '/../../../' . $this->filePathPrefix . $systemExtensionKey . '/composer.json',
            $relativeFilename
        );
    }

    protected function getExtensionTestsNamespaces(
        string $systemExtensionKey,
        string &$relativeFilename
    ): string {
        return $this->getPSR4NamespaceFromComposerJson(
            $systemExtensionKey,
            __DIR__ . '/../../../composer.json',
            $relativeFilename,
            true
        );
    }

    protected function getPSR4NamespaceFromComposerJson(
        string $systemExtensionKey,
        string $fullComposerJsonFilePath,
        string &$relativeFileName,
        bool $autoloadDev = false
    ): string {
        $autoloadKey = 'autoload';
        if ($autoloadDev) {
            $autoloadKey .= '-dev';
        }
        if (!file_exists($fullComposerJsonFilePath)) {
            return '';
        }
        try {
            $composerInfo = \json_decode(
                json: (string)file_get_contents($fullComposerJsonFilePath),
                flags: JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR,
            );
            if (is_array($composerInfo)) {
                $autoloadPSR4 = $composerInfo[$autoloadKey]['psr-4'] ?? [];

                $pathBasedAutoloadInformation = [];
                foreach ($autoloadPSR4 as $namespace => $relativePath) {
                    $pathBasedAutoloadInformation[trim($relativePath, '/') . '/'] = $namespace;
                }
                $keys = array_map(mb_strlen(...), array_keys($pathBasedAutoloadInformation));
                array_multisort($keys, SORT_DESC, $pathBasedAutoloadInformation);

                foreach ($pathBasedAutoloadInformation as $relativePath => $namespace) {

                    if ($autoloadDev && str_starts_with($this->filePathPrefix . $systemExtensionKey . '/' . $relativeFileName, $relativePath)) {
                        $relativePath = mb_substr($relativePath, mb_strlen($this->filePathPrefix . $systemExtensionKey . '/'));
                        if (str_starts_with($relativeFileName, $relativePath)) {
                            $relativeFileName = mb_substr($relativeFileName, mb_strlen($relativePath));
                        }
                        return $namespace;
                    }
                    if (str_starts_with($relativeFileName, $relativePath)) {
                        return $namespace;
                    }
                }
            }
        } catch (\JsonException) {
        }

        return '';
    }
}
