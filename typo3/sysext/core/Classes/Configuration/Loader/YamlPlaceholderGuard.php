<?php

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

namespace TYPO3\CMS\Core\Configuration\Loader;

use TYPO3\CMS\Core\Configuration\Loader\Exception\YamlPlaceholderException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\String\StringFragmentPattern;
use TYPO3\CMS\Core\Utility\String\StringFragmentSplitter;

/**
 * A guard for protecting YAML placeholders - keeps existing, but escalates on adding new placeholders
 */
class YamlPlaceholderGuard
{
    protected StringFragmentSplitter $fragmentSplitter;

    public function __construct(protected array $existingConfiguration)
    {
        $fragmentPattern = GeneralUtility::makeInstance(
            StringFragmentPattern::class,
            StringFragmentSplitter::TYPE_EXPRESSION,
            YamlFileLoader::PATTERN_PARTS
        );
        $this->fragmentSplitter = GeneralUtility::makeInstance(
            StringFragmentSplitter::class,
            $fragmentPattern
        );
    }

    /**
     * Modifies existing configuration.
     */
    public function process(array $modified): array
    {
        return $this->protectPlaceholders($this->existingConfiguration, $modified);
    }

    /**
     * Detects placeholders that have been introduced and handles* them.
     * (*) currently throws an exception, but could be purged or escaped as well
     *
     * @param array<string, mixed> $current
     * @param array<string, mixed> $modified
     * @param list<string> $steps configuration keys traversed so far
     * @return array<string, mixed> sanitized configuration (currently not used, exception thrown before)
     * @throws YamlPlaceholderException
     */
    protected function protectPlaceholders(array $current, array $modified, array $steps = []): array
    {
        foreach ($modified as $key => $value) {
            $currentSteps = array_merge($steps, [$key]);
            if (is_array($value)) {
                $modified[$key] = $this->protectPlaceholders(
                    $current[$key] ?? [],
                    $value,
                    $currentSteps
                );
            } elseif (is_string($value)) {
                $splitFlags = StringFragmentSplitter::FLAG_UNMATCHED_AS_NULL;
                $newFragments = $this->fragmentSplitter->split($value, $splitFlags);
                if (is_string($current[$key] ?? null)) {
                    $currentFragments = $this->fragmentSplitter->split($current[$key] ?? '', $splitFlags);
                } else {
                    $currentFragments = null;
                }
                // in case there are new fragments (at least one matching the pattern)
                if ($newFragments !== null) {
                    // compares differences in `expression` fragments only
                    $differences = $currentFragments === null
                        ? $newFragments->withOnlyType(StringFragmentSplitter::TYPE_EXPRESSION)
                        : $newFragments->withOnlyType(StringFragmentSplitter::TYPE_EXPRESSION)
                            ->diff($currentFragments->withOnlyType(StringFragmentSplitter::TYPE_EXPRESSION));
                    if (count($differences) > 0) {
                        throw new YamlPlaceholderException(
                            sprintf(
                                'Introducing placeholder%s %s for %s is not allowed',
                                count($differences) !== 1 ? 's' : '',
                                implode(', ', $differences->getFragments()),
                                implode('.', $currentSteps)
                            ),
                            1651690534
                        );
                    }
                }
            }
        }
        return $modified;
    }
}
