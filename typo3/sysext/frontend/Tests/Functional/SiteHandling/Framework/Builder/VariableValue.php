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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder;

class VariableValue
{
    use VariablesTrait;

    /**
     * @var string
     */
    private $value;

    /**
     * @var string[]
     */
    private $variableNames;

    /**
     * @var Variables
     */
    private $defaultVariables;

    /**
     * @var string[]
     */
    private $requiredDefinedVariableNames;

    public static function create(string $value, Variables $defaultVariables = null): self
    {
        return new static($value, $defaultVariables);
    }

    private function __construct(string $value, Variables $defaultVariables = null)
    {
        $variableNames = $this->extractVariableNames($value);
        if (count($variableNames) === 0) {
            throw new \LogicException(
                sprintf(
                    'Payload did not contain any variables "%s"',
                    $value
                ),
                1577789315
            );
        }

        $this->value = $value;
        $this->variableNames = $variableNames;
        $this->defaultVariables = $defaultVariables;
    }

    public function apply(Variables $variables): string
    {
        $variables = $variables->withDefined($this->defaultVariables);

        $this->assertVariableNames($variables);
        if (!$this->hasAllRequiredDefinedVariableNames($variables)) {
            return '';
        }

        return str_replace(
            array_map([$this, 'wrap'], $variables->keys()),
            $variables->values(),
            $this->value
        );
    }

    private function assertVariableNames(Variables $variables): void
    {
        $missingVariableNames = array_diff($this->variableNames, $variables->keys());
        if (!empty($missingVariableNames)) {
            throw new \LogicException(
                sprintf(
                    'Missing variable names "%s" for "%s"',
                    implode(',', $missingVariableNames),
                    $this->value
                ),
                1577789316
            );
        }
    }

    private function extractVariableNames(string $value): array
    {
        if (!preg_match_all('#\[\[(?P<variableName>[^\[\]]+)\]\]#', $value, $matches)) {
            return [];
        }
        return array_unique($matches['variableName']);
    }

    private function wrap(string $item): string
    {
        return '[[' . $item . ']]';
    }
}
