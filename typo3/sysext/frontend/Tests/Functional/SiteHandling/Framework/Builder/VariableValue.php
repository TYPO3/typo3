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
    private string $value;

    /**
     * @var string[]
     */
    private array $variableNames;

    /**
     * @var Variables|null
     */
    private ?Variables $defaultVariables;

    /**
     * @var string[]
     */
    private array $requiredDefinedVariableNames;

    public static function create(string $value, Variables $defaultVariables = null): self
    {
        return new static($value, $defaultVariables);
    }

    public static function createUrlEncodedParams(
        string $value,
        Variables $defaultVariables = null,
        string $prefix = '&'
    ): self {
        $value = self::urlEncodeParams($value, $prefix);
        return self::create($value, $defaultVariables);
    }

    private function __construct(string $value, Variables $defaultVariables = null)
    {
        $variableNames = self::extractVariableNames($value);
        if ($variableNames === []) {
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

    public function __toString(): string
    {
        return $this->value;
    }

    public function apply(Variables $variables): string
    {
        $variables = $variables->withDefined($this->defaultVariables);
        $variables = $this->resolveNestedVariables($variables);

        $this->assertVariableNames($variables);
        if (!$this->hasAllRequiredDefinedVariableNames($variables)) {
            return '';
        }

        return str_replace(
            array_map([self::class, 'wrap'], $variables->keys()),
            $variables->values(),
            $this->value
        );
    }

    private function resolveNestedVariables(Variables $variables): Variables
    {
        $target = clone $variables;
        foreach ($variables as $key => $value) {
            if ($value instanceof self) {
                $otherVariables = clone $variables;
                unset($otherVariables[$key]);
                $target[$key] = $value->apply($otherVariables);
            }
        }
        return $target;
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

    private static function extractVariableNames(string $value): array
    {
        if (!preg_match_all('#\[\[(?P<variableName>[^\[\]]+)\]\]#', $value, $matches)) {
            return [];
        }
        return array_unique($matches['variableName']);
    }

    private static function wrap(string $item): string
    {
        return '[[' . $item . ']]';
    }

    private static function urlEncodeParams(string $value, string $prefix = '&'): string
    {
        $variableNames = self::extractVariableNames($value);
        $variableItems = array_map([self::class, 'wrap'], $variableNames);
        $substitutes = array_map(static fn(): string => bin2hex(random_bytes(20)), $variableNames);
        $value = str_replace($variableItems, $substitutes, $value);
        parse_str($value, $params);
        $value = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        return $prefix . str_replace($substitutes, $variableItems, $value);
    }
}
