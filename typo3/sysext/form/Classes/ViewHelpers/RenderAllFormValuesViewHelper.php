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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\ViewHelpers;

use TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Renders the values of a form
 *
 * Scope: frontend
 */
class RenderAllFormValuesViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize the arguments.
     *
     * @internal
     */
    public function initializeArguments()
    {
        $this->registerArgument('renderable', RootRenderableInterface::class, 'A RootRenderableInterface instance', true);
        $this->registerArgument('as', 'string', 'The name within the template', false, 'formValue');
    }

    /**
     * Return array element by key.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string the rendered form values
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $renderable = $arguments['renderable'];

        if ($renderable instanceof CompositeRenderableInterface) {
            $elements = $renderable->getRenderablesRecursively();
        } else {
            $elements = [$renderable];
        }

        $as = $arguments['as'];
        $output = '';

        foreach ($elements as $element) {
            $output .= RenderFormValueViewHelper::renderStatic(
                [
                    'renderable' => $element,
                    'as' => $as,
                ],
                $renderChildrenClosure,
                $renderingContext
            );
        }

        return $output;
    }
}
