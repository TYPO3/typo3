<?php
namespace TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\Expression;

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

use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\AbstractExpressionNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\ExpressionNodeInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class LegacyNamespaceExpressionNode
 */
class LegacyNamespaceExpressionNode extends AbstractExpressionNode implements ExpressionNodeInterface
{
    /**
     * Pattern which detects ternary conditions written in shorthand
     * syntax, e.g. {checkvar ? thenvar : elsevar}.
     */
    public static $detectionExpression = '/{namespace\\s*([a-z0-9]+)\\s*=\\s*([a-z0-9_\\\\]+)\\s*}/i';

    /**
     * @param RenderingContextInterface $renderingContext
     * @param string $expression
     * @param array $matches
     * @return mixed
     */
    public static function evaluateExpression(RenderingContextInterface $renderingContext, $expression, array $matches)
    {
        $renderingContext->getViewHelperResolver()->addNamespace($matches[1], $matches[2]);
    }
}
