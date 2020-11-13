<?php

declare(strict_types = 1);

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

namespace TYPO3\CMS\Install\SystemEnvironment\ServerResponse;

/**
 * Evaluates a Content-Security-Policy HTTP header.
 *
 * @internal should only be used from within TYPO3 Core
 */
class ContentSecurityPolicyDirective
{
    protected const RULE_PATTERN = '#(?:\'(?<instruction>[^\']+)\')|(?<source>[^\s]+)#';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string[]
     */
    protected $instructions = [];

    /**
     * @var string[]
     */
    protected $sources = [];

    public function __construct(string $name, string $rule)
    {
        $this->name = $name;
        if (preg_match_all(self::RULE_PATTERN, $rule, $matches)) {
            foreach (array_keys($matches[0]) as $index) {
                if ($matches['instruction'][$index] !== '') {
                    $this->instructions[] = $matches['instruction'][$index];
                } elseif ($matches['source'][$index] !== '') {
                    $this->sources[] = $matches['source'][$index];
                }
            }
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getInstructions(): array
    {
        return $this->instructions;
    }

    /**
     * @return string[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    public function hasInstructions(string ...$instructions): bool
    {
        return array_intersect($this->instructions, $instructions) !== [];
    }
}
