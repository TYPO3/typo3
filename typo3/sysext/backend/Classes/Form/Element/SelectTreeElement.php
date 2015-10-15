<?php
namespace TYPO3\CMS\Backend\Form\Element;

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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Tree\TableConfiguration\ExtJsArrayTreeRenderer;
use TYPO3\CMS\Core\Tree\TableConfiguration\TableConfigurationTree;
use TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render data as a tree.
 *
 * Typically rendered for config [type=select, renderMode=tree
 */
class SelectTreeElement extends AbstractFormElement
{
    /**
     * Render tree widget
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $table = $this->data['tableName'];
        $field = $this->data['fieldName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];

        // Field configuration from TCA:
        $config = $parameterArray['fieldConf']['config'];

        $possibleSelectboxItems = $config['items'];

        $selectedNodes = $parameterArray['itemFormElValue'];

        $selectedNodesForApi = array();
        foreach ($selectedNodes as $selectedNode) {
            // @todo: this is ugly - the "old" pipe based value|label syntax is re-created here at the moment
            foreach ($possibleSelectboxItems as $possibleSelectboxItem) {
                if ((string)$possibleSelectboxItem[1] === (string)$selectedNode) {
                    $selectedNodesForApi[] = $selectedNode . '|' . rawurlencode($possibleSelectboxItem[0]);
                }
            }
        }

        $allowedUids = array();
        foreach ($possibleSelectboxItems as $item) {
            if ((int)$item[1] > 0) {
                $allowedUids[] = $item[1];
            }
        }
        $treeDataProvider = TreeDataProviderFactory::getDataProvider($config, $table, $field, $row);
        $treeDataProvider->setSelectedList(implode(',', $selectedNodes));
        $treeDataProvider->setItemWhiteList($allowedUids);
        $treeDataProvider->initializeTreeData();
        $treeRenderer = GeneralUtility::makeInstance(ExtJsArrayTreeRenderer::class);
        $tree = GeneralUtility::makeInstance(TableConfigurationTree::class);
        $tree->setDataProvider($treeDataProvider);
        $tree->setNodeRenderer($treeRenderer);
        $treeData = $tree->render();
        $itemArray = array();

        /**
         * @todo: Small bug here: In the past, this was the "not processed list" of default items, but now it is
         * @todo: a full list of elements. This needs to be fixed later, so "additional" default items are shown again.
        if (is_array($config['items'])) {
            foreach ($config['items'] as $additionalItem) {
                if ($additionalItem[1] !== '--div--') {
                    $item = new \stdClass();
                    $item->uid = $additionalItem[1];
                    $item->text = $this->getLanguageService()->sL($additionalItem[0]);
                    $item->selectable = TRUE;
                    $item->leaf = TRUE;
                    $item->checked = in_array($additionalItem[1], $selectedNodes);
                    if (file_exists(PATH_typo3 . $additionalItem[3])) {
                        $item->icon = $additionalItem[3];
                    } elseif (trim($additionalItem[3]) !== '') {
                        $item->iconCls = IconUtility::getSpriteIconClasses($additionalItem[3]);
                    }
                    $itemArray[] = $item;
                }
            }
        }
        */

        $itemArray[] = $treeData;
        $id = md5($parameterArray['itemFormElName']);
        if (isset($config['size']) && (int)$config['size'] > 0) {
            $height = (int)$config['size'] * 20;
        } else {
            $height = 280;
        }
        $autoSizeMax = null;
        if (isset($config['autoSizeMax']) && (int)$config['autoSizeMax'] > 0) {
            $autoSizeMax = (int)$config['autoSizeMax'] * 20;
        }
        $header = false;
        $expanded = false;
        $width = 280;
        $appearance = $config['treeConfig']['appearance'];
        if (is_array($appearance)) {
            $header = (bool)$appearance['showHeader'];
            $expanded = (bool)$appearance['expandAll'];
            if (isset($appearance['width'])) {
                $width = (int)$appearance['width'];
            }
        }
        $onChange = '';
        if ($parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged']) {
            $onChange = $parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'];
        }
        // Create a JavaScript code line which will ask the user to save/update the form due to changing the element.
        // This is used for eg. "type" fields and others configured with "requestUpdate"
        if (
            !empty($GLOBALS['TCA'][$table]['ctrl']['type'])
            && $field === $GLOBALS['TCA'][$table]['ctrl']['type']
            || !empty($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'])
            && GeneralUtility::inList(str_replace(' ', '', $GLOBALS['TCA'][$table]['ctrl']['requestUpdate']), $field)
        ) {
            if ($this->getBackendUserAuthentication()->jsConfirmation(JsConfirmation::TYPE_CHANGE)) {
                $onChange = 'top.TYPO3.Modal.confirm(TBE_EDITOR.labels.refreshRequired.title, TBE_EDITOR.labels.refreshRequired.content).on("button.clicked", function(e) { if (e.target.name == "ok" && TBE_EDITOR.checkSubmit(-1)) { TBE_EDITOR.submitForm() } top.TYPO3.Modal.dismiss(); });';
            } else {
                $onChange .= 'if (TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
            }
        }
        $html = '
			<div class="typo3-tceforms-tree">
				<input class="treeRecord" type="hidden" '
                    .  $this->getValidationDataAsDataAttribute($config)
                    . ' data-formengine-input-name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"'
                    . ' data-relatedfieldname="' . htmlspecialchars($parameterArray['itemFormElName']) . '"'
                    . ' name="' . htmlspecialchars($parameterArray['itemFormElName']) . '" id="treeinput' . $id . '" value="' . htmlspecialchars(implode(',', $selectedNodesForApi)) . '" />
			</div>
			<div id="tree_' . $id . '">

			</div>';

        // Wizards:
        if (empty($config['readOnly'])) {
            $html = $this->renderWizards(
                array($html),
                $config['wizards'],
                $table,
                $row,
                $field,
                $parameterArray,
                $parameterArray['itemFormElName'],
                BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras'])
            );
        }
        $resultArray = $this->initializeResultArray();
        $resultArray['extJSCODE'] .= LF .
            'Ext.onReady(function() {
			TYPO3.Components.Tree.StandardTreeItemData["' . $id . '"] = ' . json_encode($itemArray) . ';
			var tree' . $id . ' = new TYPO3.Components.Tree.StandardTree({
				id: "' . $id . '",
				showHeader: ' . (int)$header . ',
				onChange: ' . GeneralUtility::quoteJSvalue($onChange) . ',
				countSelectedNodes: ' . count($selectedNodes) . ',
				width: ' . (int)$width . ',
				rendering: false,
				listeners: {
					click: function(node, event) {
						if (typeof(node.attributes.checked) == "boolean") {
							node.attributes.checked = ! node.attributes.checked;
							node.getUI().toggleCheck(node.attributes.checked);
						}
					},
					dblclick: function(node, event) {
						if (typeof(node.attributes.checked) == "boolean") {
							node.attributes.checked = ! node.attributes.checked;
							node.getUI().toggleCheck(node.attributes.checked);
						}
					},
					checkchange: TYPO3.Components.Tree.TcaCheckChangeHandler,
					collapsenode: function(node) {
						if (node.id !== "root" && !this.rendering) {
							top.TYPO3.Storage.Persistent.removeFromList("tcaTrees." + this.ucId, node.attributes.uid);
						}
					},
					expandnode: function(node) {
						if (node.id !== "root" && !this.rendering) {
							top.TYPO3.Storage.Persistent.addToList("tcaTrees." + this.ucId, node.attributes.uid);
						}
					},
					beforerender: function(treeCmp) {
					    this.rendering = true;
						// Check if that tree element is already rendered. It is appended on the first tceforms_inline call.
						if (Ext.fly(treeCmp.getId())) {
							return false;
						}
					},
					afterrender: function(treeCmp) {
					    ' . ($expanded ? 'treeCmp.expandAll();' : '') . '
					    this.rendering = false;
					}
				},
				tcaMaxItems: ' . ($config['maxitems'] ? (int)$config['maxitems'] : 99999) . ',
				tcaSelectRecursiveAllowed: ' . ($appearance['allowRecursiveMode'] ? 'true' : 'false') . ',
				tcaSelectRecursive: false,
				tcaExclusiveKeys: "' . ($config['exclusiveKeys'] ? $config['exclusiveKeys'] : '') . '",
				ucId: "' . md5(($table . '|' . $field)) . '",
				selModel: TYPO3.Components.Tree.EmptySelectionModel,
				disabled: ' . ($config['readOnly'] ? 'true' : 'false') . '
			});' . LF .
            ($autoSizeMax
                ? 'tree' . $id . '.bodyStyle = "max-height: ' . $autoSizeMax . 'px;min-height: ' . $height . 'px;";'
                : 'tree' . $id . '.height = ' . $height . ';'
            ) . LF .
            'window.setTimeout(function() {
				tree' . $id . '.render("tree_' . $id . '");
			}, 200);
		});';
        $resultArray['html'] = $html;

        return $resultArray;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
