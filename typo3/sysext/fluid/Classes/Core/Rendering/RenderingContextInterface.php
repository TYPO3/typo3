<?php
namespace TYPO3\CMS\Fluid\Core\Rendering;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

interface RenderingContextInterface
{
    /**
     * Get the template variable container
     *
     * @return \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer The Template Variable Container
     */
    public function getTemplateVariableContainer();

    /**
     * Get the controller context which will be passed to the ViewHelper
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext The controller context to set
     */
    public function getControllerContext();

    /**
     * Get the ViewHelperVariableContainer
     *
     * @return \TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer
     */
    public function getViewHelperVariableContainer();
}
