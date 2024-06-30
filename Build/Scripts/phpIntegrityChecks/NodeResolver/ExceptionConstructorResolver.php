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

namespace TYPO3\CMS\PhpIntegrityChecks\NodeResolver;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class ExceptionConstructorResolver extends NodeVisitorAbstract
{
    public function enterNode(Node $node): void
    {
        if (!$node instanceof Node\Expr\New_) {
            return;
        }

        if (!$node->class instanceof Node\Name\FullyQualified) {
            return;
        }
        try {
            $name = $node->class->name;
            $reflectionClass = new \ReflectionClass($name);
        } catch (\ReflectionException) {
            return;
        }
        if (!$reflectionClass->isSubclassOf(\Exception::class)) {
            return;
        }
        $constructorParameters = $reflectionClass->getConstructor()->getParameters();
        $node->class->setAttribute('constructor', $constructorParameters);
    }

}
