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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * ViewHelper which return page info icon as known from TYPO3 backend modules.
 *
 * .. note::
 *    This ViewHelper is experimental!
 *
 * Examples
 * ========
 *
 * Default::
 *
 *    <f:be.pageInfo />
 *
 * Page info icon with context menu
 */
class PageInfoViewHelper extends AbstractBackendViewHelper
{

    /**
     * This ViewHelper renders HTML, thus output must not be escaped
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Render javascript in header
     *
     * @return string the rendered page info icon
     * @see \TYPO3\CMS\Backend\Template\DocumentTemplate::getPageInfo() Note: can't call this method as it's protected!
     */
    public function render()
    {
        return static::renderStatic(
            [],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $id = GeneralUtility::_GP('id');
        $pageRecord = BackendUtility::readPageAccess($id, $GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW));
        // Add icon with context menu, etc:
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if ($pageRecord['uid']) {
            // If there IS a real page
            $altText = BackendUtility::getRecordIconAltText($pageRecord, 'pages');
            $theIcon = '<span title="' . $altText . '">' . $iconFactory->getIconForRecord('pages', $pageRecord, Icon::SIZE_SMALL)->render() . '</span>';
            // Make Icon:
            $theIcon = BackendUtility::wrapClickMenuOnIcon($theIcon, 'pages', $pageRecord['uid']);

            // Setting icon with context menu + uid
            $theIcon .= ' <em>[PID: ' . $pageRecord['uid'] . ']</em>';
        } else {
            // On root-level of page tree
            // Make Icon
            $theIcon = '<span title="' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) . '">' . $iconFactory->getIcon('apps-pagetree-page-domain', Icon::SIZE_SMALL)->render() . '</span>';
            if ($GLOBALS['BE_USER']->isAdmin()) {
                $theIcon = BackendUtility::wrapClickMenuOnIcon($theIcon, 'pages');
            }
        }
        return $theIcon;
    }
}
