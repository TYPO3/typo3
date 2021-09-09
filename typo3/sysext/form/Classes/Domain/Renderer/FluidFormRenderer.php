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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
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
class FluidFormRenderer extends AbstractElementRenderer
{

    /**
     * Renders the FormDefinition.
     *
     * This method is expected to call the 'beforeRendering' hook
     * on each renderable.
     * This method call the 'beforeRendering' hook initially.
     * Each other hooks will be called from the
     * renderRenderable viewHelper.
     * {@link \TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper::renderStatic()}
     *
     * @return string the rendered $formRuntime
     * @internal
     */
    public function render(): string
    {
        $formElementType = $this->formRuntime->getType();
        $renderingOptions = $this->formRuntime->getRenderingOptions();

        $view = GeneralUtility::makeInstance(TemplateView::class);
        // @deprecated since v11, will be removed with v12.
        $view->setControllerContext($this->controllerContext);

        if (!isset($renderingOptions['templateRootPaths'])) {
            throw new RenderingException(
                sprintf('The option templateRootPaths must be set for renderable "%s"', $formElementType),
                1480293084
            );
        }
        if (!isset($renderingOptions['layoutRootPaths'])) {
            throw new RenderingException(
                sprintf('The option layoutRootPaths must be set for renderable "%s"', $formElementType),
                1480293085
            );
        }
        if (!isset($renderingOptions['partialRootPaths'])) {
            throw new RenderingException(
                sprintf('The option partialRootPaths must be set for renderable "%s"', $formElementType),
                1480293086
            );
        }

        $view->assign('form', $this->formRuntime);

        $view->getRenderingContext()
            ->getViewHelperVariableContainer()
            ->addOrUpdate(RenderRenderableViewHelper::class, 'formRuntime', $this->formRuntime);

        // Configure the fluid TemplatePaths with the rendering options
        // from the renderable
        $view->getTemplatePaths()->fillFromConfigurationArray($renderingOptions);

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRendering'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'beforeRendering')) {
                $hookObj->beforeRendering(
                    $this->formRuntime,
                    $this->formRuntime->getFormDefinition()
                );
            }
        }

        return $view->render($this->formRuntime->getTemplateName());
    }
}
