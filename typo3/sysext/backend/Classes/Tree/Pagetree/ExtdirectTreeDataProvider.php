<?php
namespace TYPO3\CMS\Backend\Tree\Pagetree;

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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Data Provider of the Page Tree
 */
class ExtdirectTreeDataProvider extends \TYPO3\CMS\Backend\Tree\AbstractExtJsTree
{
    /**
     * Data Provider
     *
     * @var \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider
     */
    protected $dataProvider = null;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Sets the data provider
     *
     * @return void
     */
    protected function initDataProvider()
    {
        /** @var $dataProvider \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider */
        $dataProvider = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\DataProvider::class);
        $this->setDataProvider($dataProvider);
    }

    /**
     * Returns the root node of the tree
     *
     * @return array
     */
    public function getRoot()
    {
        $this->initDataProvider();
        $node = $this->dataProvider->getRoot();
        return $node->toArray();
    }

    /**
     * Fetches the next tree level
     *
     * @param int $nodeId
     * @param stdClass $nodeData
     * @return array
     */
    public function getNextTreeLevel($nodeId, $nodeData)
    {
        $this->initDataProvider();
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        if ($nodeId === 'root') {
            $nodeCollection = $this->dataProvider->getTreeMounts();
        } else {
            $nodeCollection = $this->dataProvider->getNodes($node, $node->getMountPoint());
        }
        return $nodeCollection->toArray();
    }

    /**
     * Returns a tree that only contains elements that match the given search string
     *
     * @param int $nodeId
     * @param stdClass $nodeData
     * @param string $searchFilter
     * @return array
     */
    public function getFilteredTree($nodeId, $nodeData, $searchFilter)
    {
        if (strval($searchFilter) === '') {
            return [];
        }
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        $this->initDataProvider();
        if ($nodeId === 'root') {
            $nodeCollection = $this->dataProvider->getTreeMounts($searchFilter);
        } else {
            $nodeCollection = $this->dataProvider->getFilteredNodes($node, $searchFilter, $node->getMountPoint());
        }
        return $nodeCollection->toArray();
    }

    /**
     * Returns the localized list of doktypes to display
     *
     * Note: The list can be filtered by the user typoscript
     * option "options.pageTree.doktypesToShowInNewPageDragArea".
     *
     * @return array
     */
    public function getNodeTypes()
    {
        $doktypeLabelMap = [];
        foreach ($GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] as $doktypeItemConfig) {
            if ($doktypeItemConfig[1] === '--div--') {
                continue;
            }
            $doktypeLabelMap[$doktypeItemConfig[1]] = $doktypeItemConfig[0];
        }
        $doktypes = GeneralUtility::trimExplode(',', $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.doktypesToShowInNewPageDragArea'));
        $output = [];
        $allowedDoktypes = GeneralUtility::trimExplode(',', $GLOBALS['BE_USER']->groupData['pagetypes_select'], true);
        $isAdmin = $GLOBALS['BE_USER']->isAdmin();
        // Early return if backend user may not create any doktype
        if (!$isAdmin && empty($allowedDoktypes)) {
            return $output;
        }
        foreach ($doktypes as $doktype) {
            if (!$isAdmin && !in_array($doktype, $allowedDoktypes)) {
                continue;
            }
            $label = $GLOBALS['LANG']->sL($doktypeLabelMap[$doktype], true);
            $icon = $this->iconFactory->getIcon($GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$doktype], Icon::SIZE_SMALL)->render();
            $output[] = [
                'nodeType' => $doktype,
                'cls' => 'typo3-pagetree-topPanel-button',
                'html' => $icon,
                'title' => $label,
                'tooltip' => $label
            ];
        }
        return $output;
    }

    /**
     * Returns
     *
     * @return array
     */
    public function getIndicators()
    {
        /** @var $indicatorProvider \TYPO3\CMS\Backend\Tree\Pagetree\Indicator */
        $indicatorProvider = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\Indicator::class);
        $indicatorHtmlArr = $indicatorProvider->getAllIndicators();
        $indicator = [
            'html' => implode(' ', $indicatorHtmlArr),
            '_COUNT' => count($indicatorHtmlArr)
        ];
        return $indicator;
    }

    /**
     * Returns the language labels, sprites and configuration options for the pagetree
     *
     * @return array
     */
    public function loadResources()
    {
        $file = 'LLL:EXT:lang/locallang_core.xlf:';
        $indicators = $this->getIndicators();
        $configuration = [
            'LLL' => [
                'copyHint' => $GLOBALS['LANG']->sL($file . 'tree.copyHint', true),
                'fakeNodeHint' => $GLOBALS['LANG']->sL($file . 'mess.please_wait', true),
                'activeFilterMode' => $GLOBALS['LANG']->sL($file . 'tree.activeFilterMode', true),
                'dropToRemove' => $GLOBALS['LANG']->sL($file . 'tree.dropToRemove', true),
                'buttonRefresh' => $GLOBALS['LANG']->sL($file . 'labels.refresh', true),
                'buttonNewNode' => $GLOBALS['LANG']->sL($file . 'tree.buttonNewNode', true),
                'buttonFilter' => $GLOBALS['LANG']->sL($file . 'tree.buttonFilter', true),
                'dropZoneElementRemoved' => $GLOBALS['LANG']->sL($file . 'tree.dropZoneElementRemoved', true),
                'dropZoneElementRestored' => $GLOBALS['LANG']->sL($file . 'tree.dropZoneElementRestored', true),
                'searchTermInfo' => $GLOBALS['LANG']->sL($file . 'tree.searchTermInfo', true),
                'temporaryMountPointIndicatorInfo' => $GLOBALS['LANG']->sl($file . 'labels.temporaryDBmount', true),
                'deleteDialogTitle' => $GLOBALS['LANG']->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:deleteItem', true),
                'deleteDialogMessage' => $GLOBALS['LANG']->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:deleteWarning', true),
                'recursiveDeleteDialogMessage' => $GLOBALS['LANG']->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:recursiveDeleteWarning', true)
            ],
            'Configuration' => [
                'hideFilter' => $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.hideFilter'),
                'displayDeleteConfirmation' => $GLOBALS['BE_USER']->jsConfirmation(JsConfirmation::DELETE),
                'canDeleteRecursivly' => $GLOBALS['BE_USER']->uc['recursiveDelete'] == true,
                'disableIconLinkToContextmenu' => $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.disableIconLinkToContextmenu'),
                'indicator' => $indicators['html'],
                'temporaryMountPoint' => Commands::getMountPointPath()
            ],
            'Icons' => [
                'InputClear' => $this->iconFactory->getIcon('actions-input-clear', Icon::SIZE_SMALL)->render(),
                'Close' => $this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL)->render('inline'),
                'TrashCan' => $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render('inline'),
                'TrashCanRestore' => $this->iconFactory->getIcon('actions-edit-restore', Icon::SIZE_SMALL)->render('inline'),
                'Info' => $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render('inline'),
                'NewNode' => $this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL)->render(),
                'Filter' => $this->iconFactory->getIcon('actions-filter', Icon::SIZE_SMALL)->render(),
                'Refresh' => $this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)->render()
            ]
        ];
        return $configuration;
    }
}
