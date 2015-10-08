<?php
namespace TYPO3\CMS\Install\View;

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

/**
 * A standalone fluid view with reduced functionality for ext:install
 *
 * In contrast to the parent class, this StandaloneView does not
 * bootstrap a frontend, so some view helper like f:link will not work.
 */
class StandaloneView extends \TYPO3\CMS\Fluid\View\StandaloneView
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->templateParser = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class);
        $this->setRenderingContext($this->objectManager->get(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext::class));
        $request = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Request::class);
        $request->setRequestURI(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        $request->setBaseURI(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
        $uriBuilder = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class);
        $uriBuilder->setRequest($request);
        $controllerContext = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class);
        $controllerContext->setRequest($request);
        $controllerContext->setUriBuilder($uriBuilder);
        $this->setControllerContext($controllerContext);
        $this->templateCompiler = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler::class);
        $this->templateCompiler->setTemplateCache(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('fluid_template'));
    }
}
