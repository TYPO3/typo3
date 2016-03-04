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
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render data as a tree.
 *
 * Typically rendered for config type=select, renderType=selectTree
 */
class SelectTreeElement extends AbstractFormElement
{
    /**
     * Default height of the tree in pixels.
     *
     * @const
     */
    const DEFAULT_HEIGHT = 280;

    /**
     * Default width of the tree in pixels.
     *
     * @const
     */
    const DEFAULT_WIDTH = 280;

    /**
     * Render tree widget
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @see AbstractNode::initializeResultArray()
     */
    public function render()
    {
        $resultArray = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'];
        $formElementId = md5($parameterArray['itemFormElName']);

        // Field configuration from TCA:
        $config = $parameterArray['fieldConf']['config'];

        $resultArray['extJSCODE'] .= LF . $this->generateJavascript($formElementId);

        $html = [];
        $html[] = '<div class="typo3-tceforms-tree">';
        $html[] = '    <input class="treeRecord" type="hidden"';
        $html[] = '           ' . $this->getValidationDataAsDataAttribute($parameterArray['fieldConf']['config']);
        $html[] = '           data-formengine-input-name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"';
        $html[] = '           data-relatedfieldname="' . htmlspecialchars($parameterArray['itemFormElName']) . '"';
        $html[] = '           name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"';
        $html[] = '           id="treeinput' . $formElementId . '"';
        $html[] = '           value="' . htmlspecialchars(implode(',', $config['treeData']['selectedNodes'])) . '"';
        $html[] = '    />';
        $html[] = '</div>';
        $html[] = '<div id="tree_' . $formElementId . '"></div>';

        $resultArray['html'] = implode(LF, $html);

        // Wizards:
        if (empty($config['readOnly'])) {
            $resultArray['html'] = $this->renderWizards(
                [$resultArray['html']],
                $config['wizards'],
                $this->data['tableName'],
                $this->data['databaseRow'],
                $this->data['fieldName'],
                $parameterArray,
                $parameterArray['itemFormElName'],
                BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras'])
            );
        }

        return $resultArray;
    }

    /**
     * Generates the Ext JS tree JavaScript code.
     *
     * @param string $formElementId The HTML element ID of the tree select field.
     * @return string
     */
    protected function generateJavascript($formElementId)
    {
        $table = $this->data['tableName'];
        $field = $this->data['fieldName'];
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];

        $disabled = !empty($config['readOnly']) ? 'true' : 'false';
        $maxItems = $config['maxitems'] ? (int)$config['maxitems'] : 99999;
        $exclusiveKeys = !empty($config['exclusiveKeys']) ? $config['exclusiveKeys'] : '';

        $appearance = !empty($config['treeConfig']['appearance']) ? $config['treeConfig']['appearance'] : [];
        $width = isset($appearance['width']) ? (int)$appearance['width'] : static::DEFAULT_WIDTH;
        if (isset($config['size']) && (int)$config['size'] > 0) {
            $height = (int)$config['size'] * 20;
        } else {
            $height = static::DEFAULT_HEIGHT;
        }
        $showHeader = !empty($appearance['showHeader']);
        $expanded = !empty($appearance['expandAll']);
        $allowRecursiveMode = !empty($appearance['allowRecursiveMode']) ? 'true' : 'false';

        $autoSizeMax = null;
        if (isset($config['autoSizeMax']) && (int)$config['autoSizeMax'] > 0) {
            $autoSizeMax = (int)$config['autoSizeMax'] * 20;
        }

        $onChange = !empty($parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged']) ? $parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] : '';
        $onChange .= !empty($parameterArray['fieldChangeFunc']['alert']) ? $parameterArray['fieldChangeFunc']['alert'] : '';

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

        $javascript = [];
        $javascript[] = 'Ext.onReady(function() {';
        $javascript[] = '    TYPO3.Components.Tree.StandardTreeItemData["' . $formElementId . '"] = ' . json_encode($config['treeData']['items']) . ';';
        $javascript[] = '    var tree' . $formElementId . ' = new TYPO3.Components.Tree.StandardTree({';
        $javascript[] = '        id: "' . $formElementId . '",';
        $javascript[] = '        stateful: true,';
        $javascript[] = '        stateId: "tcaTrees." + this.ucId,';
        $javascript[] = '        stateEvents: [],';
        $javascript[] = '        showHeader: ' . (int)$showHeader . ',';
        $javascript[] = '        onChange: ' . GeneralUtility::quoteJSvalue($onChange) . ',';
        $javascript[] = '        countSelectedNodes: ' . count($config['treeData']['selectedNodes']) . ',';
        $javascript[] = '        width: ' . $width . ',';
        $javascript[] = '        rendering: false,';
        $javascript[] = '        listeners: {';
        $javascript[] = '            click: function(node, event) {';
        $javascript[] = '                if (typeof(node.attributes.checked) == "boolean") {';
        $javascript[] = '                    node.attributes.checked = ! node.attributes.checked;';
        $javascript[] = '                    node.getUI().toggleCheck(node.attributes.checked);';
        $javascript[] = '                }';
        $javascript[] = '            },';
        $javascript[] = '            dblclick: function(node, event) {';
        $javascript[] = '                if (typeof(node.attributes.checked) == "boolean") {';
        $javascript[] = '                    node.attributes.checked = ! node.attributes.checked;';
        $javascript[] = '                    node.getUI().toggleCheck(node.attributes.checked);';
        $javascript[] = '                }';
        $javascript[] = '            },';
        $javascript[] = '            checkchange: TYPO3.Components.Tree.TcaCheckChangeHandler,';
        $javascript[] = '            collapsenode: function(node) {';
        $javascript[] = '                if (node.id !== "root" && !this.rendering) {';
        $javascript[] = '                    top.TYPO3.Storage.Persistent.removeFromList("tcaTrees." + this.ucId, node.attributes.uid);';
        $javascript[] = '                }';
        $javascript[] = '            },';
        $javascript[] = '            expandnode: function(node) {';
        $javascript[] = '                if (node.id !== "root" && !this.rendering) {';
        $javascript[] = '                    top.TYPO3.Storage.Persistent.addToList("tcaTrees." + this.ucId, node.attributes.uid);';
        $javascript[] = '                }';
        $javascript[] = '            },';
        $javascript[] = '            beforerender: function(treeCmp) {';
        $javascript[] = '                this.rendering = true';
        $javascript[] = '                // Check if that tree element is already rendered. It is appended on the first tceforms_inline call.';
        $javascript[] = '                if (Ext.fly(treeCmp.getId())) {';
        $javascript[] = '                    return false;';
        $javascript[] = '                }';
        $javascript[] = '            },';
        $javascript[] = '            afterrender: function(treeCmp) {';
        if ($expanded) {
            $javascript[] = '                treeCmp.expandAll();';
        }
        $javascript[] = '                this.rendering = false;';
        $javascript[] = '            }';
        $javascript[] = '        },';
        $javascript[] = '        tcaMaxItems: ' . $maxItems . ',';
        $javascript[] = '        tcaSelectRecursiveAllowed: ' . $allowRecursiveMode . ',';
        $javascript[] = '        tcaSelectRecursive: false,';
        $javascript[] = '        tcaExclusiveKeys: "' . $exclusiveKeys . '",';
        $javascript[] = '        ucId: "' . md5(($table . '|' . $field)) . '",';
        $javascript[] = '        selModel: TYPO3.Components.Tree.EmptySelectionModel,';
        $javascript[] = '        disabled: ' . $disabled;
        $javascript[] = '    });';

        if ($autoSizeMax) {
            $javascript[] = '    tree' . $formElementId . '.bodyStyle = "max-height: ' . $autoSizeMax . 'px;min-height: ' . $height . 'px;";';
        } else {
            $javascript[] = '    tree' . $formElementId . '.height = ' . $height . ';';
        }

        $javascript[] = '    window.setTimeout(function() {';
        $javascript[] = '        tree' . $formElementId . '.render("tree_' . $formElementId . '");';
        $javascript[] = '    }, 200);';
        $javascript[] = '});';

        return implode(LF, $javascript);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
