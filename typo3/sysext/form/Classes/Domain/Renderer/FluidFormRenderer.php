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

namespace TYPO3\CMS\Form\Domain\Renderer;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Event\BeforeRenderableIsRenderedEvent;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;

/**
 * A fluid RendererInterface implementation used to render a *FormDefinition*.
 *
 * This renderer is called from {@link \TYPO3\CMS\Form\Domain\Runtime\FormRuntime::render()}.
 *
 * Options
 * =======
 *
 * The FluidFormRenderer uses some rendering options which are of particular
 * importance, as they determine how the form field is resolved to a path
 * in the file system.
 *
 * All rendering options are retrieved from the FormDefinition,
 * using the {@link \TYPO3\CMS\Form\Domain\Model\FormDefinition::getRenderingOptions()}
 * method.
 *
 * templateRootPaths
 * -----------------
 *
 * Used to define several paths for templates, which will be tried in reversed
 * order (the paths are searched from bottom to top). The first folder where
 * the desired layout is found, is used. If the array keys are numeric,
 * they are first sorted and then tried in reversed order.
 * Within this paths, fluid will search for a file which is named like the
 * renderable *type*.
 * For example:
 *   templateRootPaths.10 = EXT:form/Resources/Private/Frontend/Templates/
 *   $renderable->getType() = Form
 *   Expected template file: EXT:form/Resources/Private/Frontend/Templates/Form.html
 * There is a setting available to set a custom template name. Please read
 * the section 'templateName'.
 *
 * Only the root renderable (FormDefinition) has to be a template file.
 * All child renderables are partials. By default, the root renderable
 * is called 'Form'.
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
 * Within this paths, fluid will search for a file which is named like the
 * renderable *type*.
 * For example:
 *   templateRootPaths.10 = EXT:form/Resources/Private/Frontend/Partials/
 *   $renderable->getType() = Text
 *   Expected template file: EXT:form/Resources/Private/Frontend/Partials/Text.html
 * There is a setting available to set a custom partial name. Please read
 * the section 'templateName'.
 *
 * templateName
 * -----------
 * By default, the renderable type will be taken as the name for the
 * template / partial.
 * For example:
 *   partialRootPaths.10 = EXT:form/Resources/Private/Frontend/Partials/
 *   $renderable->getType() = Text
 *   Expected partial file: EXT:form/Resources/Private/Frontend/Partials/Text.html
 *
 * Set 'templateName' to define a custom name which should be used instead.
 * For example:
 *   templateName = Foo
 *   $renderable->getType() = Text
 *   Expected partial file: EXT:form/Resources/Private/Frontend/Partials/Foo.html
 *
 * Rendering Child Renderables
 * ===========================
 *
 * If a renderable wants to render child renderables, inside its template / partial,
 * it can do that using the <code><formvh:renderRenderable></code> ViewHelper.
 *
 * A template example from Page shall demonstrate this:
 *
 * <pre>
 *   <formvh:renderRenderable renderable="{page}">
 *       <f:for each="{page.elements}" as="element">
 *           <formvh:renderRenderable renderable="{element}">
 *               <f:render partial="{element.templateName}" arguments="{element: element}" />
 *           </formvh:renderRenderable>
 *       </f:for>
 *   </formvh:renderRenderable>
 * </pre>
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 * @internal
 */
#[Autoconfigure(public: true, shared: false)]
class FluidFormRenderer extends AbstractElementRenderer
{
    public function __construct(
        protected readonly ViewFactoryInterface $viewFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Renders the FormDefinition.
     *
     * This method is expected to call the 'beforeRendering' hook
     * on each renderable.
     * This method call the 'beforeRendering' hook initially.
     * Each other hooks will be called from the
     * renderRenderable viewHelper.
     * {@link \TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper::render()}
     *
     * @return string the rendered $formRuntime
     * @internal
     */
    public function render(): string
    {
        $formElementType = $this->formRuntime->getType();
        $renderingOptions = $this->formRuntime->getRenderingOptions();
        if (!isset($renderingOptions['templateRootPaths']) || !is_array($renderingOptions['templateRootPaths'])) {
            throw new RenderingException(sprintf('The option templateRootPaths must be set for renderable "%s"', $formElementType), 1480293084);
        }
        if (!isset($renderingOptions['layoutRootPaths']) || !is_array($renderingOptions['layoutRootPaths'])) {
            throw new RenderingException(sprintf('The option layoutRootPaths must be set for renderable "%s"', $formElementType), 1480293085);
        }
        if (!isset($renderingOptions['partialRootPaths']) || !is_array($renderingOptions['partialRootPaths'])) {
            throw new RenderingException(sprintf('The option partialRootPaths must be set for renderable "%s"', $formElementType), 1480293086);
        }
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: $renderingOptions['templateRootPaths'],
            partialRootPaths: $renderingOptions['partialRootPaths'],
            layoutRootPaths: $renderingOptions['layoutRootPaths'],
            request: $this->getFormRuntime()->getRequest(),
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $view->assign('form', $this->formRuntime);
        if ($view instanceof FluidViewAdapter) {
            // @todo: Find a different solution than setting this state here. This happens in other
            //        ext:form places as well and should vanish to be more non-fluid view friendly.
            $view->getRenderingContext()
                ->getViewHelperVariableContainer()
                ->addOrUpdate(RenderRenderableViewHelper::class, 'formRuntime', $this->formRuntime);
        }
        $this->eventDispatcher->dispatch(new BeforeRenderableIsRenderedEvent($this->formRuntime->getFormDefinition(), $this->formRuntime));
        return $view->render($this->formRuntime->getTemplateName());
    }
}
