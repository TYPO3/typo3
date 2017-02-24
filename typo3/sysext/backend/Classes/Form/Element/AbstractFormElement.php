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

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\DatabaseFileIconsHookInterface;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\OnTheFly;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Form\Wizard\SuggestWizard;
use TYPO3\CMS\Backend\Form\Wizard\ValueSliderWizard;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Base class for form elements of FormEngine. Contains several helper methods used by single elements.
 */
abstract class AbstractFormElement extends AbstractNode
{
    /**
     * Default width value for a couple of elements like text
     *
     * @var int
     */
    protected $defaultInputWidth = 30;

    /**
     * Minimum width value for a couple of elements like text
     *
     * @var int
     */
    protected $minimumInputWidth = 10;

    /**
     * Maximum width value for a couple of elements like text
     *
     * @var int
     */
    protected $maxInputWidth = 50;

    /**
     * @var \TYPO3\CMS\Backend\Clipboard\Clipboard|NULL
     */
    protected $clipboard = null;

    /**
     * Container objects give $nodeFactory down to other containers.
     *
     * @param NodeFactory $nodeFactory
     * @param array $data
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        // @todo: this must vanish as soon as elements are clean
        $this->nodeFactory = $nodeFactory;
    }

    /**
     * @return bool TRUE if wizards are disabled on a global level
     */
    protected function isWizardsDisabled()
    {
        return !empty($this->data['disabledWizards']);
    }

    /**
     * Returns the max width in pixels for an elements like input and text
     *
     * @param int $size The abstract size value (1-48)
     * @return int Maximum width in pixels
     */
    protected function formMaxWidth($size = 48)
    {
        $compensationForLargeDocuments = 1.33;
        $compensationForFormFields = 12;

        $size = round($size * $compensationForLargeDocuments);
        return ceil($size * $compensationForFormFields);
    }

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Rendering wizards for form fields.
     *
     * @param array $itemKinds Array with the real item in the first value
     * @param array $wizConf The "wizards" key from the config array for the field (from TCA)
     * @param string $table Table name
     * @param array $row The record array
     * @param string $field The field name
     * @param array $PA Additional configuration array.
     * @param string $itemName The field name
     * @param array $specConf Special configuration if available.
     * @param bool $RTE Whether the RTE could have been loaded.
     *
     * @return string The new item value.
     * @throws \InvalidArgumentException
     */
    protected function renderWizards($itemKinds, $wizConf, $table, $row, $field, $PA, $itemName, $specConf, $RTE = false)
    {
        // Return not changed main item directly if wizards are disabled
        if (!is_array($wizConf) || $this->isWizardsDisabled()) {
            return $itemKinds[0];
        }

        $languageService = $this->getLanguageService();

        $fieldChangeFunc = $PA['fieldChangeFunc'];
        $item = $itemKinds[0];
        $md5ID = 'ID' . GeneralUtility::shortmd5($itemName);
        $prefixOfFormElName = 'data[' . $table . '][' . $row['uid'] . '][' . $field . ']';
        $flexFormPath = '';
        if (GeneralUtility::isFirstPartOfStr($PA['itemFormElName'], $prefixOfFormElName)) {
            $flexFormPath = str_replace('][', '/', substr($PA['itemFormElName'], strlen($prefixOfFormElName) + 1, -1));
        }

        // Add a suffix-value if the item is a selector box with renderType "selectSingleBox":
        if ($PA['fieldConf']['config']['type'] === 'select' && (int)$PA['fieldConf']['config']['maxitems'] > 1 && $PA['fieldConf']['config']['renderType'] === 'selectSingleBox') {
            $itemName .= '[]';
        }

        // Contains wizard identifiers enabled for this record type, see "special configuration" docs
        $wizardsEnabledByType = $specConf['wizards']['parameters'];

        $buttonWizards = [];
        $otherWizards = [];
        foreach ($wizConf as $wizardIdentifier => $wizardConfiguration) {
            if (!isset($wizardConfiguration['module']['name']) && isset($wizardConfiguration['script'])) {
                throw new \InvalidArgumentException('The way registering a wizard in TCA has changed in 6.2 and was removed in CMS 7. '
                    . 'Please set module[name]=module_name instead of using script=path/to/script.php in your TCA. ', 1437750231);
            }

            // If an identifier starts with "_", this is a configuration option like _POSITION and not a wizard
            if ($wizardIdentifier[0] === '_') {
                continue;
            }

            // Sanitize wizard type
            $wizardConfiguration['type'] = (string)$wizardConfiguration['type'];

            // Wizards can be shown based on selected "type" of record. If this is the case, the wizard configuration
            // is set to enableByTypeConfig = 1, and the wizardIdentifier is found in $wizardsEnabledByType
            $wizardIsEnabled = true;
            if (
                isset($wizardConfiguration['enableByTypeConfig'])
                && (bool)$wizardConfiguration['enableByTypeConfig']
                && (!is_array($wizardsEnabledByType) || !in_array($wizardIdentifier, $wizardsEnabledByType))
            ) {
                $wizardIsEnabled = false;
            }
            // Disable if wizard is for RTE fields only and the handled field is no RTE field or RTE can not be loaded
            if (isset($wizardConfiguration['RTEonly']) && (bool)$wizardConfiguration['RTEonly'] && !$RTE) {
                $wizardIsEnabled = false;
            }
            // Disable if wizard is for not-new records only and we're handling a new record
            if (isset($wizardConfiguration['notNewRecords']) && $wizardConfiguration['notNewRecords'] && !MathUtility::canBeInterpretedAsInteger($row['uid'])) {
                $wizardIsEnabled = false;
            }
            // Wizard types script, colorbox and popup must contain a module name configuration
            if (!isset($wizardConfiguration['module']['name']) && in_array($wizardConfiguration['type'], ['script', 'colorbox', 'popup'], true)) {
                $wizardIsEnabled = false;
            }

            if (!$wizardIsEnabled) {
                continue;
            }

            // Title / icon:
            $iTitle = htmlspecialchars($languageService->sL($wizardConfiguration['title']));
            if (isset($wizardConfiguration['icon'])) {
                $icon = FormEngineUtility::getIconHtml($wizardConfiguration['icon'], $iTitle, $iTitle);
            } else {
                $icon = $iTitle;
            }

            switch ($wizardConfiguration['type']) {
                case 'userFunc':
                    $params = [];
                    $params['params'] = $wizardConfiguration['params'];
                    $params['exampleImg'] = $wizardConfiguration['exampleImg'];
                    $params['table'] = $table;
                    $params['uid'] = $row['uid'];
                    $params['pid'] = $row['pid'];
                    $params['field'] = $field;
                    $params['flexFormPath'] = $flexFormPath;
                    $params['md5ID'] = $md5ID;
                    $params['returnUrl'] = $this->data['returnUrl'];

                    $params['formName'] = 'editform';
                    $params['itemName'] = $itemName;
                    $params['hmac'] = GeneralUtility::hmac($params['formName'] . $params['itemName'], 'wizard_js');
                    $params['fieldChangeFunc'] = $fieldChangeFunc;
                    $params['fieldChangeFuncHash'] = GeneralUtility::hmac(serialize($fieldChangeFunc));

                    $params['item'] = &$item;
                    $params['icon'] = $icon;
                    $params['iTitle'] = $iTitle;
                    $params['wConf'] = $wizardConfiguration;
                    $params['row'] = $row;
                    $otherWizards[] = GeneralUtility::callUserFunction($wizardConfiguration['userFunc'], $params, $this);
                    break;

                case 'script':
                    $params = [];
                    $params['params'] = $wizardConfiguration['params'];
                    $params['exampleImg'] = $wizardConfiguration['exampleImg'];
                    $params['table'] = $table;
                    $params['uid'] = $row['uid'];
                    $params['pid'] = $row['pid'];
                    $params['field'] = $field;
                    $params['flexFormPath'] = $flexFormPath;
                    $params['md5ID'] = $md5ID;
                    $params['returnUrl'] = $this->data['returnUrl'];

                    // Resolving script filename and setting URL.
                    $urlParameters = [];
                    if (isset($wizardConfiguration['module']['urlParameters']) && is_array($wizardConfiguration['module']['urlParameters'])) {
                        $urlParameters = $wizardConfiguration['module']['urlParameters'];
                    }
                    $wScript = BackendUtility::getModuleUrl($wizardConfiguration['module']['name'], $urlParameters, '');
                    $url = $wScript . (strstr($wScript, '?') ? '' : '?') . GeneralUtility::implodeArrayForUrl('', ['P' => $params]);
                    $buttonWizards[] =
                        '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" onclick="this.blur(); return !TBE_EDITOR.isFormChanged();">'
                            . $icon .
                        '</a>';
                    break;

                case 'popup':
                    $params = [];
                    $params['params'] = $wizardConfiguration['params'];
                    $params['exampleImg'] = $wizardConfiguration['exampleImg'];
                    $params['table'] = $table;
                    $params['uid'] = $row['uid'];
                    $params['pid'] = $row['pid'];
                    $params['field'] = $field;
                    $params['flexFormPath'] = $flexFormPath;
                    $params['md5ID'] = $md5ID;
                    $params['returnUrl'] = $this->data['returnUrl'];

                    $params['formName'] = 'editform';
                    $params['itemName'] = $itemName;
                    $params['hmac'] = GeneralUtility::hmac($params['formName'] . $params['itemName'], 'wizard_js');
                    $params['fieldChangeFunc'] = $fieldChangeFunc;
                    $params['fieldChangeFuncHash'] = GeneralUtility::hmac(serialize($fieldChangeFunc));

                    // Resolving script filename and setting URL.
                    $urlParameters = [];
                    if (isset($wizardConfiguration['module']['urlParameters']) && is_array($wizardConfiguration['module']['urlParameters'])) {
                        $urlParameters = $wizardConfiguration['module']['urlParameters'];
                    }
                    $wScript = BackendUtility::getModuleUrl($wizardConfiguration['module']['name'], $urlParameters, '');
                    $url = $wScript . (strstr($wScript, '?') ? '' : '?') . GeneralUtility::implodeArrayForUrl('', ['P' => $params]);

                    $onlyIfSelectedJS = '';
                    if (isset($wizardConfiguration['popup_onlyOpenIfSelected']) && $wizardConfiguration['popup_onlyOpenIfSelected']) {
                        $notSelectedText = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:mess.noSelItemForEdit');
                        $onlyIfSelectedJS =
                            'if (!TBE_EDITOR.curSelected(' . GeneralUtility::quoteJSvalue($itemName) . ')){' .
                                'alert(' . GeneralUtility::quoteJSvalue($notSelectedText) . ');' .
                                'return false;' .
                            '}';
                    }
                    $aOnClick =
                        'this.blur();' .
                        $onlyIfSelectedJS .
                        'vHWin=window.open(' . GeneralUtility::quoteJSvalue($url) . '+\'&P[currentValue]=\'+TBE_EDITOR.rawurlencode(' .
                                'document.editform[' . GeneralUtility::quoteJSvalue($itemName) . '].value,300' .
                            ')' .
                            '+\'&P[currentSelectedValues]=\'+TBE_EDITOR.curSelected(' . GeneralUtility::quoteJSvalue($itemName) . '),' .
                            GeneralUtility::quoteJSvalue('popUp' . $md5ID) . ',' .
                            GeneralUtility::quoteJSvalue($wizardConfiguration['JSopenParams']) .
                        ');' .
                        'vHWin.focus();' .
                        'return false;';

                    $buttonWizards[] =
                        '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($aOnClick) . '">' .
                            $icon .
                        '</a>';
                    break;

                case 'colorbox':
                    $params = [];
                    $params['params'] = $wizardConfiguration['params'];
                    $params['exampleImg'] = $wizardConfiguration['exampleImg'];
                    $params['table'] = $table;
                    $params['uid'] = $row['uid'];
                    $params['pid'] = $row['pid'];
                    $params['field'] = $field;
                    $params['flexFormPath'] = $flexFormPath;
                    $params['md5ID'] = $md5ID;
                    $params['returnUrl'] = $this->data['returnUrl'];

                    $params['formName'] = 'editform';
                    $params['itemName'] = $itemName;
                    $params['hmac'] = GeneralUtility::hmac($params['formName'] . $params['itemName'], 'wizard_js');
                    $params['fieldChangeFunc'] = $fieldChangeFunc;
                    $params['fieldChangeFuncHash'] = GeneralUtility::hmac(serialize($fieldChangeFunc));

                    // Resolving script filename and setting URL.
                    $urlParameters = [];
                    if (isset($wizardConfiguration['module']['urlParameters']) && is_array($wizardConfiguration['module']['urlParameters'])) {
                        $urlParameters = $wizardConfiguration['module']['urlParameters'];
                    }
                    $wScript = BackendUtility::getModuleUrl($wizardConfiguration['module']['name'], $urlParameters, '');
                    $url = $wScript . (strstr($wScript, '?') ? '' : '?') . GeneralUtility::implodeArrayForUrl('', ['P' => $params]);

                    $aOnClick =
                        'this.blur();' .
                        'vHWin=window.open(' . GeneralUtility::quoteJSvalue($url) . '+\'&P[currentValue]=\'+TBE_EDITOR.rawurlencode(' .
                            'document.editform[' . GeneralUtility::quoteJSvalue($itemName) . '].value,300' .
                            ')' .
                            '+\'&P[currentSelectedValues]=\'+TBE_EDITOR.curSelected(' . GeneralUtility::quoteJSvalue($itemName) . '),' .
                            GeneralUtility::quoteJSvalue('popUp' . $md5ID) . ',' .
                            GeneralUtility::quoteJSvalue($wizardConfiguration['JSopenParams']) .
                        ');' .
                        'vHWin.focus();' .
                        'return false;';

                    $otherWizards[] = '<a id="' . $md5ID . '" class="btn btn-default" href="#" onclick="' . htmlspecialchars($aOnClick) . '"><span class="t3-icon fa fa-eyedropper"></span></a>';
                    break;
                case 'slider':
                    $params = [];
                    $params['fieldConfig'] = $PA['fieldConf']['config'];
                    $params['field'] = $field;
                    $params['table'] = $table;
                    $params['flexFormPath'] = $flexFormPath;
                    $params['md5ID'] = $md5ID;
                    $params['itemName'] = $itemName;
                    $params['wConf'] = $wizardConfiguration;
                    $params['row'] = $row;

                    /** @var ValueSliderWizard $wizard */
                    $wizard = GeneralUtility::makeInstance(ValueSliderWizard::class);
                    $otherWizards[] = $wizard->renderWizard($params);
                    break;

                case 'select':
                    // The select wizard is a select drop down added to the main element. It provides all the functionality
                    // that select items can do for us, so we process this element via data processing.
                    // @todo: This should be embedded in an own provider called in the main data group to not handle this on the fly here

                    // Select wizard page TS can be set in TCEFORM."table"."field".wizards."wizardName"
                    $pageTsConfig = [];
                    if (isset($this->data['pageTsConfig']['TCEFORM.'][$table . '.'][$field . '.']['wizards.'][$wizardIdentifier . '.'])
                        && is_array($this->data['pageTsConfig']['TCEFORM.'][$table . '.'][$field . '.']['wizards.'][$wizardIdentifier . '.'])
                    ) {
                        $pageTsConfig['TCEFORM.']['dummySelectWizard.'][$wizardIdentifier . '.'] = $this->data['pageTsConfig']['TCEFORM.'][$table . '.'][$field . '.']['wizards.'][$wizardIdentifier . '.'];
                    }
                    $selectWizardDataInput = [
                        'tableName' => 'dummySelectWizard',
                        'command' => 'edit',
                        'pageTsConfig' => $pageTsConfig,
                        'processedTca' => [
                            'ctrl' => [],
                            'columns' => [
                                $wizardIdentifier => [
                                    'type' => 'select',
                                    'renderType' => 'selectSingle',
                                    'config' => $wizardConfiguration,
                                ],
                            ],
                        ],
                    ];
                    /** @var OnTheFly $formDataGroup */
                    $formDataGroup = GeneralUtility::makeInstance(OnTheFly::class);
                    $formDataGroup->setProviderList([ TcaSelectItems::class ]);
                    /** @var FormDataCompiler $formDataCompiler */
                    $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
                    $compilerResult = $formDataCompiler->compile($selectWizardDataInput);
                    $selectWizardItems = $compilerResult['processedTca']['columns'][$wizardIdentifier]['config']['items'];

                    $options = [];
                    $options[] = '<option>' . $iTitle . '</option>';
                    foreach ($selectWizardItems as $selectWizardItem) {
                        $options[] = '<option value="' . htmlspecialchars($selectWizardItem[1]) . '">' . htmlspecialchars($selectWizardItem[0]) . '</option>';
                    }
                    if ($wizardConfiguration['mode'] == 'append') {
                        $assignValue = 'document.querySelectorAll(' . GeneralUtility::quoteJSvalue('[data-formengine-input-name="' . $itemName . '"]') . ')[0].value+=\'\'+this.options[this.selectedIndex].value';
                    } elseif ($wizardConfiguration['mode'] == 'prepend') {
                        $assignValue = 'document.querySelectorAll(' . GeneralUtility::quoteJSvalue('[data-formengine-input-name="' . $itemName . '"]') . ')[0].value=\'\'+this.options[this.selectedIndex].value+document.editform[' . GeneralUtility::quoteJSvalue($itemName) . '].value';
                    } else {
                        $assignValue = 'document.querySelectorAll(' . GeneralUtility::quoteJSvalue('[data-formengine-input-name="' . $itemName . '"]') . ')[0].value=this.options[this.selectedIndex].value';
                    }
                    $otherWizards[] =
                        '<select' .
                            ' id="' . StringUtility::getUniqueId('tceforms-select-') . '"' .
                            ' class="form-control tceforms-select tceforms-wizardselect"' .
                            ' onchange="' . htmlspecialchars($assignValue . ';this.blur();this.selectedIndex=0;' . implode('', $fieldChangeFunc)) . '"' .
                        '>' .
                            implode('', $options) .
                        '</select>';
                    break;
                case 'suggest':
                    if (!empty($PA['fieldTSConfig']['suggest.']['default.']['hide'])) {
                        break;
                    }
                    /** @var SuggestWizard $suggestWizard */
                    $suggestWizard = GeneralUtility::makeInstance(SuggestWizard::class);
                    $otherWizards[] = $suggestWizard->renderSuggestSelector($PA['itemFormElName'], $table, $field, $row, $PA);
                    break;
            }
        }

        // For each rendered wizard, put them together around the item.
        if (!empty($buttonWizards) || !empty($otherWizards)) {
            $innerContent = '';
            if (!empty($buttonWizards)) {
                $innerContent .= '<div class="btn-group' . ($wizConf['_VERTICAL'] ? ' btn-group-vertical' : '') . '">' . implode('', $buttonWizards) . '</div>';
            }
            $innerContent .= implode(' ', $otherWizards);

            // Position
            $classes = ['form-wizards-wrap'];
            if ($wizConf['_POSITION'] === 'left') {
                $classes[] = 'form-wizards-aside';
                $innerContent = '<div class="form-wizards-items">' . $innerContent . '</div><div class="form-wizards-element">' . $item . '</div>';
            } elseif ($wizConf['_POSITION'] === 'top') {
                $classes[] = 'form-wizards-top';
                $innerContent = '<div class="form-wizards-items">' . $innerContent . '</div><div class="form-wizards-element">' . $item . '</div>';
            } elseif ($wizConf['_POSITION'] === 'bottom') {
                $classes[] = 'form-wizards-bottom';
                $innerContent = '<div class="form-wizards-element">' . $item . '</div><div class="form-wizards-items">' . $innerContent . '</div>';
            } else {
                $classes[] = 'form-wizards-aside';
                $innerContent = '<div class="form-wizards-element">' . $item . '</div><div class="form-wizards-items">' . $innerContent . '</div>';
            }
            $item = '
				<div class="' . implode(' ', $classes) . '">
					' . $innerContent . '
				</div>';
        }

        return $item;
    }

    /**
     * Prints the selector box form-field for the db/file/select elements (multiple)
     *
     * @param string $fName Form element name
     * @param string $mode Mode "db", "file" (internal_type for the "group" type) OR blank (then for the "select" type)
     * @param string $allowed Commalist of "allowed
     * @param array $itemArray The array of items. For "select" and "group"/"file" this is just a set of value. For "db" its an array of arrays with table/uid pairs.
     * @param string $selector Alternative selector box.
     * @param array $params An array of additional parameters, eg: "size", "info", "headers" (array with "selector" and "items"), "noBrowser", "thumbnails
     * @param string $onFocus On focus attribute string
     * @param string $table (optional) Table name processing for
     * @param string $field (optional) Field of table name processing for
     * @param string $uid (optional) uid of table record processing for
     * @param array $config (optional) The TCA field config
     * @return string The form fields for the selection.
     * @throws \UnexpectedValueException
     * @todo: Hack this mess into pieces and inline to group / select element depending on what they need
     */
    protected function dbFileIcons($fName, $mode, $allowed, $itemArray, $selector = '', $params = [], $onFocus = '', $table = '', $field = '', $uid = '', $config = [])
    {
        $languageService = $this->getLanguageService();
        $disabled = '';
        if ($params['readOnly']) {
            $disabled = ' disabled="disabled"';
        }
        // INIT
        $uidList = [];
        $opt = [];
        $itemArrayC = 0;
        // Creating <option> elements:
        if (is_array($itemArray)) {
            $itemArrayC = count($itemArray);
            switch ($mode) {
                case 'db':
                    foreach ($itemArray as $pp) {
                        $pRec = BackendUtility::getRecordWSOL($pp['table'], $pp['id']);
                        if (is_array($pRec)) {
                            $pTitle = BackendUtility::getRecordTitle($pp['table'], $pRec, false, true);
                            $pUid = $pp['table'] . '_' . $pp['id'];
                            $uidList[] = $pUid;
                            $title = htmlspecialchars($pTitle);
                            $opt[] = '<option value="' . htmlspecialchars($pUid) . '" title="' . $title . '">' . $title . '</option>';
                        }
                    }
                    break;
                case 'file_reference':

                case 'file':
                    foreach ($itemArray as $item) {
                        $itemParts = explode('|', $item);
                        $uidList[] = ($pUid = ($pTitle = $itemParts[0]));
                        $title = htmlspecialchars(rawurldecode($itemParts[1]));
                        $opt[] = '<option value="' . htmlspecialchars(rawurldecode($itemParts[0])) . '" title="' . $title . '">' . $title . '</option>';
                    }
                    break;
                case 'folder':
                    foreach ($itemArray as $pp) {
                        $pParts = explode('|', $pp);
                        $uidList[] = ($pUid = ($pTitle = $pParts[0]));
                        $title = htmlspecialchars(rawurldecode($pParts[0]));
                        $opt[] = '<option value="' . htmlspecialchars(rawurldecode($pParts[0])) . '" title="' . $title . '">' . $title . '</option>';
                    }
                    break;
                default:
                    foreach ($itemArray as $pp) {
                        $pParts = explode('|', $pp, 2);
                        $uidList[] = ($pUid = $pParts[0]);
                        $pTitle = $pParts[1];
                        $title = htmlspecialchars(rawurldecode($pTitle));
                        $opt[] = '<option value="' . htmlspecialchars(rawurldecode($pUid)) . '" title="' . $title . '">' . $title . '</option>';
                    }
            }
        }
        // Create selector box of the options
        $sSize = $params['autoSizeMax']
            ? MathUtility::forceIntegerInRange($itemArrayC + 1, MathUtility::forceIntegerInRange($params['size'], 1), $params['autoSizeMax'])
            : $params['size'];
        if (!$selector) {
            $maxItems = isset($params['maxitems']) ? (int)$params['maxitems'] : 0;
            $size = isset($params['size']) ? (int)$params['size'] : 0;
            $classes = ['form-control', 'tceforms-multiselect'];
            if ($maxItems === 1) {
                $classes[] = 'form-select-no-siblings';
            }
            $isMultiple = $maxItems !== 1 && $size !== 1;
            $selector = '<select id="' . StringUtility::getUniqueId('tceforms-multiselect-') . '" '
                . ($params['noList'] ? 'style="display: none"' : 'size="' . $sSize . '" class="' . implode(' ', $classes) . '"')
                . ($isMultiple ? ' multiple="multiple"' : '')
                . ' data-formengine-input-name="' . htmlspecialchars($fName) . '" ' . $this->getValidationDataAsDataAttribute($config) . $onFocus . $params['style'] . $disabled . '>' . implode('', $opt)
                . '</select>';
        }
        $icons = [
            'L' => [],
            'R' => []
        ];
        $rOnClickInline = '';
        if (!$params['readOnly'] && !$params['noList']) {
            if (!$params['noBrowser']) {
                // Check against inline uniqueness
                /** @var InlineStackProcessor $inlineStackProcessor */
                $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
                $inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);
                $aOnClickInline = '';
                if ($this->data['isInlineChild'] && $this->data['inlineParentUid']) {
                    if ($this->data['inlineParentConfig']['foreign_table'] === $table
                        && $this->data['inlineParentConfig']['foreign_unique'] === $field
                    ) {
                        $objectPrefix = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']) . '-' . $table;
                        $aOnClickInline = $objectPrefix . '|inline.checkUniqueElement|inline.setUniqueElement';
                        $rOnClickInline = 'inline.revertUnique(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',null,' . GeneralUtility::quoteJSvalue($uid) . ');';
                    }
                }
                if (is_array($config['appearance']) && isset($config['appearance']['elementBrowserType'])) {
                    $elementBrowserType = $config['appearance']['elementBrowserType'];
                } else {
                    $elementBrowserType = $mode;
                }
                if (is_array($config['appearance']) && isset($config['appearance']['elementBrowserAllowed'])) {
                    $elementBrowserAllowed = $config['appearance']['elementBrowserAllowed'];
                } else {
                    $elementBrowserAllowed = $allowed;
                }
                $aOnClick = 'setFormValueOpenBrowser(' . GeneralUtility::quoteJSvalue($elementBrowserType) . ','
                    . GeneralUtility::quoteJSvalue(($fName . '|||' . $elementBrowserAllowed . '|' . $aOnClickInline)) . '); return false;';
                $icons['R'][] = '
					<a href="#"
						onclick="' . htmlspecialchars($aOnClick) . '"
						class="btn btn-default"
						title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.browse_' . ($mode == 'db' ? 'db' : 'file'))) . '">
						' . $this->iconFactory->getIcon('actions-insert-record', Icon::SIZE_SMALL)->render() . '
					</a>';
            }
            if (!$params['dontShowMoveIcons']) {
                if ($sSize >= 5) {
                    $icons['L'][] = '
						<a href="#"
							class="btn btn-default t3js-btn-moveoption-top"
							data-fieldname="' . $fName . '"
							title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.move_to_top')) . '">
							' . $this->iconFactory->getIcon('actions-move-to-top', Icon::SIZE_SMALL)->render() . '
						</a>';
                }
                $icons['L'][] = '
					<a href="#"
						class="btn btn-default t3js-btn-moveoption-up"
						data-fieldname="' . $fName . '"
						title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.move_up')) . '">
						' . $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render() . '
					</a>';
                $icons['L'][] = '
					<a href="#"
						class="btn btn-default t3js-btn-moveoption-down"
						data-fieldname="' . $fName . '"
						title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.move_down')) . '">
						' . $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render() . '
					</a>';
                if ($sSize >= 5) {
                    $icons['L'][] = '
						<a href="#"
							class="btn btn-default t3js-btn-moveoption-bottom"
							data-fieldname="' . $fName . '"
							title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.move_to_bottom')) . '">
							' . $this->iconFactory->getIcon('actions-move-to-bottom', Icon::SIZE_SMALL)->render() . '
						</a>';
                }
            }
            $clipElements = $this->getClipboardElements($allowed, $mode);
            if (!empty($clipElements)) {
                $aOnClick = '';
                foreach ($clipElements as $elValue) {
                    if ($mode == 'db') {
                        list($itemTable, $itemUid) = explode('|', $elValue);
                        $recordTitle = BackendUtility::getRecordTitle($itemTable, BackendUtility::getRecordWSOL($itemTable, $itemUid));
                        $itemTitle = GeneralUtility::quoteJSvalue($recordTitle);
                        $elValue = $itemTable . '_' . $itemUid;
                    } else {
                        // 'file', 'file_reference' and 'folder' mode
                        $itemTitle = 'unescape(' . GeneralUtility::quoteJSvalue(rawurlencode(basename($elValue))) . ')';
                    }
                    $aOnClick .= 'setFormValueFromBrowseWin(' . GeneralUtility::quoteJSvalue($fName) . ',unescape('
                        . GeneralUtility::quoteJSvalue(rawurlencode(str_replace('%20', ' ', $elValue))) . '),' . $itemTitle . ',' . $itemTitle . ');';
                }
                $aOnClick .= 'return false;';
                $icons['R'][] = '
					<a href="#"
						class="btn btn-default"
						onclick="' . htmlspecialchars($aOnClick) . '"
						title="' . htmlspecialchars(sprintf($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.clipInsert_' . ($mode == 'db' ? 'db' : 'file')), count($clipElements))) . '">
						' . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render() . '
					</a>';
            }
        }
        if (!$params['readOnly'] && !$params['noDelete']) {
            $icons['L'][] = '
				<a href="#"
					class="btn btn-default t3js-btn-removeoption"
					onClick="' . $rOnClickInline . '"
					data-fieldname="' . $fName . '"
					title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.remove_selected')) . '">
					' . $this->iconFactory->getIcon('actions-selection-delete', Icon::SIZE_SMALL)->render() . '
				</a>';
        }

        // Thumbnails
        $imagesOnly = false;
        if ($params['thumbnails'] && $params['allowed']) {
            // In case we have thumbnails, check if only images are allowed.
            // In this case, render them below the field, instead of to the right
            $allowedExtensionList = $params['allowed'];
            $imageExtensionList = GeneralUtility::trimExplode(',', strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']), true);
            $imagesOnly = true;
            foreach ($allowedExtensionList as $allowedExtension) {
                if (!ArrayUtility::inArray($imageExtensionList, $allowedExtension)) {
                    $imagesOnly = false;
                    break;
                }
            }
        }
        $thumbnails = '';
        if (is_array($params['thumbnails']) && !empty($params['thumbnails'])) {
            if ($imagesOnly) {
                $thumbnails .= '<ul class="list-inline">';
                foreach ($params['thumbnails'] as $thumbnail) {
                    $thumbnails .= '<li><span class="thumbnail">' . $thumbnail['image'] . '</span></li>';
                }
                $thumbnails .= '</ul>';
            } else {
                $thumbnails .= '<div class="table-fit"><table class="table table-white"><tbody>';
                foreach ($params['thumbnails'] as $thumbnail) {
                    $thumbnails .= '
						<tr>
							<td class="col-icon">
								' . ($config['internal_type'] === 'db'
                            ? BackendUtility::wrapClickMenuOnIcon($thumbnail['image'], $thumbnail['table'], $thumbnail['uid'], 1, '', '+copy,info,edit,view')
                            : $thumbnail['image']) . '
							</td>
							<td class="col-title">
								' . ($config['internal_type'] === 'db'
                            ? BackendUtility::wrapClickMenuOnIcon($thumbnail['name'], $thumbnail['table'], $thumbnail['uid'], 1, '', '+copy,info,edit,view')
                            : $thumbnail['name']) . '
								' . ($config['internal_type'] === 'db' ? ' <span class="text-muted">[' . $thumbnail['uid'] . ']</span>' : '') . '
							</td>
						</tr>
						';
                }
                $thumbnails .= '</tbody></table></div>';
            }
        }

        // Allowed Tables
        $allowedTables = '';
        if (is_array($params['allowedTables']) && !empty($params['allowedTables'])) {
            $allowedTables .= '<div class="help-block">';
            foreach ($params['allowedTables'] as $key => $item) {
                if (is_array($item)) {
                    if (empty($params['readOnly'])) {
                        $allowedTables .= '<a href="#" onClick="' . htmlspecialchars($item['onClick']) . '" class="btn btn-default">' . $item['icon'] . ' ' . htmlspecialchars($item['name']) . '</a> ';
                    } else {
                        $allowedTables .= '<span>' . htmlspecialchars($item['name']) . '</span> ';
                    }
                } elseif ($key === 'name') {
                    $allowedTables .= '<span>' . htmlspecialchars($item) . '</span> ';
                }
            }
            $allowedTables .= '</div>';
        }
        // Allowed
        $allowedList = '';
        if (is_array($params['allowed']) && !empty($params['allowed'])) {
            foreach ($params['allowed'] as $item) {
                $allowedList .= '<span class="label label-success">' . strtoupper($item) . '</span> ';
            }
        }
        // Disallowed
        $disallowedList = '';
        if (is_array($params['disallowed']) && !empty($params['disallowed'])) {
            foreach ($params['disallowed'] as $item) {
                $disallowedList .= '<span class="label label-danger">' . strtoupper($item) . '</span> ';
            }
        }
        // Rightbox
        $rightbox = ($params['rightbox'] ?: '');

        // Hook: dbFileIcons_postProcess (requested by FAL-team for use with the "fal" extension)
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['dbFileIcons'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['dbFileIcons'] as $classRef) {
                $hookObject = GeneralUtility::getUserObj($classRef);
                if (!$hookObject instanceof DatabaseFileIconsHookInterface) {
                    throw new \UnexpectedValueException($classRef . ' must implement interface ' . DatabaseFileIconsHookInterface::class, 1290167704);
                }
                $additionalParams = [
                    'mode' => $mode,
                    'allowed' => $allowed,
                    'itemArray' => $itemArray,
                    'onFocus' => $onFocus,
                    'table' => $table,
                    'field' => $field,
                    'uid' => $uid,
                    'config' => $GLOBALS['TCA'][$table]['columns'][$field]
                ];
                $hookObject->dbFileIcons_postProcess($params, $selector, $thumbnails, $icons, $rightbox, $fName, $uidList, $additionalParams, $this);
            }
        }

        // Output
        $str = '
			' . ($params['headers']['selector'] ? '<label>' . $params['headers']['selector'] . '</label>' : '') . '
			<div class="form-wizards-wrap form-wizards-aside">
				<div class="form-wizards-element">
					' . $selector . '
					' . (!$params['noList'] && !empty($allowedTables) ? $allowedTables : '') . '
					' . (!$params['noList'] && (!empty($allowedList) || !empty($disallowedList))
                ? '<div class="help-block">' . $allowedList . $disallowedList . ' </div>'
                : '') . '
				</div>
				' . (!empty($icons['L']) ? '<div class="form-wizards-items"><div class="btn-group-vertical">' . implode('', $icons['L']) . '</div></div>' : '') . '
				' . (!empty($icons['R']) ? '<div class="form-wizards-items"><div class="btn-group-vertical">' . implode('', $icons['R']) . '</div></div>' : '') . '
			</div>
			';
        if ($rightbox) {
            $str = '
				<div class="form-multigroup-wrap t3js-formengine-field-group">
					<div class="form-multigroup-item form-multigroup-element">' . $str . '</div>
					<div class="form-multigroup-item form-multigroup-element">
						' . ($params['headers']['items'] ? '<label>' . $params['headers']['items'] . '</label>' : '') . '
						' . ($params['headers']['selectorbox'] ? '<div class="form-multigroup-item-wizard">' . $params['headers']['selectorbox'] . '</div>' : '') . '
						' . $rightbox . '
					</div>
				</div>
				';
        }
        $str .= $thumbnails;

        // Creating the hidden field which contains the actual value as a comma list.
        $str .= '<input type="hidden" name="' . $fName . '" value="' . htmlspecialchars(implode(',', $uidList)) . '" />';
        return $str;
    }

    /**
     * Returns array of elements from clipboard to insert into GROUP element box.
     *
     * @param string $allowed Allowed elements, Eg "pages,tt_content", "gif,jpg,jpeg,png
     * @param string $mode Mode of relations: "db" or "file
     * @return array Array of elements in values (keys are insignificant), if none found, empty array.
     */
    protected function getClipboardElements($allowed, $mode)
    {
        if (!is_object($this->clipboard)) {
            $this->clipboard = GeneralUtility::makeInstance(Clipboard::class);
            $this->clipboard->initializeClipboard();
        }

        $output = [];
        switch ($mode) {
            case 'file_reference':

            case 'file':
                $elFromTable = $this->clipboard->elFromTable('_FILE');
                $allowedExts = GeneralUtility::trimExplode(',', $allowed, true);
                // If there are a set of allowed extensions, filter the content:
                if ($allowedExts) {
                    foreach ($elFromTable as $elValue) {
                        $pI = pathinfo($elValue);
                        $ext = strtolower($pI['extension']);
                        if (in_array($ext, $allowedExts)) {
                            $output[] = $elValue;
                        }
                    }
                } else {
                    // If all is allowed, insert all: (This does NOT respect any disallowed extensions,
                    // but those will be filtered away by the backend TCEmain)
                    $output = $elFromTable;
                }
                break;
            case 'db':
                $allowedTables = GeneralUtility::trimExplode(',', $allowed, true);
                // All tables allowed for relation:
                if (trim($allowedTables[0]) === '*') {
                    $output = $this->clipboard->elFromTable('');
                } else {
                    // Only some tables, filter them:
                    foreach ($allowedTables as $tablename) {
                        $elFromTable = $this->clipboard->elFromTable($tablename);
                        $output = array_merge($output, $elFromTable);
                    }
                }
                $output = array_keys($output);
                break;
        }

        return $output;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
