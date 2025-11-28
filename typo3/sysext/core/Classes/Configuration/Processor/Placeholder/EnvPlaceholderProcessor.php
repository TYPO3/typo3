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

namespace TYPO3\CMS\Core\Configuration\Processor\Placeholder;

use TYPO3\CMS\Core\Configuration\Processor\PlaceholderProcessorList;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Can process a string consisting of one or multiple %env(...)% YAML
 * placeholders, and replace them with their evaluated values.
 * This in contrast to the EnvVariableProcessor, which only evaluates
 * a single ENV value (without the placeholders), and is also utilized
 * for the actual expansion here by means of the PlaceholderProcessorList.
 */
class EnvPlaceholderProcessor
{
    // see also YamlFileLoader
    public const PATTERN_PARTS = '%[^(%]+?\([\'"]?([^(]*?)[\'"]?\)%|%([^%()]*?)%';

    protected array $processorList = [];

    public function __construct(
    ) {
        $processorList = GeneralUtility::makeInstance(
            PlaceholderProcessorList::class,
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['yamlLoader']['placeholderProcessors']
        );
        $this->processorList = $processorList->compile();
    }

    public function canProcess(mixed $placeholder): bool
    {
        // only strings may be candidates for $placeholder substitution
        if (!is_string($placeholder)) {
            return false;
        }

        return str_contains($placeholder, '%env(');
    }

    public function process(string $value): string
    {
        return $this->processPlaceholderLine($value);
    }

    /**
     * The following methods are taken from YamlFileLoader, but adapted
     * for isolated usage and preventing circular dependencies.
     */
    protected function processPlaceholderLine(string $line): string
    {
        $parts = $this->getParts($line);
        foreach ($parts as $partKey => $part) {
            $result = $this->processSinglePlaceholder($partKey, $part);
            // Replace whole content if placeholder is the only thing in this line
            if ($line === $partKey) {
                $line = $result;
            } elseif (is_string($result) || is_numeric($result)) {
                $line = str_replace($partKey, $result, $line);
            } else {
                throw new \UnexpectedValueException(
                    'ENV Placeholder can not be substituted if result is not string or numeric',
                    1770965068
                );
            }
            if ($result !== $partKey && $this->containsPlaceholder($line)) {
                $line = $this->processPlaceholderLine($line);
            }
        }
        return $line;
    }

    protected function processSinglePlaceholder(string $placeholder, string $value): mixed
    {
        foreach ($this->processorList as $processor) {
            if ($processor->canProcess($placeholder, [])) {
                try {
                    $result = $processor->process($value, []);
                } catch (\UnexpectedValueException) {
                    $result = $placeholder;
                }
                break;
            }
        }
        return $result ?? $placeholder;
    }

    // These two methods are used just as in YamlFileLoader and
    // might be moved to utility classes; however for now they
    // are replicated to be able to be adapted.
    protected function getParts(string $placeholders): array
    {
        // find occurrences of placeholders like %some()% and %array.access%.
        // Only find the innermost ones, so we can nest them.
        preg_match_all(
            '/' . self::PATTERN_PARTS . '/',
            $placeholders,
            $parts,
            PREG_UNMATCHED_AS_NULL
        );
        $matches = array_filter(
            array_merge($parts[1], $parts[2])
        );
        return array_combine($parts[0], $matches);
    }

    /**
     * Finds possible placeholders.
     * May find false positives for complexer structures, but they will be sorted later on.
     */
    protected function containsPlaceholder(mixed $value): bool
    {
        return is_string($value) && substr_count($value, '%') >= 2;
    }
}
