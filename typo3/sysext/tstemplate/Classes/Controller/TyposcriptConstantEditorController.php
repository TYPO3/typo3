<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Tstemplate\Controller;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\TypoScript\Parser\ConstantConfigurationParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Fluid\View\BackendTemplateView;

/**
 * TypoScript Constant editor
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TyposcriptConstantEditorController extends TypoScriptTemplateModuleController
{
    protected array $categories = [
        'basic' => [],
        // Constants of superior importance for the template-layout. This is dimensions, imagefiles and enabling of various features. The most basic constants, which you would almost always want to configure.
        'menu' => [],
        // Menu setup. This includes fontfiles, sizes, background images. Depending on the menutype.
        'content' => [],
        // All constants related to the display of pagecontent elements
        'page' => [],
        // General configuration like metatags, link targets
        'advanced' => [],
        // Advanced functions, which are used very seldom.
        'all' => [],
    ];

    /**
     * The currently selected sys_template record
     * @var array|false|null
     */
    protected $templateRow;

    /**
     * @var array
     */
    protected $constants;

    protected ConstantConfigurationParser $constantParser;

    /**
     * @var array<string, JavaScriptModuleInstruction>
     */
    protected array $javaScriptInstructions = [];

    /**
     * Init, called from parent object
     *
     * @param TypoScriptTemplateModuleController $pObj A reference to the parent (calling) object
     * @param ServerRequestInterface $request
     */
    public function init($pObj, ServerRequestInterface $request)
    {
        $this->constantParser = GeneralUtility::makeInstance(ConstantConfigurationParser::class);
        parent::init($pObj, $request);
    }

    /**
     * Initialize editor
     *
     * Initializes the module.
     * Done in this function because we may need to re-initialize if data is submitted!
     *
     * @param int $pageId
     * @param int $template_uid
     * @return bool
     */
    protected function initialize_editor($pageId, $template_uid = 0)
    {
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);

        // Get the row of the first VISIBLE template of the page. whereclause like the frontend.
        $this->templateRow = $this->getFirstTemplateRecordOnPage((int)$pageId, $template_uid);
        // IF there was a template...
        if (is_array($this->templateRow)) {
            // Gets the rootLine
            $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId);
            $rootLine = $rootlineUtility->get();
            // This generates the constants/config + hierarchy info for the template.
            $this->templateService->runThroughTemplates($rootLine, $template_uid);
            // The editable constants are returned in an array.
            $this->constants = $this->templateService->generateConfig_constants();
            // The returned constants are sorted in categories, that goes into the $tmpl->categories array
            $this->categorizeEditableConstants($this->constants);
            // This array will contain key=[expanded constant name], value=line number in template.
            $this->templateService->ext_regObjectPositions((string)$this->templateRow['constants']);
            return true;
        }
        return false;
    }

    /**
     * Main, called from parent object
     *
     * @return string
     */
    public function main()
    {
        $assigns = [];
        // Create extension template
        $this->createTemplate($this->id);
        // Checking for more than one template an if, set a menu...
        $manyTemplatesMenu = $this->templateMenu($this->request);
        $template_uid = 0;
        if ($manyTemplatesMenu) {
            $template_uid = $this->MOD_SETTINGS['templatesOnPage'];
        }

        // initialize
        $existTemplate = $this->initialize_editor($this->id, $template_uid);
        if ($existTemplate) {
            $assigns['templateRecord'] = $this->templateRow;
            $assigns['manyTemplatesMenu'] = $manyTemplatesMenu;

            $saveId = empty($this->templateRow['_ORIG_uid']) ? $this->templateRow['uid'] : $this->templateRow['_ORIG_uid'];
            // Update template ?
            if ($this->request->getParsedBody()['_savedok'] ?? false) {
                $this->templateService->changed = false;
                $this->templateService->ext_procesInput($this->request->getParsedBody(), $this->constants);
                if ($this->templateService->changed) {
                    // Set the data to be saved
                    $recData = [];
                    $recData['sys_template'][$saveId]['constants'] = implode(LF, $this->templateService->raw);
                    // Create new  tce-object
                    $tce = GeneralUtility::makeInstance(DataHandler::class);
                    $tce->start($recData, []);
                    $tce->process_datamap();
                    // re-read the template ...
                    // re-read the constants as they have changed
                    $this->initialize_editor($this->id, $template_uid);
                }
            }
            // Resetting the menu (start). I wonder if this in any way is a violation of the menu-system. Haven't checked. But need to do it here, because the menu is dependent on the categories available.
            $this->MOD_MENU['constant_editor_cat'] = $this->getCategoryLabels();
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $this->request->getParsedBody()['SET'] ?? $this->request->getQueryParams()['SET'] ?? [], 'web_ts');
            // Resetting the menu (stop)
            $assigns['title'] = $this->linkWrapTemplateTitle($this->templateRow['title'], 'constants');
            if (!empty($this->MOD_MENU['constant_editor_cat'])) {
                $assigns['constantsMenu'] = BackendUtility::getDropdownMenu($this->id, 'SET[constant_editor_cat]', $this->MOD_SETTINGS['constant_editor_cat'], $this->MOD_MENU['constant_editor_cat']);
            }
            // Category and constant editor config:
            $category = $this->MOD_SETTINGS['constant_editor_cat'];

            $assigns['editorFields'] = $this->printFields($this->constants, $category);
            foreach ($this->javaScriptInstructions as $instruction) {
                $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction($instruction);
            }

            // Rendering of the output via fluid
            $view = GeneralUtility::makeInstance(BackendTemplateView::class);
            $view->setTemplateRootPaths(['EXT:tstemplate/Resources/Private/Templates']);
            $view->assignMultiple($assigns);
            $theOutput = $view->render('ConstantEditor');
        } else {
            $theOutput = $this->noTemplate(1);
        }
        return $theOutput;
    }

    protected function fNandV(array $params): array
    {
        $fN = 'data[' . $params['name'] . ']';
        $idName = str_replace('.', '-', $params['name']);
        $fV = $params['value'];
        // Values entered from the constantsedit cannot be constants!	230502; removed \{ and set {
        if (preg_match('/^{[\\$][a-zA-Z0-9\\.]*}$/', trim($fV), $reg)) {
            $fV = '';
        }
        $fV = htmlspecialchars($fV);
        return [$fN, $fV, $params, $idName];
    }

    /**
     * This functions returns the HTML-code that creates the editor-layout of the module.
     *
     * @param array $theConstants
     * @param string $category
     * @return array
     */
    protected function printFields(array $theConstants, string $category): array
    {
        reset($theConstants);
        $groupedOutput = [];
        $subcat = '';
        if (!empty($this->categories[$category]) && is_array($this->categories[$category])) {
            asort($this->categories[$category]);
            $categoryLoop = 0;
            foreach ($this->categories[$category] as $name => $type) {
                $params = $theConstants[$name];
                if (is_array($params)) {
                    if ($subcat !== (string)($params['subcat_name'] ?? '')) {
                        $categoryLoop++;
                        $subcat = (string)($params['subcat_name'] ?? '');
                        $subcat_name = $subcat ? (string)($this->constantParser->getSubCategories()[$subcat][0] ?? '') : 'Others';
                        $groupedOutput[$categoryLoop] = [
                            'label' => $subcat_name,
                            'fields' => [],
                        ];
                    }
                    $label = $this->getLanguageService()->sL($params['label']);
                    $label_parts = explode(':', $label, 2);
                    if (count($label_parts) === 2) {
                        $head = trim($label_parts[0]);
                        $body = trim($label_parts[1]);
                    } else {
                        $head = trim($label_parts[0]);
                        $body = '';
                    }
                    $typeDat = $this->templateService->ext_getTypeData($params['type']);
                    $p_field = '';
                    $fragmentName = substr(md5($params['name']), 0, 10);
                    $fragmentNameEscaped = htmlspecialchars($fragmentName);
                    [$fN, $fV, $params, $idName] = $this->fNandV($params);
                    $idName = htmlspecialchars($idName);
                    $hint = '';
                    switch ($typeDat['type']) {
                        case 'int':
                        case 'int+':
                            $additionalAttributes = '';
                            if ($typeDat['paramstr'] ?? false) {
                                $hint = ' Range: ' . $typeDat['paramstr'];
                            } elseif ($typeDat['type'] === 'int+') {
                                $hint = ' Range: 0 - ';
                                $typeDat['min'] = 0;
                            } else {
                                $hint = ' (Integer)';
                            }

                            if (isset($typeDat['min'])) {
                                $additionalAttributes .= ' min="' . (int)$typeDat['min'] . '" ';
                            }
                            if (isset($typeDat['max'])) {
                                $additionalAttributes .= ' max="' . (int)$typeDat['max'] . '" ';
                            }

                            $p_field =
                                '<input class="form-control" id="' . $idName . '" type="number"'
                                . ' name="' . $fN . '" value="' . $fV . '" data-form-update-fragment="' . $fragmentNameEscaped . '" ' . $additionalAttributes . ' />';
                            break;
                        case 'color':
                            $p_field = '
                                <input class="form-control formengine-colorpickerelement t3js-color-picker" type="text" id="input-' . $idName . '" rel="' . $idName .
                                '" name="' . $fN . '" value="' . $fV . '" data-form-update-fragment="' . $fragmentNameEscaped . '"/>';

                            $this->javaScriptInstructions['color'] ??= JavaScriptModuleInstruction::create('@typo3/backend/color-picker.js')
                                ->invoke('initialize');
                            break;
                        case 'wrap':
                            $wArr = explode('|', $fV);
                            $p_field = '<div class="input-group">
                                            <input class="form-control form-control-adapt" type="text" id="' . $idName . '" name="' . $fN . '" value="' . $wArr[0] . '" data-form-update-fragment="' . $fragmentNameEscaped . '" />
                                            <span class="input-group-addon input-group-icon">|</span>
                                            <input class="form-control form-control-adapt" type="text" name="W' . $fN . '" value="' . $wArr[1] . '" data-form-update-fragment="' . $fragmentNameEscaped . '" />
                                         </div>';
                            break;
                        case 'offset':
                            $wArr = explode(',', $fV);
                            $labels = GeneralUtility::trimExplode(',', $typeDat['paramstr']);
                            $p_field = '<span class="input-group-addon input-group-icon">' . ($labels[0] ?: 'x') . '</span><input type="text" class="form-control form-control-adapt" name="' . $fN . '" value="' . $wArr[0] . '" data-form-update-fragment="' . $fragmentNameEscaped . '" />';
                            $p_field .= '<span class="input-group-addon input-group-icon">' . ($labels[1] ?: 'y') . '</span><input type="text" name="W' . $fN . '" value="' . $wArr[1] . '" class="form-control form-control-adapt" data-form-update-fragment="' . $fragmentNameEscaped . '" />';
                            $labelsCount = count($labels);
                            for ($aa = 2; $aa < $labelsCount; $aa++) {
                                if ($labels[$aa]) {
                                    $p_field .= '<span class="input-group-addon input-group-icon">' . $labels[$aa] . '</span><input type="text" name="W' . $aa . $fN . '" value="' . $wArr[$aa] . '" class="form-control form-control-adapt" data-form-update-fragment="' . $fragmentNameEscaped . '" />';
                                } else {
                                    $p_field .= '<input type="hidden" name="W' . $aa . $fN . '" value="' . $wArr[$aa] . '" />';
                                }
                            }
                            $p_field = '<div class="input-group">' . $p_field . '</div>';
                            break;
                        case 'options':
                            if (is_array($typeDat['params'])) {
                                $p_field = '';
                                foreach ($typeDat['params'] as $val) {
                                    $vParts = explode('=', $val, 2);
                                    $label = $vParts[0];
                                    $val = $vParts[1] ?? $vParts[0];
                                    // option tag:
                                    $sel = '';
                                    if ($val === $params['value']) {
                                        $sel = ' selected';
                                    }
                                    $p_field .= '<option value="' . htmlspecialchars($val) . '"' . $sel . '>' . $this->getLanguageService()->sL($label) . '</option>';
                                }
                                $p_field = '<select class="form-select" id="' . $idName . '" name="' . $fN . '" data-form-update-fragment="' . $fragmentNameEscaped . '">' . $p_field . '</select>';
                            }
                            break;
                        case 'boolean':
                            $sel = $fV ? 'checked' : '';
                            $p_field =
                                '<input type="hidden" name="' . $fN . '" value="0" />'
                                . '<label class="btn btn-default btn-checkbox">'
                                . '<input id="' . $idName . '" type="checkbox" name="' . $fN . '" value="' . (($typeDat['paramstr'] ?? false) ?: 1) . '" ' . $sel . ' data-form-update-fragment="' . $fragmentNameEscaped . '" />'
                                . '<span class="t3-icon fa"></span>'
                                . '</label>';
                            break;
                        case 'comment':
                            $sel = $fV ? '' : 'checked';
                            $p_field =
                                '<input type="hidden" name="' . $fN . '" value="" />'
                                . '<label class="btn btn-default btn-checkbox">'
                                . '<input id="' . $idName . '" type="checkbox" name="' . $fN . '" value="1" ' . $sel . ' data-form-update-fragment="' . $fragmentNameEscaped . '" />'
                                . '<span class="t3-icon fa"></span>'
                                . '</label>';
                            break;
                        case 'file':
                            // extensionlist
                            $extList = $typeDat['paramstr'];
                            if ($extList === 'IMAGE_EXT') {
                                $extList = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];
                            }
                            $p_field = '<option value="">(' . $extList . ')</option>';
                            if (trim($params['value'])) {
                                $val = $params['value'];
                                $p_field .= '<option value=""></option>';
                                $p_field .= '<option value="' . htmlspecialchars($val) . '" selected>' . $val . '</option>';
                            }
                            $p_field = '<select class="form-select" id="' . $idName . '" name="' . $fN . '" data-form-update-fragment="' . $fragmentNameEscaped . '">' . $p_field . '</select>';
                            break;
                        case 'user':
                            $userFunction = $typeDat['paramstr'];
                            $userFunctionParams = ['fieldName' => $fN, 'fieldValue' => $fV];
                            $p_field = GeneralUtility::callUserFunction($userFunction, $userFunctionParams, $this);
                            break;
                        default:
                            $p_field = '<input class="form-control" id="' . $idName . '" type="text" name="' . $fN . '" value="' . $fV . '" data-form-update-fragment="' . $fragmentNameEscaped . '" />';
                    }
                    // Define default names and IDs
                    $userTyposcriptID = 'userTS-' . $idName;
                    $defaultTyposcriptID = 'defaultTS-' . $idName;
                    $userTyposcriptStyle = '';
                    // Set the default styling options
                    if (isset($this->templateService->objReg[$params['name']])) {
                        $checkboxValue = 'checked';
                        $defaultTyposcriptStyle = 'style="display:none;"';
                    } else {
                        $checkboxValue = '';
                        $userTyposcriptStyle = 'style="display:none;"';
                        $defaultTyposcriptStyle = '';
                    }
                    $deleteIconHTML =
                        '<button type="button" class="btn btn-default t3js-toggle" data-bs-toggle="undo" rel="' . $idName . '">'
                        . '<span title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deleteTitle')) . '">'
                        . $this->iconFactory->getIcon('actions-edit-undo', Icon::SIZE_SMALL)->render()
                        . '</span>'
                        . '</button>';
                    $editIconHTML =
                        '<button type="button" class="btn btn-default t3js-toggle" data-bs-toggle="edit"  rel="' . $idName . '">'
                        . '<span title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editTitle')) . '">'
                        . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render()
                        . '</span>'
                        . '</button>';
                    $constantCheckbox = '<input type="hidden" name="check[' . $params['name'] . ']" id="check-' . $idName . '" value="' . $checkboxValue . '"/>';
                    // If there's no default value for the field, use a static label.
                    if (!$params['default_value']) {
                        $params['default_value'] = '[Empty]';
                    }
                    $constantDefaultRow =
                        '<div class="input-group defaultTS" id="' . $defaultTyposcriptID . '" ' . $defaultTyposcriptStyle . '>'
                        . '<span class="input-group-btn">' . $editIconHTML . '</span>'
                        . '<input class="form-control" type="text" placeholder="' . htmlspecialchars($params['default_value']) . '" readonly>'
                        . '</div>';
                    $constantEditRow =
                        '<div class="input-group userTS" id="' . $userTyposcriptID . '" ' . $userTyposcriptStyle . '>'
                        . '<span class="input-group-btn">' . $deleteIconHTML . '</span>'
                        . $p_field
                        . '</div>';
                    $constantData =
                        $constantCheckbox
                        . $constantEditRow
                        . $constantDefaultRow;

                    $groupedOutput[$categoryLoop]['items'][] = [
                        'identifier' => $fragmentName,
                        'label' => $head,
                        'name' => $params['name'],
                        'description' => $body,
                        'hint' => $hint,
                        'data' => $constantData,
                    ];
                } else {
                    debug('Error. Constant did not exist. Should not happen.');
                }
            }
        }
        return $groupedOutput;
    }

    protected function categorizeEditableConstants(array $editConstArray): void
    {
        // Runs through the available constants and fills the $this->categories array with pointers and priority-info
        foreach ($editConstArray as $constName => $constData) {
            if (!$constData['type']) {
                $constData['type'] = 'string';
            }
            $cats = explode(',', $constData['cat']);
            // if = only one category, while allows for many. We have agreed on only one category is the most basic way...
            foreach ($cats as $theCat) {
                $theCat = trim($theCat);
                if ($theCat) {
                    $this->categories[$theCat][$constName] = $constData['subcat'];
                }
            }
        }
    }

    protected function getCategoryLabels(): array
    {
        // Returns array used for labels in the menu.
        $retArr = [];
        foreach ($this->categories as $k => $v) {
            if (!empty($v)) {
                $retArr[$k] = strtoupper($k) . ' (' . count($v) . ')';
            }
        }
        return $retArr;
    }
}
