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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\OnTheFly;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Form\Wizard\SuggestWizard;
use TYPO3\CMS\Backend\Form\Wizard\ValueSliderWizard;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
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
     * @var NodeFactory
     */
    protected $nodeFactory;

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
        $md5ID = 'ID' . GeneralUtility::shortMD5($itemName);
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
                        $notSelectedText = $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.noSelItemForEdit');
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
                        $assignValue = 'document.querySelectorAll(' . GeneralUtility::quoteJSvalue('[data-formengine-input-name="' . $itemName . '"]') . ')[0].value=\'\'+this.options[this.selectedIndex].value+document.editform[' . GeneralUtility::quoteJSvalue($itemName) . '].value';
                    } elseif ($wizardConfiguration['mode'] == 'prepend') {
                        $assignValue = 'document.querySelectorAll(' . GeneralUtility::quoteJSvalue('[data-formengine-input-name="' . $itemName . '"]') . ')[0].value+=\'\'+this.options[this.selectedIndex].value';
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
                    $suggestWizard = GeneralUtility::makeInstance(SuggestWizard::class);
                    $otherWizards[] = $suggestWizard->renderSuggestSelector($this->data);
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
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
