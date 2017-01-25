<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

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
