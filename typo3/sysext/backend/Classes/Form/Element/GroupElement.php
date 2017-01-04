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
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Generation of TCEform elements of the type "group"
 */
class GroupElement extends AbstractFormElement
{
    /**
     * @var Clipboard
     */
    protected $clipboard;

    /**
     * This will render a selector box into which elements from either
     * the file system or database can be inserted. Relations.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @throws \RuntimeException
     */
    public function render()
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();

        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $elementName = $parameterArray['itemFormElName'];

        $resultArray = $this->initializeResultArray();

        $selectedItems = $parameterArray['itemFormElValue'];
        $selectedItemsCount = count($selectedItems);

        $maxItems = $config['maxitems'];
        $autoSizeMax = MathUtility::forceIntegerInRange($config['autoSizeMax'], 0);
        $size = 5;
        if (isset($config['size'])) {
            $size = (int)$config['size'];
        }
        if ($autoSizeMax >= 1) {
            $size = MathUtility::forceIntegerInRange($selectedItemsCount + 1, MathUtility::forceIntegerInRange($size, 1), $autoSizeMax);
        }

        $isDisabled = false;
        if (isset($config['readOnly']) && $config['readOnly']) {
            $isDisabled = true;
        }
        $showMoveIcons = true;
        if (isset($config['hideMoveIcons']) && $config['hideMoveIcons']) {
            $showMoveIcons = false;
        }

        $internalType = (string)$config['internal_type'];
        $showThumbs = (bool)$config['show_thumbs'];
        $allowed = GeneralUtility::trimExplode(',', $config['allowed'], true);
        $disallowed = GeneralUtility::trimExplode(',', $config['disallowed'], true);
        $uploadFieldId = $parameterArray['itemFormElID'] . '_files';
        $itemCanBeSelectedMoreThanOnce = !empty($config['multiple']);
        $maxTitleLength = $backendUser->uc['titleLen'];
        $isDirectFileUploadEnabled = (bool)$backendUser->uc['edit_docModuleUpload'];
        $clipboardElements = $config['clipboardElements'];

        $disableControls = [];
        if (isset($config['disable_controls'])) {
            $disableControls = GeneralUtility::trimExplode(',', $config['disable_controls'], true);
        }
        $showListControl = true;
        if (in_array('list', $disableControls, true)) {
            $showListControl = false;
        }
        $showDeleteControl = true;
        if (in_array('delete', $disableControls, true)) {
            $showDeleteControl = false;
        }
        $showBrowseControl = true;
        if (in_array('browser', $disableControls, true)) {
            $showBrowseControl = false;
        }
        $showAllowedTables = true;
        if (in_array('allowedTables', $disableControls, true)) {
            $showAllowedTables = false;
        }
        $showUploadField = true;
        if (in_array('upload', $disableControls, true)) {
            $showUploadField = false;
        }

        if ($maxItems === 1) {
            // If maxitems==1 then automatically replace the current item (in list and file selector)
            $resultArray['additionalJavaScriptPost'][] =
                'TBE_EDITOR.clearBeforeSettingFormValueFromBrowseWin[' . GeneralUtility::quoteJSvalue($elementName) . '] = {'
                    . 'itemFormElID_file: ' . GeneralUtility::quoteJSvalue($uploadFieldId)
                . '}';
            $parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] =
                'setFormValueManipulate(' . GeneralUtility::quoteJSvalue($elementName) . ', \'Remove\');'
                . $parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'];
        } elseif (!$showListControl) {
            // If the list controls have been removed and the maximum number is reached, remove the first entry to avoid "write once" field
            $parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] =
                'setFormValueManipulate(' . GeneralUtility::quoteJSvalue($elementName) . ', \'RemoveFirstIfFull\', ' . $maxItems . ');'
                . $parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'];
        }

        $listOfSelectedValues = [];
        $thumbnailsHtml = [];
        $recordsOverviewHtml = [];
        $selectorOptionsHtml = [];
        $clipboardOnClick = [];
        if ($internalType === 'file_reference' || $internalType === 'file') {
            $fileFactory = ResourceFactory::getInstance();
            foreach ($selectedItems as $selectedItem) {
                $uidOrPath = $selectedItem['uidOrPath'];
                $listOfSelectedValues[] = $uidOrPath;
                $title = $selectedItem['title'];
                $shortenedTitle = GeneralUtility::fixed_lgd_cs($title, $maxTitleLength);
                $selectorOptionsHtml[] =
                    '<option value="' . htmlspecialchars($uidOrPath) . '" title="' . htmlspecialchars($title) . '">'
                        . htmlspecialchars($shortenedTitle)
                    . '</option>';
                if ($showThumbs) {
                    if (MathUtility::canBeInterpretedAsInteger($uidOrPath)) {
                        $fileObject = $fileFactory->getFileObject($uidOrPath);
                        if (!$fileObject->isMissing()) {
                            $extension = $fileObject->getExtension();
                            if (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                                $extension)
                            ) {
                                $thumbnailsHtml[] =
                                    '<li>'
                                        . '<span class="thumbnail">'
                                            . $fileObject->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, [])->getPublicUrl(true)
                                        . '</span>'
                                    . '</li>';
                            }
                        }
                    } else {
                        $rowCopy = [];
                        $rowCopy[$fieldName] = $uidOrPath;
                        try {
                            $icon = BackendUtility::thumbCode(
                                $rowCopy,
                                $table,
                                $fieldName,
                                '',
                                '',
                                $config['uploadfolder'],
                                0,
                                ' align="middle"'
                            );
                            $thumbnailsHtml[] =
                                '<li>'
                                    . '<span class="thumbnail">'
                                        . $icon
                                    . '</span>'
                                . '</li>';
                        } catch (\Exception $exception) {
                            $message = $exception->getMessage();
                            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '',
                                FlashMessage::ERROR, true);
                            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                            $defaultFlashMessageQueue->enqueue($flashMessage);
                            $logMessage = $message . ' (' . $table . ':' . $row['uid'] . ')';
                            GeneralUtility::sysLog($logMessage, 'core', GeneralUtility::SYSLOG_SEVERITY_WARNING);
                        }
                    }
                }
            }
            foreach ($clipboardElements as $clipboardElement) {
                $value = $clipboardElement['value'];
                $title = 'unescape(' . GeneralUtility::quoteJSvalue(rawurlencode(basename($clipboardElement['title']))) . ')';
                $clipboardOnClick[] = 'setFormValueFromBrowseWin('
                        . GeneralUtility::quoteJSvalue($elementName) . ','
                        . 'unescape(' . GeneralUtility::quoteJSvalue(rawurlencode(str_replace('%20', ' ', $value))) . '),'
                        . $title . ','
                        . $title
                    . ');';
            }
        } elseif ($internalType === 'folder') {
            foreach ($selectedItems as $selectedItem) {
                $folder = $selectedItem['folder'];
                $listOfSelectedValues[] = $folder;
                $selectorOptionsHtml[] =
                    '<option value="' . htmlspecialchars($folder) . '" title="' . htmlspecialchars($folder) . '">'
                        . htmlspecialchars($folder)
                    . '</option>';
            }
        } else {
            // 'db'
            foreach ($selectedItems as $selectedItem) {
                $tableWithUid = $selectedItem['table'] . '_' . $selectedItem['uid'];
                $listOfSelectedValues[] = $tableWithUid;
                $title = $selectedItem['title'];
                if (empty($title)) {
                    $title = '[' . $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.no_title') . ']';
                }
                $shortenedTitle = GeneralUtility::fixed_lgd_cs($title, $maxTitleLength);
                $selectorOptionsHtml[] =
                    '<option value="' . htmlspecialchars($tableWithUid) . '" title="' . htmlspecialchars($title) . '">'
                        . htmlspecialchars($shortenedTitle)
                    . '</option>';
                if (!$isDisabled && $showThumbs) {
                    $linkedIcon = BackendUtility::wrapClickMenuOnIcon(
                        $this->iconFactory->getIconForRecord($selectedItem['table'], $selectedItem['row'], Icon::SIZE_SMALL)->render(),
                        $selectedItem['table'],
                        $selectedItem['uid'],
                        1,
                        '',
                        '+copy,info,edit,view'
                    );
                    $linkedTitle = BackendUtility::wrapClickMenuOnIcon(
                        $shortenedTitle,
                        $selectedItem['table'],
                        $selectedItem['uid'],
                        1,
                        '',
                        '+copy,info,edit,view'
                    );
                    $recordsOverviewHtml[] =
                        '<tr>'
                            . '<td class="col-icon">'
                                . $linkedIcon
                            . '</td>'
                            . '<td class="col-title">'
                                . $linkedTitle
                                . '<span class="text-muted">'
                                    . ' [' . $selectedItem['uid'] . ']'
                                . '</span>'
                            . '</td>'
                        . '</tr>';
                }
            }
            foreach ($clipboardElements as $clipboardElement) {
                $value = $clipboardElement['value'];
                $title = GeneralUtility::quoteJSvalue($clipboardElement['title']);
                $clipboardOnClick[] = 'setFormValueFromBrowseWin('
                    . GeneralUtility::quoteJSvalue($elementName) . ','
                    . 'unescape(' . GeneralUtility::quoteJSvalue(rawurlencode(str_replace('%20', ' ', $value))) . '),'
                    . $title . ','
                    . $title
                    . ');';
            }
        }

        // Check against inline uniqueness - Create some onclick js for delete control and element browser
        // to override record selection in some FAL scenarios - See 'appearance' docs of group element
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);
        $elementBrowserOnClickInline = '';
        $deleteControlOnClick = '';
        if ($this->data['isInlineChild']
            && $this->data['inlineParentUid']
            && $this->data['inlineParentConfig']['foreign_table'] === $table
            && $this->data['inlineParentConfig']['foreign_unique'] === $fieldName
        ) {
            $objectPrefix = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']) . '-' . $table;
            $elementBrowserOnClickInline = $objectPrefix . '|inline.checkUniqueElement|inline.setUniqueElement';
            $deleteControlOnClick = 'inline.revertUnique(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',null,' . GeneralUtility::quoteJSvalue($row['uid']) . ');';
        }
        $elementBrowserType = $internalType;
        if (is_array($config['appearance']) && isset($config['appearance']['elementBrowserType'])) {
            $elementBrowserType = $config['appearance']['elementBrowserType'];
        }
        $elementBrowserAllowed = implode(',', $allowed);
        if (is_array($config['appearance']) && isset($config['appearance']['elementBrowserAllowed'])) {
            $elementBrowserAllowed = $config['appearance']['elementBrowserAllowed'];
        }
        $elementBrowserOnClick = 'setFormValueOpenBrowser('
                . GeneralUtility::quoteJSvalue($elementBrowserType) . ','
                . GeneralUtility::quoteJSvalue($elementName . '|||' . $elementBrowserAllowed . '|' . $elementBrowserOnClickInline)
            . ');'
            . ' return false;';

        $allowedTablesHtml = [];
        if ($allowed[0] === '*') {
            $allowedTablesHtml[] =
                '<span>'
                    . htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.allTables'))
                . '</span>';
        } else {
            foreach ($allowed as $tableName) {
                $label = $languageService->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']);
                if (!$isDisabled) {
                    $icon = $this->iconFactory->getIconForRecord($tableName, [], Icon::SIZE_SMALL);
                    $onClick = 'setFormValueOpenBrowser(\'db\', ' . GeneralUtility::quoteJSvalue($elementName . '|||' . $tableName) . '); return false;';
                    $allowedTablesHtml[] =
                        '<a href="#" onClick="' . htmlspecialchars($onClick) . '" class="btn btn-default">'
                            . $icon->render() . htmlspecialchars($label) . '</a> '
                        . '</a>';
                } else {
                    $allowedTablesHtml[] = '<span>' . htmlspecialchars($label) . '</span> ';
                }
            }
        }

        $allowedHtml = [];
        foreach ($allowed as $item) {
            $allowedHtml[] = '<span class="label label-success">' . htmlspecialchars(strtoupper($item)) . '</span> ';
        }

        $disallowedHtml = [];
        foreach ($disallowed as $item) {
            $disallowedHtml[] = '<span class="label label-danger">' . htmlspecialchars(strtoupper($item)) . '</span> ';
        }

        $selectorStyles = [];
        $selectorAttributes = [];
        $selectorAttributes[] = 'id="' . StringUtility::getUniqueId('tceforms-multiselect-') . '"';
        $selectorAttributes[] = 'data-formengine-input-name="' . htmlspecialchars($elementName) . '"';
        $selectorAttributes[] = $this->getValidationDataAsDataAttribute($config);
        if ($maxItems !== 1 && $size !== 1) {
            $selectorAttributes[] = 'multiple="multiple"';
        }
        if ($isDisabled) {
            $selectorAttributes[] = 'disabled="disabled"';
        }
        if ($showListControl) {
            $selectorClasses = [];
            $selectorClasses[] = 'form-control';
            $selectorClasses[] = 'tceforms-multiselect';
            if ($maxItems === 1) {
                $selectorClasses[] = 'form-select-no-siblings';
            }
            $selectorAttributes[] = 'class="' . implode(' ', $selectorClasses) . '"';
            $selectorAttributes[] = 'size="' . $size . '"';
        } else {
            $selectorStyles[] = 'display: none';
        }
        if (isset($config['selectedListStyle'])) {
            $selectorStyles[] = $config['selectedListStyle'];
        }
        $selectorAttributes[] = 'style="' . implode(';', $selectorStyles) . '"';

        $html = [];
        $html[] = '<input type="hidden" class="t3js-group-hidden-field" data-formengine-input-name="' . htmlspecialchars($elementName) . '" value="' . $itemCanBeSelectedMoreThanOnce . '" />';
        $html[] = '<div class="form-wizards-wrap form-wizards-aside">';
        $html[] =   '<div class="form-wizards-element">';
        $html[] =       '<select ' . implode(' ', $selectorAttributes) . '>';
        $html[] =           implode(LF, $selectorOptionsHtml);
        $html[] =       '</select>';
        if ($showListControl && $showAllowedTables && $internalType === 'db' && !empty($allowedTablesHtml)) {
            $html[] =       '<div class="help-block">';
            $html[] =           implode(LF, $allowedTablesHtml);
            $html[] =       '</div>';
        }
        if ($showListControl && $internalType === 'file' && (!empty($allowedHtml) || !empty($disallowedHtml)) && !$isDisabled) {
            $html[] =       '<div class="help-block">';
            $html[] =           implode(LF, $allowedHtml);
            $html[] =           implode(LF, $disallowedHtml);
            $html[] =       '</div>';
        }
        $html[] =   '</div>';

        $html[] =   '<div class="form-wizards-items">';
        $html[] =       '<div class="btn-group-vertical">';
        if ($maxItems > 1 && $size >=5 && !$isDisabled && $showMoveIcons) {
            $html[] =       '<a href="#"';
            $html[] =           ' class="btn btn-default t3js-btn-moveoption-top"';
            $html[] =           ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =           ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.move_to_top')) . '"';
            $html[] =       '>';
            $html[] =           $this->iconFactory->getIcon('actions-move-to-top', Icon::SIZE_SMALL)->render();
            $html[] =       '</a>';
        }
        if ($maxItems > 1 && !$isDisabled && $showMoveIcons) {
            $html[] =       '<a href="#"';
            $html[] =           ' class="btn btn-default t3js-btn-moveoption-up"';
            $html[] =           ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =           ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.move_up')) . '"';
            $html[] =       '>';
            $html[] =           $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render();
            $html[] =       '</a>';
            $html[] =       '<a href="#"';
            $html[] =           ' class="btn btn-default t3js-btn-moveoption-down"';
            $html[] =           ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =           ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.move_down')) . '"';
            $html[] =       '>';
            $html[] =           $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render();
            $html[] =       '</a>';
        }
        if ($maxItems > 1 && $size >=5 && !$isDisabled && $showMoveIcons) {
            $html[] =       '<a href="#"';
            $html[] =           ' class="btn btn-default t3js-btn-moveoption-bottom"';
            $html[] =           ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =           ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.move_to_bottom')) . '"';
            $html[] =       '>';
            $html[] =           $this->iconFactory->getIcon('actions-move-to-bottom', Icon::SIZE_SMALL)->render();
            $html[] =       '</a>';
        }
        if ($showDeleteControl && !$isDisabled) {
            $html[] =       '<a href="#"';
            $html[] =           ' class="btn btn-default t3js-btn-removeoption"';
            $html[] =           ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =           ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.remove_selected')) . '"';
            $html[] =           ' onClick="' . $deleteControlOnClick . '"';
            $html[] =       '>';
            $html[] =           $this->iconFactory->getIcon('actions-selection-delete', Icon::SIZE_SMALL)->render();
            $html[] =       '</a>';
        }
        $html[] =       '</div>';
        $html[] =   '</div>';

        $html[] =   '<div class="form-wizards-items">';
        $html[] =       '<div class="btn-group-vertical">';
        if ($showListControl && $showBrowseControl && !$isDisabled) {
            if ($internalType === 'db') {
                $elementBrowserLabel = $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.browse_db');
            } else {
                $elementBrowserLabel = $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.browse_file');
            }
            $html[] =       '<a href="#"';
            $html[] =           ' onclick="' . htmlspecialchars($elementBrowserOnClick) . '"';
            $html[] =           ' class="btn btn-default"';
            $html[] =           ' title="' . htmlspecialchars($elementBrowserLabel) . '"';
            $html[] =       '>';
            $html[] =           $this->iconFactory->getIcon('actions-insert-record', Icon::SIZE_SMALL)->render();
            $html[] =       '</a>';
        }
        if ($showListControl && $showBrowseControl && !$isDisabled && !empty($clipboardElements)) {
            if ($internalType === 'db') {
                $clipboardLabel = sprintf($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.clipInsert_db'), count($clipboardElements));
            } else {
                $clipboardLabel = sprintf($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.clipInsert_file'), count($clipboardElements));
            }
            $html[] =       '<a href="#"';
            $html[] =           ' onclick="' . htmlspecialchars(implode(LF, $clipboardOnClick)) . ' return false;"';
            $html[] =           ' class="btn btn-default"';
            $html[] =           ' title="' . htmlspecialchars($clipboardLabel) . '"';
            $html[] =       '>';
            $html[] =           $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render();
            $html[] =       '</a>';
        }
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        if (!empty($thumbnailsHtml)) {
            $html[] = '<ul class="list-inline">';
            $html[] =   implode(LF, $thumbnailsHtml);
            $html[] = '</ul>';
        }
        if (!empty($recordsOverviewHtml)) {
            $html[] = '<div class="table-fit">';
            $html[] =   '<table class="table table-white">';
            $html[] =       '<tbody>';
            $html[] =           implode(LF, $recordsOverviewHtml);
            $html[] =       '</tbody>';
            $html[] =   '</table>';
            $html[] = '</div>';
        }

        if (!$isDisabled && $showUploadField) {
            // Adding the upload field
            if ($isDirectFileUploadEnabled && !empty($config['uploadfolder'])) {
                // Insert the multiple attribute to enable HTML5 multiple file upload
                $selectorMultipleAttribute = '';
                $multipleFilenameSuffix = '';
                if ($maxItems > 1) {
                    $selectorMultipleAttribute = ' multiple="multiple"';
                    $multipleFilenameSuffix = '[]';
                }
                $html[] = '<div id="' . $uploadFieldId . '">';
                $html[] =   '<input';
                $html[] =       ' type="file"';
                $html[] =       $selectorMultipleAttribute;
                $html[] =       ' name="data_files' . $this->data['elementBaseName'] . $multipleFilenameSuffix . '"';
                $html[] =       ' size="35"';
                $html[] =       ' onchange="' . implode('', $parameterArray['fieldChangeFunc']) . '"';
                $html[] =   '/>';
                $html[] = '</div>';
            }
        }

        $html[] = '<input type="hidden" name="' . htmlspecialchars($elementName) . '" value="' . htmlspecialchars(implode(',', $listOfSelectedValues)) . '" />';

        $html = implode(LF, $html);

        if (!$config['readOnly']) {
            $html = $this->renderWizards(
                [ $html ],
                $config['wizards'],
                $table,
                $row,
                $fieldName,
                $parameterArray,
                $elementName,
                BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras'])
            );
        }

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
