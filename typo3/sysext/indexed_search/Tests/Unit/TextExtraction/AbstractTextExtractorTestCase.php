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

namespace TYPO3\CMS\IndexedSearch\Tests\Unit\TextExtraction;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\MimeTypeDetector;
use TYPO3\CMS\IndexedSearch\TextExtraction\ExternalCommandExecutor;
use TYPO3\CMS\IndexedSearch\TextExtraction\ExternalToolConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

abstract class AbstractTextExtractorTestCase extends UnitTestCase
{
    protected string $workingDirectory = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->workingDirectory = sys_get_temp_dir() . '/indexed_search_' . bin2hex(random_bytes(6));
        mkdir($this->workingDirectory . '/bin', 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->workingDirectory . '/{,bin/}*', GLOB_BRACE) ?: [] as $path) {
            is_file($path) ? unlink($path) : @rmdir($path);
        }
        @rmdir($this->workingDirectory);
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $configuration extension configuration values
     * @param list<string> $installedTools dummy executables created in the configured tool directory
     */
    protected function createToolConfiguration(array $configuration = [], array $installedTools = []): ExternalToolConfiguration
    {
        foreach ($installedTools as $tool) {
            touch($this->workingDirectory . '/bin/' . $tool);
        }
        $configuration += [
            'pdftools' => $this->workingDirectory . '/bin',
            'catdoc' => $this->workingDirectory . '/bin',
            'ppthtml' => $this->workingDirectory . '/bin',
            'xlhtml' => $this->workingDirectory . '/bin',
            'unzip' => $this->workingDirectory . '/bin',
            'unrtf' => $this->workingDirectory . '/bin',
            'ignoreExtensions' => '',
            'pdf_mode' => -20,
        ];
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn($configuration);
        return new ExternalToolConfiguration($extensionConfiguration);
    }

    protected function createFile(string $name, string $content = ''): File
    {
        $path = $this->workingDirectory . '/' . $name;
        file_put_contents($path, $content);
        $file = self::createStub(File::class);
        $file->method('getName')->willReturn($name);
        $file->method('getIdentifier')->willReturn('/' . $name);
        $file->method('getResourceIdentifier')->willReturn('1:/' . $name);
        $file->method('getExtension')->willReturn(pathinfo($name, PATHINFO_EXTENSION));
        $file->method('getMimeType')->willReturn(new MimeTypeDetector()->getMimeTypesForFileExtension(pathinfo($name, PATHINFO_EXTENSION))[0] ?? 'application/octet-stream');
        $file->method('getForLocalProcessing')->willReturn($path);
        return $file;
    }

    /**
     * @param \Closure(string): list<string>|null $responder
     * @return ExternalCommandExecutor&object{commands: list<string>}
     */
    protected function createExecutor(?\Closure $responder = null): ExternalCommandExecutor
    {
        return new class ($responder) extends ExternalCommandExecutor {
            /** @var list<string> */
            public array $commands = [];

            public function __construct(private readonly ?\Closure $responder) {}

            public function execute(string $command): array
            {
                $this->commands[] = $command;
                return $this->responder ? ($this->responder)($command) : [];
            }
        };
    }
}
