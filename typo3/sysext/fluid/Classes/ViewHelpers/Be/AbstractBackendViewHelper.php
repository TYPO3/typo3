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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * The abstract base class for all backend view helpers
 * Note: backend view helpers are still experimental!
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
        if ($this->viewHelperVariableContainer->exists(self::class, 'DocumentTemplate')) {
            $doc = $this->viewHelperVariableContainer->get(self::class, 'DocumentTemplate');
        } else {
            /** @var $doc DocumentTemplate */
            $doc = GeneralUtility::makeInstance(DocumentTemplate::class);
            $this->viewHelperVariableContainer->add(self::class, 'DocumentTemplate', $doc);
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
        if ($this->viewHelperVariableContainer->exists(self::class, 'PageRenderer')) {
            $pageRenderer = $this->viewHelperVariableContainer->get(self::class, 'PageRenderer');
        } else {
            /** @var $doc DocumentTemplate */
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $this->viewHelperVariableContainer->add(self::class, 'PageRenderer', $pageRenderer);
        }

        return $pageRenderer;
    }
}
