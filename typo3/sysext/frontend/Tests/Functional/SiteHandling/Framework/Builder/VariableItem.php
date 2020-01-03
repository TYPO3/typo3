<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder;

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

class VariableItem
{
    /**
     * @var VariableValue
     */
    private $variableKey;

    /**
     * @var array
     */
    private $values;

    public static function create(string $key, array $values): self
    {
        return new static(
            VariableValue::create(sprintf('[[%s]]', $key)),
            $values
        );
    }

    private function __construct(VariableValue $variableKey, array $values)
    {
        $this->variableKey = $variableKey;
        $this->values = $values;
    }

    public function apply(Variables $variables): array
    {
        return [$this->key($variables) => $this->values];
    }

    public function key(Variables $variables): string
    {
        return $this->variableKey->apply($variables);
    }
}
