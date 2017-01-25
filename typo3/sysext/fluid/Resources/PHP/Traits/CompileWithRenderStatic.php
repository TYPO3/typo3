<?php
namespace TYPO3Fluid\Fluid\Core\ViewHelper\Traits;

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

use TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode;

/**
 * Trait CompilableWithRenderStatic
 *
 * Provides default methods for rendering and compiling
 * any ViewHelper that conforms to the `renderStatic`
 * method pattern.
 */
trait CompileWithRenderStatic
{

    /**
     * @return \Closure
     */
    abstract public function buildRenderChildrenClosure();

    /**
     * Default render method - simply calls renderStatic() with a
     * prepared set of arguments.
     *
     * @return string Rendered string
     * @api
     */
    public function render()
    {
        return static::renderStatic(
            $this->arguments,
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param string $argumentsName
     * @param string $closureName
     * @param string $initializationPhpCode
     * @param AbstractNode $node
     * @param TemplateCompiler $compiler
     * @return string
     */
    public function compile(
        $argumentsName,
        $closureName,
        &$initializationPhpCode,
        AbstractNode $node,
        TemplateCompiler $compiler
    ) {
        return sprintf(
            '%s::renderStatic(%s, %s, $renderingContext)',
            static::class,
            $argumentsName,
            $closureName
        );
    }
}
