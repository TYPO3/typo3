<?php
namespace TYPO3\CMS\Fluid\Core\Widget;

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

use TYPO3\CMS\Fluid\View\TemplatePaths;

/**
 * This is the base class for all widget controllers.
 * It is basically an ActionController and additionally has $this->widgetConfiguration set to the
 * Configuration of the current Widget.
 */
abstract class AbstractWidgetController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var array
     */
    protected $supportedRequestTypes = [\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class];

    /**
     * Configuration for this widget.
     *
     * @var array
     */
    protected $widgetConfiguration;

    /**
     * Handles a request. The result output is returned by altering the given response.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request object
     * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, modified by this handler
     */
    public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response)
    {
        $this->widgetConfiguration = $request->getWidgetContext()->getWidgetConfiguration();
        parent::processRequest($request, $response);
    }

    /**
     * Allows the widget template root path to be overridden via the framework configuration,
     * e.g. plugin.tx_extension.view.widget.<WidgetViewHelperClassName>.templateRootPath
     *
     * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
     */
    protected function setViewConfiguration(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view)
    {
        $extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $widgetViewHelperClassName = $this->request->getWidgetContext()->getWidgetViewHelperClassName();
        $templatePaths = new TemplatePaths($this->controllerContext->getRequest()->getControllerExtensionKey());
        $parentConfiguration = $view->getTemplatePaths()->toArray();
        $rootConfiguration = $templatePaths->toArray();
        $pluginConfiguration = $extbaseFrameworkConfiguration['view']['widget'][$widgetViewHelperClassName] ?? [];
        if (isset($pluginConfiguration['templateRootPath']) && !isset($pluginConfiguration['templateRootPaths'])) {
            $pluginConfiguration['templateRootPaths'][10] = $pluginConfiguration['templateRootPath'];
        }
        $widgetViewConfiguration = array_merge_recursive(
            (array)$rootConfiguration,
            (array)$parentConfiguration,
            (array)$pluginConfiguration
        );
        $view->getTemplatePaths()->fillFromConfigurationArray($widgetViewConfiguration);
    }
}
