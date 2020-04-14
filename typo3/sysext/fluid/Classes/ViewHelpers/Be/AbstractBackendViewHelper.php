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

namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * The abstract base class for all backend ViewHelpers
 * Note: backend ViewHelpers are still experimental!
 */
abstract class AbstractBackendViewHelper extends AbstractViewHelper
{
    /**
     * Gets instance of template if exists or create a new one.
     * Saves instance in viewHelperVariableContainer
     *
     * @return ModuleTemplate
     */
    public function getModuleTemplate()
    {
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if ($viewHelperVariableContainer->exists(self::class, 'ModuleTemplate')) {
            $moduleTemplate = $viewHelperVariableContainer->get(self::class, 'ModuleTemplate');
        } else {
            $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
            $viewHelperVariableContainer->add(self::class, 'ModuleTemplate', $moduleTemplate);
        }
        return $moduleTemplate;
    }

    /**
     * Gets instance of PageRenderer if exists or create a new one.
     * Saves instance in viewHelperVariableContainer
     *
     * @return PageRenderer
     */
    public function getPageRenderer()
    {
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if ($viewHelperVariableContainer->exists(self::class, 'PageRenderer')) {
            $pageRenderer = $viewHelperVariableContainer->get(self::class, 'PageRenderer');
        } else {
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $viewHelperVariableContainer->add(self::class, 'PageRenderer', $pageRenderer);
        }

        return $pageRenderer;
    }
}
