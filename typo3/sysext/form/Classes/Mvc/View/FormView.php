<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Mvc\View;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Renderer\RendererInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * A fluid TemplateView implementation which used to render *Renderables*.
 *
 * The FormView is especially capable of rendering nested renderables
 * as well, i.e a form with a page, with all FormElements.
 *
 * Options
 * =======
 *
 * The FormView uses some rendering options which are of particular
 * importance, as they determine how the form field is resolved to a path
 * in the file system.
 *
 * All rendering options are retrieved from the renderable which shall be rendered,
 * using the {@link \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface::getRenderingOptions()}
 * method.
 *
 * templateRootPaths
 * -----------------
 *
 * Used to define several paths for templates, which will be tried in reversed
 * order (the paths are searched from bottom to top). The first folder where
 * the desired layout is found, is used. If the array keys are numeric,
 * they are first sorted and then tried in reversed order.
 *
 * layoutRootPaths
 * ---------------
 *
 * Used to define several paths for layouts, which will be tried in reversed
 * order (the paths are searched from bottom to top). The first folder where
 * the desired layout is found, is used. If the array keys are numeric,
 * they are first sorted and then tried in reversed order.
 *
 * partialRootPaths
 * ----------------
 *
 * Used to define several paths for partials, which will be tried in reversed
 * order. The first folder where the desired partial is found, is used.
 * The keys of the array define the order.
 *
 * renderableNameInTemplate
 * ------------------------
 *
 * This is a mostly-internal setting which controls the name under which the current
 * renderable is made available inside the template. For example, it controls that
 * inside the template of a "Page", the Page object is available using the variable
 * *page*.
 *
 * Rendering Child Renderables
 * ===========================
 *
 * If a renderable wants to render child renderables, inside its template,
 * it can do that using the <code><formvh:renderRenderable></code> ViewHelper.
 *
 * A template example from Page shall demonstrate this:
 *
 * <pre>
 * {namespace formvh=TYPO3\CMS\Form\ViewHelpers}
 * <f:for each="{page.elements}" as="element">
 *   <formvh:renderRenderable renderable="{element}" />
 * </f:for>
 * </pre>
 *
 * Rendering PHP Based Child Renderables
 * =====================================
 *
 * If a child renderable has a *rendererClassName* set (i.e. {@link \TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface::getRendererClassName()}
 * returns a non-NULL string), this renderer is automatically instanciated
 * and the rendering for this element is delegated to this Renderer.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 * @internal
 */
class FormView extends AbstractTemplateView
{

    /**
     * @var \TYPO3\CMS\Form\Domain\Runtime\FormRuntime
     */
    protected $formRuntime;

    /**
     * @param FormRuntime $formRuntime
     * @return void
     * @internal
     */
    public function setFormRuntime(FormRuntime $formRuntime)
    {
        $this->formRuntime = $formRuntime;
    }

    /**
     * @return FormRuntime
     * @internal
     */
    public function getFormRuntime(): FormRuntime
    {
        return $this->formRuntime;
    }

    /**
     * Render the $renderable and return the content.
     *
     * @param RootRenderable $renderable
     * @return string
     * @throws RenderingException
     * @internal
     */
    public function renderRenderable(RootRenderableInterface $renderable): string
    {
        // Invoke the beforeRendering callback on the renderable
        $renderable->beforeRendering($this->formRuntime);

        if (
            $renderable->getRendererClassName() !== null
            && $renderable->getRendererClassName() !== $this->formRuntime->getRendererClassName()
        ) {
            // If a child renderable has a *rendererClassName* set
            // then render it with this foreign renderer.
            $rendererClassName = $renderable->getRendererClassName();
            $renderer = GeneralUtility::makeInstance(ObjectManager::class)->get($rendererClassName);
            if (!($renderer instanceof RendererInterface)) {
                throw new RenderingException(
                    sprintf('The renderer class "%s" for "%s" does not implement RendererInterface.', $rendererClassName, $renderable->getType()),
                    1480286138
                );
            }
            $renderer->setControllerContext($this->baseRenderingContext->getControllerContext());
            $renderer->setFormRuntime($this->formRuntime);
            return $renderer->render($renderable);
        }

        $renderingOptions = $renderable->getRenderingOptions();

        if (!isset($renderingOptions['templateRootPaths'])) {
            throw new RenderingException(
                sprintf('The option templateRootPaths must be set for renderable "%s"', $renderable->getType()),
                1480293084
            );
        }
        if (!isset($renderingOptions['layoutRootPaths'])) {
            throw new RenderingException(
                sprintf('The option layoutRootPaths must be set for renderable "%s"', $renderable->getType()),
                1480293085
            );
        }
        if (!isset($renderingOptions['partialRootPaths'])) {
            throw new RenderingException(
                sprintf('The option partialRootPaths must be set for renderable "%s"', $renderable->getType()),
                1480293086
            );
        }
        if (!isset($renderingOptions['renderableNameInTemplate'])) {
            throw new RenderingException(
                sprintf('The option renderableNameInTemplate must be set for renderable "%s"', $renderable->getType()),
                1480293087
            );
        }

        $renderingContext = $this->getCurrentRenderingContext();
        // Configure the fluid TemplateView with the rendering options
        // from the renderable
        $renderingContext->getTemplatePaths()->setTemplateRootPaths($renderingOptions['templateRootPaths']);
        $renderingContext->getTemplatePaths()->setLayoutRootPaths($renderingOptions['layoutRootPaths']);
        $renderingContext->getTemplatePaths()->setPartialRootPaths($renderingOptions['partialRootPaths']);

        // Add the renderable object to the template variables and use the
        // configured variable name
        $renderingContext->getVariableProvider()->add($renderingOptions['renderableNameInTemplate'], $renderable);

        // Render the renderable.
        if (isset($renderingOptions['templatePathAndFilename'])) {
            $renderingContext->getTemplatePaths()->setTemplatePathAndFilename($renderingOptions['templatePathAndFilename']);
            $output = $this->render();
        } else {
            // Use the *type* of the renderable as template name
            $output = $this->render($renderable->getType());
        }

        return $this->renderPreviewMode($output, $renderable);
    }

    /**
     * Wrap every renderable with a span with a identifier path data attribute.
     *
     * @param string $output
     * @param RootRenderableInterface $renderable
     * @return string
     * @internal
     */
    protected function renderPreviewMode(string $output, RootRenderableInterface $renderable): string
    {
        $renderingOptions = $this->formRuntime->getRenderingOptions();
        $previewMode = isset($renderingOptions['previewMode']) && $renderingOptions['previewMode'] === true;
        if ($previewMode) {
            $path = $renderable->getIdentifier();
            if ($renderable instanceof RenderableInterface) {
                while ($renderable = $renderable->getParentRenderable()) {
                    $path = $renderable->getIdentifier() . '/' . $path;
                }
            }
            $output = sprintf('<span data-element-identifier-path="%s">%s</span>', $path, $output);
        }
        return $output;
    }
}
