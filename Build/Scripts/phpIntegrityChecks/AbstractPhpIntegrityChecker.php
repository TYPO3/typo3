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

use PhpParser\NodeVisitorAbstract;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractPhpIntegrityChecker extends NodeVisitorAbstract
{
    /**
     * @var array<int|string, mixed>
     */
    protected array $messages = [];

    /**
     * @var string[]
     */
    protected array $excludedDirectories = [];

    /**
     * @var string[]
     */
    protected array $excludedFileNames = [];

    protected \SplFileInfo $file;

    public function canHandle(\SplFileInfo $file): bool
    {
        if (in_array($file->getFilename(), $this->excludedFileNames, true)) {
            return false;
        }
        foreach ($this->excludedDirectories as $path) {
            if (str_starts_with($this->removeRootPathFromPath($file->getRealPath()), $path)) {
                return false;
            }
        }
        return true;
    }

    public function startProcessing(\SplFileInfo $file): void
    {
        $this->file = $file;
        $this->messages = [];
    }

    public function finishProcessing(): void
    {
        // override in concrete classes, if needed
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    protected function getRelativeFileNameFromRepositoryRoot(): string
    {
        return $this->removeRootPathFromPath($this->file->getRealPath());
    }

    protected function removeRootPathFromPath(string $path): string
    {
        $rootPath = rtrim($this->getRootPath(), '/') . '/';
        return str_starts_with($path, $rootPath)
            ? mb_substr($path, mb_strlen($rootPath))
            : $path;
    }

    protected function getRootPath(): string
    {
        return dirname(__FILE__, 4);
    }

    abstract public function outputResult(SymfonyStyle $io, array $issueCollection): void;

}
