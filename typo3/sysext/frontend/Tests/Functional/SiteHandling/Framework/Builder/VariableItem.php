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

class VariableItem
{
    use VariablesTrait;

    /**
     * @var VariableValue
     */
    private $variableKey;

    /**
     * @var array
     */
    private $value;

    /**
     * @var string[]
     */
    private $requiredDefinedVariableNames;

    public static function create(string $key, $value): self
    {
        return new static(
            VariableValue::create(sprintf('[[%s]]', $key)),
            $value
        );
    }

    private function __construct(VariableValue $variableKey, $value)
    {
        $this->variableKey = $variableKey;
        $this->value = $value;
    }

    public function isArray(): bool
    {
        return is_array($this->value);
    }

    public function apply(Variables $variables): array
    {
        if (!$this->hasAllRequiredDefinedVariableNames($variables)) {
            return [];
        }
        return [$this->key($variables) => $this->value];
    }

    public function key(Variables $variables): ?string
    {
        if (!$this->hasAllRequiredDefinedVariableNames($variables)) {
            return null;
        }
        return $this->variableKey->apply($variables);
    }
}
