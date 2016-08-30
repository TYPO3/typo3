<?php
namespace TYPO3\CMS\Backend\ContextMenu\Pagetree;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Context Menu Data Provider for the Page Tree
 */
class ContextMenuDataProvider extends \TYPO3\CMS\Backend\ContextMenu\AbstractContextMenuDataProvider
{
    /**
     * Old Context Menu Options (access mapping)
     *
     * Note: Only option with different namings are mapped!
     *
     * @var array
     */
    protected $legacyContextMenuMapping = [
        'hide' => 'disable',
        'paste' => 'pasteInto,pasteAfter',
        'mount_as_treeroot' => 'mountAsTreeroot'
    ];

    /**
     * Fetches the items that should be disabled from the context menu
     *
     * @return array
     */
    protected function getDisableActions()
    {
        $tsConfig = $this->getBackendUser()->getTSConfig('options.contextMenu.' . $this->getContextMenuType() . '.disableItems');
        $disableItems = [];
        if (trim($tsConfig['value']) !== '') {
            $disableItems = GeneralUtility::trimExplode(',', $tsConfig['value']);
        }
        $tsConfig = $this->getBackendUser()->getTSConfig('options.contextMenu.pageTree.disableItems');
        $oldDisableItems = [];
        if (trim($tsConfig['value']) !== '') {
            $oldDisableItems = GeneralUtility::trimExplode(',', $tsConfig['value']);
        }
        $additionalItems = [];
        foreach ($oldDisableItems as $item) {
            if (!isset($this->legacyContextMenuMapping[$item])) {
                $additionalItems[] = $item;
                continue;
            }
            if (strpos($this->legacyContextMenuMapping[$item], ',')) {
                $actions = GeneralUtility::trimExplode(',', $this->legacyContextMenuMapping[$item]);
                $additionalItems = array_merge($additionalItems, $actions);
            } else {
                $additionalItems[] = $item;
            }
        }
        $disableItems = array_merge($disableItems, $additionalItems);

        // Further manipulation of disableItems array via hook
        // @internal: This is an internal hook for extension impexp only, this hook may change without further notice
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['backend']['contextMenu']['disableItems'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['backend']['contextMenu']['disableItems'])
        ) {
            $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['backend']['contextMenu']['disableItems'];
            foreach ($hooks as $hook) {
                $parameterArray = [
                    'disableItems' => &$disableItems,
                ];
                $null = null;
                GeneralUtility::callUserFunction($hook, $parameterArray, $null);
            }
        }

        return $disableItems;
    }

    /**
     * Returns the actions for the node
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
     * @return \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection
     */
    public function getActionsForNode(\TYPO3\CMS\Backend\Tree\TreeNode $node)
    {
        $this->disableItems = $this->getDisableActions();
        $configuration = $this->getConfiguration();
        $contextMenuActions = [];
        if (is_array($configuration)) {
            $contextMenuActions = $this->getNextContextMenuLevel($configuration, $node);
        }
        return $contextMenuActions;
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
