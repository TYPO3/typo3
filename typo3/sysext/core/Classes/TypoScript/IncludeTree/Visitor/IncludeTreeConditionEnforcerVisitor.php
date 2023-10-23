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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor;

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeConditionInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;

/**
 * Force condition verdicts.
 *
 * This visitor is used in ext:tstemplate TypoScript modules and ext:backend page TSconfig
 * backend modules to toggle on/off selected conditions.
 *
 * @internal This is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
final class IncludeTreeConditionEnforcerVisitor implements IncludeTreeVisitorInterface
{
    /**
     * @var array<int, string>
     */
    private array $enabledConditions;

    public function setEnabledConditions(array $enabledConditions): void
    {
        $this->enabledConditions = $enabledConditions;
    }

    public function visitBeforeChildren(IncludeInterface $include, int $currentDepth): void
    {
        if (!$include instanceof IncludeConditionInterface) {
            return;
        }
        $conditionValue = $include->getConditionToken()->getValue();
        if (in_array($conditionValue, $this->enabledConditions) && !$include->isConditionNegated()
            || !in_array($conditionValue, $this->enabledConditions) && $include->isConditionNegated()
        ) {
            $include->setConditionVerdict(true);
        } else {
            $include->setConditionVerdict(false);
        }
    }

    public function visit(IncludeInterface $include, int $currentDepth): void {}
}
