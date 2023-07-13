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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser;

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeVisitorInterface;

/**
 * Traverse all nodes of a RootInclude. Used mostly in backend "Template" module.
 *
 * @internal: Internal tree structure.
 */
final class IncludeTreeTraverser implements IncludeTreeTraverserInterface
{
    public function traverse(RootInclude $rootInclude, array $visitors): void
    {
        foreach ($visitors as $visitor) {
            if (!$visitor instanceof IncludeTreeVisitorInterface) {
                throw new \RuntimeException(
                    'Visitors must implement IncludeTreeVisitorInterface',
                    1689244841
                );
            }
        }
        $this->traverseRecursive($rootInclude, $visitors, 0);
    }

    private function traverseRecursive(IncludeInterface $include, array $visitors, int $currentDepth): void
    {
        foreach ($visitors as $visitor) {
            $visitor->visitBeforeChildren($include, $currentDepth);
        }
        foreach ($include->getNextChild() as $child) {
            $this->traverseRecursive($child, $visitors, $currentDepth + 1);
            foreach ($visitors as $visitor) {
                $visitor->visit($child, $currentDepth);
            }
        }
    }
}
