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
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;

/**
 * This is used in FE to "gather" condition nodes as a flat tree (root + condition nodes).
 * The FE uses this optimized tree to quickly determine condition verdicts without loading
 * the full tree.
 *
 * @internal: Internal tree structure.
 */
final class IncludeTreeConditionIncludeListAccumulatorVisitor implements IncludeTreeVisitorInterface
{
    private RootInclude $rootInclude;

    public function __construct()
    {
        $this->rootInclude = new RootInclude();
    }

    public function getConditionIncludes(): RootInclude
    {
        return $this->rootInclude;
    }

    public function visitBeforeChildren(IncludeInterface $include, int $currentDepth): void
    {
        if (!$include instanceof IncludeConditionInterface) {
            return;
        }
        /** @var IncludeConditionInterface&IncludeInterface $newConditionInclude */
        $newConditionInclude = (new ($include::class));
        $newConditionInclude->setConditionToken($include->getConditionToken());
        $this->rootInclude->addChild($newConditionInclude);
    }

    public function visit(IncludeInterface $include, int $currentDepth): void
    {
        // Noop, just implement interface.
    }
}
