<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\ViewHelpers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It originated from the Neos.Form package (www.neos.io)
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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Render a renderable.
 *
 * Set the renderable into the \TYPO3\CMS\Form\Mvc\View\FormView
 * and return the rendered content.
 *
 * Scope: frontend
 * @api
 */
class RenderRenderableViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize the arguments.
     *
     * @return void
     * @internal
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('renderable', RootRenderableInterface::class, 'A RenderableInterface instance', true);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @public
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        /** @var FormRuntime $formRuntime */
        $formRuntime =  $renderingContext
            ->getViewHelperVariableContainer()
            ->get(self::class, 'formRuntime');

        // Invoke the beforeRendering callback on the renderable
        $arguments['renderable']->beforeRendering($formRuntime);

        $renderable = $arguments['renderable'];
        $content = $renderChildrenClosure();
        if (!empty($content)) {
            $content = static::renderPreviewMode($content, $renderable, $renderingContext, $formRuntime);
        }
        return $content;
    }

    /**
     * Wrap every renderable with a span with a identifier path data attribute.
     *
     * @param string $content
     * @param RootRenderableInterface $renderable
     * @param RenderingContextInterface $renderingContext
     * @param FormRuntime $formRuntime
     * @return string
     * @internal
     */
    public static function renderPreviewMode(
        string $content,
        RootRenderableInterface $renderable,
        RenderingContextInterface $renderingContext,
        FormRuntime $formRuntime
    ): string {
        $renderingOptions = $formRuntime->getRenderingOptions();
        $previewMode = isset($renderingOptions['previewMode']) && $renderingOptions['previewMode'] === true;
        if ($previewMode) {
            $path = $renderable->getIdentifier();
            if ($renderable instanceof RenderableInterface) {
                while ($renderable = $renderable->getParentRenderable()) {
                    $path = $renderable->getIdentifier() . '/' . $path;
                }
            }
            $content = sprintf('<span data-element-identifier-path="%s">%s</span>', $path, $content);
        }
        return $content;
    }
}
