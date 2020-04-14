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

use TYPO3\CMS\Core\Utility\PermutationUtility;

class Permutation
{
    /**
     * @var TestSet[]
     */
    private $targets;

    /**
     * @var Applicable[][]
     */
    private $applicableSets;

    /**
     * @var Variables
     */
    private $variables;

    /**
     * @var TestSet[]
     */
    private $results = [];

    public static function create(Variables $variables): self
    {
        return new static($variables);
    }

    private function __construct(Variables $variables)
    {
        $this->variables = $variables;
    }

    public function permute(): self
    {
        $this->results = [];
        foreach ($this->targets as $target) {
            if (!$target instanceof TestSet) {
                throw new \LogicException('Expected DataSet', 1578045577);
            }
            $target = $target->withMergedVariables($this->variables);
            foreach (PermutationUtility::meltArrayItems($this->applicableSets) as $applicables) {
                try {
                    $this->results[] = $this->applyApplicables($target, ...$applicables);
                } catch (SkipException $exception) {
                    continue;
                }
            }
        }
        return $this;
    }

    private function applyApplicables(TestSet $target, Applicable ...$applicables): TestSet
    {
        foreach ($applicables as $index => $applicable) {
            $target = $this->withVariableContext($target, $applicable, ...$applicables);
        }
        return $target->withMergedApplicables(...$applicables);
    }

    private function withVariableContext(TestSet $target, Applicable $candidate, Applicable ...$applicables): TestSet
    {
        if ($candidate instanceof ApplicableConjunction) {
            foreach ($candidate->filter(VariablesContext::class) as $variableContext) {
                // in case SkipException is thrown, skip the whole(!) conjunction
                // (that's why this is not caught here explicitly)
                $target = $this->withVariableContext($target, $variableContext, ...$applicables);
            }
        } elseif ($candidate instanceof VariablesContext) {
            // apply variables for matching VariableContext
            $targetApplicables = $this->includeTargetApplicables($target, ...$applicables);
            if ($candidate->matchesRequiredApplicables(...$targetApplicables)) {
                return $target->withMergedVariables($candidate->getVariables());
            }
            // otherwise don't apply variables & skip this TestSet
            throw new SkipException('skip', 1578162207);
        }
        return $target;
    }

    /**
     * @return TestSet[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function getTargetsForDataProvider(): array
    {
        $keys = array_map(
            function (TestSet $testSet) {
                return $testSet->describe();
            },
            $this->results
        );
        $values = array_map(
            function (TestSet $testSet) {
                return [$testSet];
            },
            $this->results
        );
        return array_combine($keys, $values);
    }

    public function withTargets(TestSet ...$targets): self
    {
        $target = clone $this;
        $target->targets = $targets;
        return $target;
    }

    public function withApplicableSet(Applicable ...$applicables): self
    {
        $target = clone $this;
        $target->applicableSets[] = $applicables;
        return $target;
    }

    public function withApplicableItems(array $applicableItems, Applicable ...$applicables): self
    {
        $applicableItems = array_values($applicableItems);
        $applicableItems = array_merge($applicableItems, $applicables);
        return $this->withApplicableSet(...$applicableItems);
    }

    /**
     * @param TestSet $target
     * @param Applicable ...$applicables
     * @return Applicable[]
     */
    private function includeTargetApplicables(TestSet $target, Applicable ...$applicables): array
    {
        if (empty($target->getApplicables())) {
            return $applicables;
        }
        $targetApplicables = $applicables;
        foreach ($target->getApplicables() as $targetApplicable) {
            if (!in_array($targetApplicable, $targetApplicables, true)) {
                $targetApplicables[] = $targetApplicable;
            }
        }
        return $targetApplicables;
    }
}
