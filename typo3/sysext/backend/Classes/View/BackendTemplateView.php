<?php

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

namespace TYPO3\CMS\Backend\View;

use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\View\TemplateView;

/**
 * Decorates the main template view. Should be used as view if you want to use
 * Fluid templates in a backend module in order to have a consistent backend.
 *
 * @deprecated since v11, will be removed with v12.
 */
class BackendTemplateView implements ViewInterface
{
    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var TemplateView
     */
    protected $templateView;

    /**
     * @param ModuleTemplate $moduleTemplate
     */
    public function injectModuleTemplate(ModuleTemplate $moduleTemplate)
    {
        $this->moduleTemplate = $moduleTemplate;
    }

    /**
     * @param TemplateView $templateView
     */
    public function injectTemplateView(TemplateView $templateView)
    {
        $this->templateView = $templateView;
    }

    /**
     * @return ModuleTemplate
     */
    public function getModuleTemplate()
    {
        return $this->moduleTemplate;
    }

    /**
     * Loads the template source and render the template.
     * If "layoutName" is set in a PostParseFacet callback, it will render the file with the given layout.
     *
     * Additionally amends the rendered template with a module template "frame"
     *
     * @param string $actionName If set, the view of the specified action will be rendered instead. Default is the action specified in the Request object
     * @return string Rendered Template
     */
    public function render($actionName = null)
    {
        $actionViewContent = $this->templateView->render($actionName);
        $this->moduleTemplate->setContent($actionViewContent);
        return $this->moduleTemplate->renderContent();
    }

    /**
     * Sets the current controller context
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext Controller context which is available inside the view
     * @deprecated since v11, will be removed with v12.
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->templateView->setControllerContext($controllerContext);
    }

    /**
     * Assign a value to the variable container.
     *
     * @param string $key The key of a view variable to set
     * @param mixed $value The value of the view variable
     * @return \TYPO3\CMS\Fluid\View\AbstractTemplateView the instance of this view to allow chaining
     */
    public function assign($key, $value)
    {
        $this->templateView->assign($key, $value);
        return $this;
    }

    /**
     * Assigns multiple values to the JSON output.
     * However, only the key "value" is accepted.
     *
     * @param array $values Keys and values - only a value with key "value" is considered
     * @return \TYPO3\CMS\Fluid\View\AbstractTemplateView the instance of this view to allow chaining
     */
    public function assignMultiple(array $values)
    {
        $this->templateView->assignMultiple($values);
        return $this;
    }

    /**
     * Checks whether a template can be resolved for the current request context.
     *
     * @return bool
     * @deprecated since TYPO3 v11, will be removed in v12. Legacy method, not part of ViewInterface anymore.
     */
    public function canRender()
    {
        // Deprecation logged by AbstractTemplateView
        return $this->templateView->canRender();
    }

    /**
     * Init view
     */
    public function initializeView()
    {
        if (method_exists($this->templateView, 'initializeView')) {
            $this->templateView->initializeView();
        }
    }

    /**
     * Set the root path(s) to the templates.
     *
     * @param array $templateRootPaths Root path(s) to the templates.
     */
    public function setTemplateRootPaths(array $templateRootPaths)
    {
        $this->templateView->setTemplateRootPaths($templateRootPaths);
    }

    /**
     * Set the root path(s) to the partials.
     *
     * @param array $partialRootPaths Root paths to the partials.
     */
    public function setPartialRootPaths(array $partialRootPaths)
    {
        $this->templateView->setPartialRootPaths($partialRootPaths);
    }

    /**
     * Set the root path(s) to the layouts.
     *
     * @param array $layoutRootPaths Root path to the layouts.
     */
    public function setLayoutRootPaths(array $layoutRootPaths)
    {
        $this->templateView->setLayoutRootPaths($layoutRootPaths);
    }
}
