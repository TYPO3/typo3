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

namespace TYPO3\CMS\Install\ExtensionScanner\Result;

/**
 * The outcome of scanning one file.
 *
 * A file that could not be parsed yields a result carrying the parser message and no matches,
 * so that scanning a whole extension is not aborted by a single unparsable file.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
final readonly class ScanResult
{
    /**
     * @param list<ScanMatch> $matches
     */
    public function __construct(
        private string $file,
        private array $matches,
        private ?string $parseError,
        private bool $isFileIgnored,
        private int $effectiveCodeLines,
        private int $ignoredLines,
    ) {
        if ($parseError !== null && $matches !== []) {
            throw new \RuntimeException(
                'A scan result with a parse error can not carry matches.',
                1790540103
            );
        }
    }

    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return list<ScanMatch>
     */
    public function getMatches(): array
    {
        return $this->matches;
    }

    public function getParseError(): ?string
    {
        return $this->parseError;
    }

    public function isFileIgnored(): bool
    {
        return $this->isFileIgnored;
    }

    public function getEffectiveCodeLines(): int
    {
        return $this->effectiveCodeLines;
    }

    public function getIgnoredLines(): int
    {
        return $this->ignoredLines;
    }
}
