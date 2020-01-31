<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
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
     * @return DocumentTemplate $doc
     */
    public function getDocInstance()
    {
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if ($viewHelperVariableContainer->exists(self::class, 'DocumentTemplate')) {
            $doc = $viewHelperVariableContainer->get(self::class, 'DocumentTemplate');
        } else {
            /** @var DocumentTemplate $doc */
            $doc = GeneralUtility::makeInstance(DocumentTemplate::class);
            $viewHelperVariableContainer->add(self::class, 'DocumentTemplate', $doc);
        }

        return $doc;
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
            /** @var DocumentTemplate $doc */
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $viewHelperVariableContainer->add(self::class, 'PageRenderer', $pageRenderer);
        }

        return $pageRenderer;
    }
}
