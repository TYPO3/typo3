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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Render a renderable.
 *
 * Set the renderable into the \TYPO3\CMS\Form\Mvc\View\FormView
 * and return the rendered content.
 *
 * Scope: frontend
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
     * @internal
     */
    public function initializeArguments()
    {
        $this->registerArgument('renderable', RootRenderableInterface::class, 'A RenderableInterface instance', true);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @internal
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {

        /** @var FormRuntime $formRuntime */
        $formRuntime = $renderingContext
            ->getViewHelperVariableContainer()
            ->get(self::class, 'formRuntime');

        $renderable = $arguments['renderable'];

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRendering'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'beforeRendering')) {
                $hookObj->beforeRendering(
                    $formRuntime,
                    $renderable
                );
            }
        }

        $content = '';

        if ($renderable instanceof FormRuntime || $renderable instanceof RenderableInterface && $renderable->isEnabled()) {
            $content = $renderChildrenClosure();
        }

        // Wrap every renderable with a span with an identifier path data attribute if previewMode is active
        if (!empty($content)) {
            $renderingOptions = $formRuntime->getRenderingOptions();
            if (isset($renderingOptions['previewMode']) && $renderingOptions['previewMode'] === true) {
                $path = $renderable->getIdentifier();
                if ($renderable instanceof RenderableInterface) {
                    while ($renderable = $renderable->getParentRenderable()) {
                        $path = $renderable->getIdentifier() . '/' . $path;
                    }
                }
                $content = '<span data-element-identifier-path="' . $path . '">' . $content . '</span>';
            }
        }
        return $content;
    }
}
