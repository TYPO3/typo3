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

class VariablesContext implements Applicable
{
    private $variables;

    /**
     * @var Applicable[]
     */
    private $requiredApplicables;

    public static function create(Variables $variables): self
    {
        return new static($variables);
    }

    private function __construct(Variables $variables)
    {
        $this->variables = $variables;
    }

    /**
     * @return Variables
     */
    public function getVariables(): Variables
    {
        return $this->variables;
    }

    public function matchesRequiredApplicables(Applicable ...$applicables): bool
    {
        if ($this->requiredApplicables === null || $this->requiredApplicables === []) {
            return true;
        }
        $missingApplicables = array_udiff(
            $this->requiredApplicables,
            $applicables,
            [$this, 'compareApplicables']
        );
        return count($missingApplicables) === 0;
    }

    public function withRequiredApplicables(Applicable ...$requiredApplicables): self
    {
        $target = clone $this;
        $target->requiredApplicables = $requiredApplicables;
        return $target;
    }

    public function describe(): string
    {
        $items = [];
        foreach ($this->variables->getArrayCopy() as $key => $value) {
            $items[] = sprintf('$%s=%s', $key, $this->asHumanReadable($value));
        }
        return implode(', ', $items);
    }

    private function asHumanReadable($value)
    {
        if ($value === null) {
            return '<null>';
        }
        if ($value === '') {
            return '<empty>';
        }
        return $value;
    }

    private function compareApplicables(Applicable $a, Applicable $b): int
    {
        if ($a === $b) {
            return 0;
        }
        return 1;
    }
}
