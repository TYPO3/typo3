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
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Used in fluid tree rendering in Object Browser to open / collapse nodes.
 *
 * @internal This is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
final class AstCurrentObjectPathAsJsonViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('currentObjectPath', 'object', 'Current AST object path', true);
        $this->registerArgument('key', 'string', 'Json key', true);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $currentObjectPath = $arguments['currentObjectPath'];
        if (!$currentObjectPath instanceof CurrentObjectPath) {
            throw new \RuntimeException(
                'currentObjectPath must be an instance of CurrentObjectPath',
                1654267174
            );
        }
        $jsonKey = $arguments['key'];
        return json_encode([$jsonKey => $currentObjectPath->getPathAsString()]);
    }
}
