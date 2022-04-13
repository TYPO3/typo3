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

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;

/**
 * A visitor that can be attached to IncludeTreeTraverser's.
 *
 * @internal: Internal tree structure.
 */
interface IncludeTreeVisitorInterface
{
    /**
     * Gets called by the traversers *before* children are traversed. Useful for
     * instance for the IncludeTreeConditionMatcherVisitor to evaluate a condition
     * verdict *before* children are traversed (or not).
     */
    public function visitBeforeChildren(IncludeInterface $include, int $currentDepth): void;

    /**
     * Main visit method called for each node.
     */
    public function visit(IncludeInterface $include, int $currentDepth): void;
}
