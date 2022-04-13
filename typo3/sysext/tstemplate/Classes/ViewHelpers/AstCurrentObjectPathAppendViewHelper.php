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

namespace TYPO3\CMS\Tstemplate\ViewHelpers;

use TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath\CurrentObjectPath;
use TYPO3\CMS\Core\TypoScript\AST\Node\NodeInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Used in fluid tree rendering in Object Browser to track current level.
 *
 * @internal This is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
final class AstCurrentObjectPathAppendViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('currentObjectPath', 'object', 'Current AST object path to append a node to', true);
        $this->registerArgument('node', 'object', 'NodeInterface to append', true);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): void
    {
        $currentObjectPath = $arguments['currentObjectPath'];
        $nodeToAppend = $arguments['node'];
        if (!$currentObjectPath instanceof CurrentObjectPath
            || !$nodeToAppend instanceof NodeInterface
        ) {
            throw new \RuntimeException(
                'currentObjectPath must be an instance of CurrentObjectPath and node must be an instance of NodeInterface',
                1654249048
            );
        }
        $currentObjectPath->append($nodeToAppend);
    }
}
