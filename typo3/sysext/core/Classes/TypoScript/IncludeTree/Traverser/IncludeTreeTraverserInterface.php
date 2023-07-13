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

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeVisitorInterface;

/**
 * Interface implemented by include tree traversers.
 *
 * Visitors can be attached and are called for each traversed node.
 *
 * @internal: Internal tree structure.
 */
interface IncludeTreeTraverserInterface
{
    /**
     * @param IncludeTreeVisitorInterface[] $visitors
     */
    public function traverse(RootInclude $rootInclude, array $visitors): void;
}
