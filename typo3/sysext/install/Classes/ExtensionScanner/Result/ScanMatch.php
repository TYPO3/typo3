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
 * A single place in a scanned file that matched one of the extension scanner rules.
 *
 * The match carries the name of the matcher that produced it: this is what makes the
 * indicator interpretable, since a weak match from a name-only matcher means something
 * different from a weak match from an argument count matcher.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
final readonly class ScanMatch
{
    /**
     * @param string $matcher Opaque identifier of the matcher that produced this match, stable
     *                        within a minor version, e.g. "MethodCallMatcher". Meant for
     *                        display and grouping, not for mapping back to a class.
     * @param list<string> $restFiles File names of the changelog entries, unresolved,
     *                                e.g. "Deprecation-80491-BackendControllerInclusionHooks.rst"
     */
    public function __construct(
        private int $line,
        private Indicator $indicator,
        private string $matcher,
        private string $message,
        private array $restFiles,
    ) {}

    public function getLine(): int
    {
        return $this->line;
    }

    public function getIndicator(): Indicator
    {
        return $this->indicator;
    }

    public function getMatcher(): string
    {
        return $this->matcher;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return list<string>
     */
    public function getRestFiles(): array
    {
        return $this->restFiles;
    }
}
