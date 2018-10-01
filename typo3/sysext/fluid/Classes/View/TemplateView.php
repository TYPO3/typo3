<?php
namespace TYPO3\CMS\Fluid\View;

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

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;

/**
 * The main template view. Should be used as view if you want Fluid Templating
 */
class TemplateView extends AbstractTemplateView
{
    /**
     * Sets the path and name of the layout file. Overrides the dynamic resolving of the layout file.
     *
     * @param string $layoutPathAndFilename Path and filename of the layout file
     */
    public function setLayoutPathAndFilename($layoutPathAndFilename)
    {
        $this->baseRenderingContext->getTemplatePaths()->setLayoutPathAndFilename($layoutPathAndFilename);
    }

    /**
     * Checks whether a template can be resolved for the current request context.
     *
     * @param ControllerContext $controllerContext Controller context which is available inside the view
     * @return bool
     */
    public function canRender(ControllerContext $controllerContext)
    {
        try {
            $request = $controllerContext->getRequest();
            $this->setControllerContext($controllerContext);
            $this->baseRenderingContext->getTemplatePaths()->setFormat($request->getFormat());
            $this->baseRenderingContext->getTemplatePaths()->getTemplateSource($request->getControllerName(), $request->getControllerActionName());
            return true;
        } catch (InvalidTemplateResourceException $e) {
            return false;
        }
    }
}
