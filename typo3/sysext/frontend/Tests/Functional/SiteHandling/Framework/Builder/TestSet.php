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

class TestSet
{
    /**
     * @var VariableValue|null
     */
    private $url;

    /**
     * @var int|null
     */
    private $targetPageId;

    /**
     * @var Applicable[]
     */
    private $applicables;

    /**
     * @var Variables
     */
    private $variables;

    public static function create($parentSet = null): self
    {
        if (!$parentSet instanceof static) {
            return new static();
        }
        return clone $parentSet;
    }

    /**
     * @return VariableValue
     */
    public function getUrl(): ?VariableValue
    {
        return $this->url;
    }

    /**
     * @return int|null
     */
    public function getTargetPageId(): ?int
    {
        return $this->targetPageId;
    }

    /**
     * @param string $type
     * @return Applicable[]
     */
    public function getApplicables(string $type = null): array
    {
        if ($type === null) {
            return $this->applicables;
        }
        return $this->filterApplicables($type);
    }

    public function getSingleApplicable(string $type): ?Applicable
    {
        $applicables = $this->filterApplicables($type);
        if (count($applicables) > 1) {
            throw new \LogicException(
                sprintf('Got %dx %s, expected one', count($applicables), $type),
                1578054920
            );
        }
        return array_values($applicables)[0] ?? null;
    }

    /**
     * @return Variables
     */
    public function getVariables(): ?Variables
    {
        return $this->variables;
    }

    public function withMergedApplicables(Applicable ...$applicables): self
    {
        $target = clone $this;
        foreach ($applicables as $applicable) {
            if (!in_array($applicable, $this->applicables ?? [], true)) {
                $target->applicables[] = $applicable;
            }
        }
        return $target;
    }

    public function withVariables(Variables $variables): self
    {
        $target = clone $this;
        $target->variables = $variables;
        return $target;
    }

    public function withMergedVariables(Variables $variables): self
    {
        $target = clone $this;
        $target->variables = $variables->withDefined($target->variables);
        return $target;
    }

    public function withUrl(VariableValue $url): self
    {
        $target = clone $this;
        $target->url = $url;
        return $target;
    }

    public function withTargetPageId(int $targetPageId): self
    {
        $target = clone $this;
        $target->targetPageId = $targetPageId;
        return $target;
    }

    public function describe(): string
    {
        $descriptions = array_map(
            function (Applicable $applicable) {
                return $applicable->describe();
            },
            $this->applicables
        );
        return 'pid: ' . $this->targetPageId . ' | ' . implode(' | ', $descriptions);
    }

    /**
     * @param string $type
     * @return Applicable[]
     */
    private function filterApplicables(string $type): array
    {
        $applicables = [];
        foreach ($this->applicables as $applicable) {
            if (is_a($applicable, $type)) {
                $applicables[] = $applicable;
            } elseif ($applicable instanceof ApplicableConjunction) {
                $applicables = array_merge($applicables, $applicable->filter($type));
            }
        }
        return $applicables;
    }
}
