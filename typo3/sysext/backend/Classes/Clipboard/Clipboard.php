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

namespace TYPO3\CMS\Backend\Clipboard;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * TYPO3 clipboard for records and files
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
class Clipboard
{
    /**
     * Clipboard data kept here
     *
     * Keys:
     * 'normal'
     * 'tab_[x]' where x is >=1 and denotes the pad-number
     * 'mode'	:	'copy' means copy-mode, default = moving ('cut')
     * 'el'	:	Array of elements:
     * DB: keys = '[tablename]|[uid]'	eg. 'tt_content:123'
     * DB: values = 1 (basically insignificant)
     * FILE: keys = '_FILE|[md5 of path]'	eg. '_FILE|9ebc7e5c74'
     * FILE: values = The full filepath, eg. '/www/htdocs/typo3/32/dummy/fileadmin/sem1_3_examples/alternative_index.php'
     * or 'C:/www/htdocs/typo3/32/dummy/fileadmin/sem1_3_examples/alternative_index.php'
     *
     * 'current' pointer to current tab (among the above...)
     *
     * The virtual tablename '_FILE' will always indicate files/folders. When checking for elements from eg. 'all tables'
     * (by using an empty string) '_FILE' entries are excluded (so in effect only DB elements are counted)
     *
     * @var array
     */
    public array $clipData = [];

    public bool $changed = false;

    public string $current = '';

    public bool $lockToNormal = false;

    public int $numberOfPads = 3;

    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;
    protected ResourceFactory $resourceFactory;

    protected ?ServerRequestInterface $request = null;

    public function __construct(IconFactory $iconFactory, UriBuilder $uriBuilder, ResourceFactory $resourceFactory)
    {
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
        $this->resourceFactory = $resourceFactory;
    }

    /*****************************************
     *
     * Initialize
     *
     ****************************************/
    /**
     * Initialize the clipboard from the be_user session
     */
    public function initializeClipboard(?ServerRequestInterface $request = null): void
    {
        // Initialize the request
        // @todo: Clipboard does two things: It is a repository to find out which records
        //        are in the clipboard, and it is a class to help with rendering the
        //        clipboard. $request is optional and only used in rendering.
        //        It would be better to split these two aspects into single classes.
        $this->request = $request ?? $GLOBALS['TYPO3_REQUEST'] ?? null;

        $userTsConfig = $this->getBackendUser()->getTSConfig();
        // Get data
        $clipData = $this->getBackendUser()->getModuleData('clipboard', !empty($userTsConfig['options.']['saveClipboard'])  ? '' : 'ses') ?: [];
        $clipData += ['normal' => []];
        $this->numberOfPads = MathUtility::forceIntegerInRange((int)($userTsConfig['options.']['clipboardNumberPads'] ?? 3), 0, 20);
        // Resets/reinstates the clipboard pads
        $this->clipData['normal'] = is_array($clipData['normal']) ? $clipData['normal']: [];
        for ($a = 1; $a <= $this->numberOfPads; $a++) {
            $index = 'tab_' . $a;
            $this->clipData[$index] = is_iterable($clipData[$index] ?? null) ? $clipData[$index] : [];
        }
        // Setting the current pad pointer ($this->current))
        $current = (string)($clipData['current'] ?? '');
        $this->current = isset($this->clipData[$current]) ? $current : 'normal';
        $this->clipData['current'] = $this->current;
    }

    /**
     * Call this method after initialization if you want to lock the clipboard to operate on the normal pad only.
     * Trying to switch pad through ->setCmd will not work.
     * This is used by the clickmenu since it only allows operation on single elements at a time (that is the "normal" pad)
     */
    public function lockToNormal(): void
    {
        $this->lockToNormal = true;
        $this->current = 'normal';
    }

    /**
     * The array $cmd may hold various keys which notes some action to take.
     * Normally perform only one action at a time.
     * In scripts like db_list.php / filelist/mod1/index.php the GET-var CB is used to control the clipboard.
     *
     * Selecting / Deselecting elements
     * Array $cmd['el'] has keys = element-ident, value = element value (see description of clipData array in header)
     * Selecting elements for 'copy' should be done by simultaneously setting setCopyMode.
     *
     * @param array $cmd Array of actions, see function description
     */
    public function setCmd(array $cmd): void
    {
        $cmd['el'] ??= [];
        $cmd['el'] = is_iterable($cmd['el']) ? $cmd['el'] : [];
        foreach ($cmd['el'] as $key => $value) {
            if ($this->current === 'normal') {
                unset($this->clipData['normal']);
            }
            if ($value) {
                $this->clipData[$this->current]['el'][$key] = $value;
            } else {
                $this->removeElement((string)$key);
            }
            $this->changed = true;
        }
        // Change clipboard pad (if not locked to normal)
        if ($cmd['setP'] ?? false) {
            $this->setCurrentPad((string)$cmd['setP']);
        }
        // Remove element	(value = item ident: DB; '[tablename]|[uid]'    FILE: '_FILE|[md5 hash of path]'
        if ($cmd['remove'] ?? false) {
            $this->removeElement((string)$cmd['remove']);
            $this->changed = true;
        }
        // Remove all on current pad (value = pad-ident)
        if ($cmd['removeAll'] ?? false) {
            $this->clipData[$cmd['removeAll']] = [];
            $this->changed = true;
        }
        // Set copy mode of the tab
        if (isset($cmd['setCopyMode'])) {
            $this->clipData[$this->current]['mode'] = $cmd['setCopyMode'] ? 'copy' : '';
            $this->changed = true;
        }
    }

    /**
     * Setting the current pad on clipboard
     *
     * @param string $padIdentifier Key in the array $this->clipData
     */
    public function setCurrentPad(string $padIdentifier): void
    {
        // Change clipboard pad (if not locked to normal)
        if (!$this->lockToNormal && $this->current !== $padIdentifier) {
            if (isset($this->clipData[$padIdentifier])) {
                $this->clipData['current'] = ($this->current = $padIdentifier);
            }
            if ($this->current !== 'normal' || !$this->isElements()) {
                $this->clipData[$this->current]['mode'] = '';
            }
            // Setting mode to default (move) if no items on it or if not 'normal'
            $this->changed = true;
        }
    }

    /**
     * Call this after initialization and setCmd in order to save the clipboard to the user session.
     * The function will check if the internal flag ->changed has been set and if so, save the clipboard. Else not.
     */
    public function endClipboard(): void
    {
        if ($this->changed) {
            $this->saveClipboard();
        }
        $this->changed = false;
    }

    /**
     * Cleans up an incoming element array $CBarr (Array selecting/deselecting elements)
     *
     * @param array $CBarr Element array from outside ("key" => "selected/deselected")
     * @param string $table The 'table which is allowed'. Must be set.
     * @param bool $removeDeselected Can be set in order to remove entries which are marked for deselection.
     * @return array Processed input $CBarr
     */
    public function cleanUpCBC(array $CBarr, string $table, bool $removeDeselected = false): array
    {
        foreach ($CBarr as $reference => $value) {
            $referenceTable = (string)(explode('|', $reference)[0] ?? '');
            if ($referenceTable !== $table || ($removeDeselected && !$value)) {
                unset($CBarr[$reference]);
            }
        }
        return $CBarr;
    }

    /**
     * @deprecated Backwards compatibility for some extensions. Will be removed in v12.
     */
    public function printClipboard(string $table = ''): string
    {
        $attributes = [
          'table' => $table,
        ];
        GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule('TYPO3/CMS/Backend/ClipboardPanel');
        return '<typo3-backend-clipboard-panel ' . GeneralUtility::implodeAttributes($attributes, true) . '></typo3-backend-clipboard-panel>';
    }

    public function getClipboardData(string $table = ''): array
    {
        $lang = $this->getLanguageService();

        $clipboardData = [
            'current' => $this->current,
            'copyMode' => $this->currentMode(),
            'elementCount' => count($this->elFromTable($table)),
        ];

        // Initialize tabs by adding the "normal" tab
        $tabs = [
            [
                'identifier' => 'normal',
                'info' => $this->getTabInfo('normal', $table),
                'title' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.normal'),
                'description' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.normal-description'),
                'items' => $this->current === 'normal' ? $this->getTabItems('normal', $table) : [],
            ],
        ];
        // Add numeric tabs
        for ($a = 1; $a <= $this->numberOfPads; $a++) {
            $tabs[] = [
                'identifier' => 'tab_' . $a,
                'info' => $this->getTabInfo('tab_' . $a, $table),
                'title' => sprintf($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cliptabs-name'), (string)$a),
                'description' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cliptabs-description'),
                'items' => $this->current === 'tab_' . $a ? $this->getTabItems('tab_' . $a, $table) : [],
            ];
        }
        // Add tabs to clipboard Data
        $clipboardData['tabs'] = $tabs;

        return $clipboardData;
    }

    /**
     * Get the items for the given pad identifier
     *
     * @param string $padIdentifier Pad reference
     * @return array The tab items
     */
    protected function getTabItems(string $padIdentifier, string $currentTable): array
    {
        if (!is_array($this->clipData[$padIdentifier]['el'] ?? false)) {
            return [];
        }

        $records = [];
        foreach ($this->clipData[$padIdentifier]['el'] as $reference => $value) {
            if (!$value) {
                // Skip element if empty value
                continue;
            }
            [$table, $uid] = explode('|', $reference);
            // Rendering files/directories on the clipboard
            if ($table === '_FILE') {
                $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($value);
                if ($fileObject) {
                    $thumb = '';
                    $folder = $fileObject instanceof Folder;
                    $size = $folder ? '' : '(' . GeneralUtility::formatSize((int)$fileObject->getSize()) . 'bytes)';
                    /** @var File $fileObject */
                    if (!$folder && $fileObject->isImage()) {
                        $processedFile = $fileObject->process(
                            ProcessedFile::CONTEXT_IMAGEPREVIEW,
                            [
                                'width' => 64,
                                'height' => 64,
                            ]
                        );

                        $thumb = '<img src="' . htmlspecialchars($processedFile->getPublicUrl() ?? '') . '" ' .
                            'width="' . htmlspecialchars((string)$processedFile->getProperty('width')) . '" ' .
                            'height="' . htmlspecialchars((string)$processedFile->getProperty('height')) . '" ' .
                            'title="' . htmlspecialchars($processedFile->getName()) . '" alt="" />';
                    }
                    $records[] = [
                        'identifier' => '_FILE|' . md5($value),
                        'icon' => '<span title="' . htmlspecialchars($fileObject->getName() . ' ' . $size) . '">' . $this->iconFactory->getIconForResource(
                            $fileObject,
                            Icon::SIZE_SMALL
                        )->render() . '</span>',
                        'title' => $this->linkItemText(htmlspecialchars(GeneralUtility::fixed_lgd_cs(
                            $fileObject->getName(),
                            $this->getBackendUser()->uc['titleLen']
                        )), $fileObject->getParentFolder()->getCombinedIdentifier(), $currentTable === '_FILE'),
                        'thumb' => $thumb,
                        'infoDataDispatch' => [
                            'action' => 'TYPO3.InfoWindow.showItem',
                            'args' => GeneralUtility::jsonEncodeForHtmlAttribute([$table, $value], false),
                        ],
                    ];
                } else {
                    // If the file did not exist (or is illegal) then it is removed from the clipboard immediately:
                    unset($this->clipData[$padIdentifier]['el'][$reference]);
                    $this->changed = true;
                }
            } else {
                // Rendering records:
                $record = BackendUtility::getRecordWSOL($table, (int)$uid);
                if (is_array($record)) {
                    $isRequestedTable = $currentTable !== '_FILE';
                    $records[] = [
                        'identifier' => $table . '|' . $uid,
                        'icon' => $this->iconFactory->getIconForRecord($table, $record, Icon::SIZE_SMALL)->render(),
                        'title' => $this->linkItemText(htmlspecialchars(GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle(
                            $table,
                            $record
                        ), $this->getBackendUser()->uc['titleLen'])), $record, $isRequestedTable),
                        'infoDataDispatch' => [
                            'action' => 'TYPO3.InfoWindow.showItem',
                            'args' => GeneralUtility::jsonEncodeForHtmlAttribute([$table, (int)$uid], false),
                        ],
                    ];

                    $localizationData = $this->getLocalizations($table, $record, $isRequestedTable);
                    if (!empty($localizationData)) {
                        $records = array_merge($records, $localizationData);
                    }
                } else {
                    unset($this->clipData[$padIdentifier]['el'][$reference]);
                    $this->changed = true;
                }
            }
        }
        $this->endClipboard();
        return $records;
    }

    /**
     * Returns true if the clipboard contains elements
     *
     * @return bool
     */
    public function hasElements(): bool
    {
        foreach ($this->clipData as $data) {
            if (isset($data['el']) && is_array($data['el']) && !empty($data['el'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets all localizations of the current record.
     *
     * @param string $table The table
     * @param array $parentRecord The parent record
     * @param bool $isRequestedTable Whether the element is from the requested table
     * @return array HTML table rows
     */
    protected function getLocalizations(string $table, array $parentRecord, bool $isRequestedTable): array
    {
        if (!BackendUtility::isTableLocalizable($table)) {
            return [];
        }

        $records = [];
        $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $tcaCtrl['transOrigPointerField'],
                    $queryBuilder->createNamedParameter((int)$parentRecord['uid'], \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->neq(
                    $tcaCtrl['languageField'],
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    'pid',
                    $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
                )
            )
            ->orderBy($tcaCtrl['languageField']);

        if (BackendUtility::isTableWorkspaceEnabled($table)) {
            $queryBuilder->getRestrictions()->add(
                GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace)
            );
        }

        foreach ($queryBuilder->executeQuery()->fetchAllAssociative() as $record) {
            $title = htmlspecialchars(GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($table, $record), $this->getBackendUser()->uc['titleLen']));
            if (!$isRequestedTable) {
                // In case the current table is not the requested table, e.g. "_FILE", wrap title in "muted" style
                $title = '<span class="text-muted">' . $title . '</span>';
            }
            $records[] = [
                'icon' => $this->iconFactory->getIconForRecord($table, $record, Icon::SIZE_SMALL)->render(),
                'title' => $title,
                'infoDataDispatch' => [
                    'action' => 'TYPO3.InfoWindow.showItem',
                    'args' => GeneralUtility::jsonEncodeForHtmlAttribute([$table, (int)$record['uid']], false),
                ],
            ];
        }

        return $records;
    }

    /**
     * Additional information for the tab. This is either
     * the current copyMode (for "normal") or the elements
     * count, for numeric tabs. Latter will not be shown,
     * in case no elements exist for the tab.
     *
     * @param string $padIdentifier Identifier for the clipboard pad
     * @param string $table The table name to count for elements
     * @return string
     */
    protected function getTabInfo(string $padIdentifier, string $table = ''): string
    {
        $el = count($this->elFromTable($table, $padIdentifier));
        if (!$el) {
            return '';
        }
        $modeLabel = ($this->clipData['normal']['mode'] ?? '') === 'copy' ? $this->clipboardLabel('cm.copy') : $this->clipboardLabel('cm.cut');
        return ' (' . ($padIdentifier === 'normal' ? $modeLabel : htmlspecialchars((string)$el)) . ')';
    }

    /**
     * Wraps the title of the element in a link to the page/folder where they originate from.
     * Will be wrapped into "muted" style in case the element is not from the currently requested table.
     *
     * @param string $itemText Title of element - must be htmlspecialchar'ed on beforehand.
     * @param array|string $reference If array, a record is expected. If string, its the folders' combined identifier
     * @param bool $isRequestedTable Whether the element is from the requested table
     * @return string
     */
    protected function linkItemText(string $itemText, $reference, bool $isRequestedTable): string
    {
        if (is_array($reference)) {
            if ($isRequestedTable) {
                // Wrap in link to corresponding page in recordlist in case current requested table matches
                $itemText = '<a href="' . htmlspecialchars((string)$this->uriBuilder->buildUriFromRoute('web_list', ['id' => $reference['pid']])) . '">' . $itemText . '</a>';
            } else {
                $itemText = '<span class="text-muted">' . $itemText . '</span>';
            }
        } elseif (is_string($reference)) {
            if ($isRequestedTable && ExtensionManagementUtility::isLoaded('filelist')) {
                // Wrap in link to the files folder in case current requested table matches and filelist is loaded
                $itemText = '<a href="' . htmlspecialchars((string)$this->uriBuilder->buildUriFromRoute('file_FilelistList', ['id' => $reference])) . '">' . $itemText . '</a>';
            } else {
                $itemText = '<span class="text-muted">' . $itemText . '</span>';
            }
        }
        return $itemText;
    }

    /**
     * Returns the select-url for database elements
     *
     * @param string $table Table name
     * @param int $uid Uid of record
     * @param bool $copy If set, copymode will be enabled
     * @param bool $deselect If set, the link will deselect, otherwise select.
     * @return string URL linking to the current script but with the CB array set to select the element with table/uid
     */
    public function selUrlDB(string $table, int $uid, bool $copy = false, bool $deselect = false): string
    {
        return $this->buildUrl(['CB' => [
            'el' => [
                $table . '|' . $uid => $deselect ? 0 : 1,
            ],
            'setCopyMode' => (int)$copy,
        ]]);
    }

    /**
     * Returns the select-url for files
     *
     * @param string $path Filepath
     * @param bool $copy If set, copymode will be enabled
     * @param bool $deselect If set, the link will deselect, otherwise select.
     * @return string URL linking to the current script but with the CB array set to select the path
     */
    public function selUrlFile(string $path, bool $copy = false, bool $deselect = false): string
    {
        return $this->buildUrl(['CB' => [
            'el' => [
                '_FILE|' . md5($path) => $deselect ? '' : $path,
            ],
            'setCopyMode' => (int)$copy,
        ]]);
    }

    /**
     * pasteUrl of the element (database and file)
     * For the meaning of $table and $uid, please read from ->makePasteCmdArray!!!
     * The URL will point to tce_file or tce_db depending in $table
     *
     * @param string $table Tablename (_FILE for files)
     * @param string|int $identifier "destination": can be positive or negative indicating how the paste is done
     *                               (paste into / paste after). For files, this is the combined identifier.
     * @param bool $setRedirect If set, then the redirect URL will point back to the current script, but with CB reset.
     * @param array|null $update Additional key/value pairs which should get set in the moved/copied record (via DataHandler)
     * @return string
     */
    public function pasteUrl(string $table, $identifier, bool $setRedirect = true, array $update = null): string
    {
        $urlParameters = [
            'CB' => [
                'paste' => $table . '|' . $identifier,
                'pad' => $this->current,
            ],
        ];
        if ($setRedirect) {
            $urlParameters['redirect'] = $this->buildUrl(['CB' => []]);
        }
        if (is_array($update)) {
            $urlParameters['CB']['update'] = $update;
        }
        return (string)$this->uriBuilder->buildUriFromRoute($table === '_FILE' ? 'tce_file' : 'tce_db', $urlParameters);
    }

    /**
     * Returns confirm JavaScript message
     *
     * @param string $table Table name
     * @param array|string $reference For records its an array, for files its a string (path)
     * @param string $type Type-code
     * @param array $selectedElements Array of selected elements
     * @param string $columnLabel Name of the content column
     * @return string the text for a confirm message
     */
    public function confirmMsgText(
        string $table,
        $reference,
        string $type,
        array $selectedElements,
        string $columnLabel = ''
    ): string {
        if (!$this->getBackendUser()->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE)) {
            return '';
        }

        $labelKey = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.'
            . ($this->currentMode() === 'copy' ? 'copy' : 'move')
            . ($this->current === 'normal' ? '' : 'cb') . '_' . $type;
        $confirmationMessage = $this->getLanguageService()->sL($labelKey . ($columnLabel ? '_colPos' : ''));

        if ($table === '_FILE' && is_string($reference)) {
            $recordTitle = PathUtility::basename($reference);
            if ($this->current === 'normal') {
                $selectedItem = reset($selectedElements);
                $selectedRecordTitle = PathUtility::basename($selectedItem);
            } else {
                $selectedRecordTitle = count($selectedElements);
            }
        } else {
            $recordTitle = $table !== 'pages' && is_array($reference)
                ? BackendUtility::getRecordTitle($table, $reference)
                : $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
            if ($this->current === 'normal') {
                $selectedItem = $this->getSelectedRecord();
                $selectedRecordTitle = $selectedItem['_RECORD_TITLE'];
            } else {
                $selectedRecordTitle = count($selectedElements);
            }
        }
        // @TODO
        // This can get removed as soon as the "_colPos" label is translated
        // into all available locallang languages.
        if (!$confirmationMessage && $columnLabel) {
            $recordTitle .= ' | ' . $columnLabel;
            $confirmationMessage = $this->getLanguageService()->sL($labelKey);
        }

        return sprintf(
            $confirmationMessage,
            GeneralUtility::fixed_lgd_cs($selectedRecordTitle, 30),
            GeneralUtility::fixed_lgd_cs($recordTitle, 30),
            GeneralUtility::fixed_lgd_cs($columnLabel, 30)
        );
    }

    /**
     * Clipboard label - getting from "EXT:core/Resources/Private/Language/locallang_core.xlf:"
     *
     * @param string $key Label Key
     * @return string htmspecialchared' label
     */
    protected function clipboardLabel(string $key): string
    {
        return htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:' . $key));
    }

    /*****************************************
     *
     * Helper functions
     *
     ****************************************/
    /**
     * Removes element on clipboard
     *
     * @param string $elementKey Key of element in ->clipData array
     */
    public function removeElement(string $elementKey): void
    {
        unset($this->clipData[$this->current]['el'][$elementKey]);
        $this->changed = true;
    }

    /**
     * Saves the clipboard, no questions asked.
     * Use ->endClipboard normally (as it checks if changes has been done so saving is necessary)
     */
    protected function saveClipboard(): void
    {
        $this->getBackendUser()->pushModuleData('clipboard', $this->clipData);
    }

    /**
     * Returns the current mode, 'copy' or 'cut'
     *
     * @return string "copy" or "cut
     */
    public function currentMode(): string
    {
        return ($this->clipData[$this->current]['mode'] ?? '') === 'copy' ? 'copy' : 'cut';
    }

    /**
     * This traverses the elements on the current clipboard pane
     * and unsets elements which does not exist anymore or are disabled.
     */
    public function cleanCurrent(): void
    {
        if (!is_array($this->clipData[$this->current]['el'] ?? false)) {
            return;
        }

        foreach ($this->clipData[$this->current]['el'] as $reference => $value) {
            [$table, $uid] = explode('|', $reference);
            if ($table !== '_FILE') {
                if (!$value || !is_array(BackendUtility::getRecord($table, (int)$uid, 'uid'))) {
                    unset($this->clipData[$this->current]['el'][$reference]);
                    $this->changed = true;
                }
            } elseif (!$value) {
                unset($this->clipData[$this->current]['el'][$reference]);
                $this->changed = true;
            } else {
                try {
                    $this->resourceFactory->retrieveFileOrFolderObject($value);
                } catch (ResourceDoesNotExistException $e) {
                    // The file has been deleted in the meantime, so just remove it silently
                    unset($this->clipData[$this->current]['el'][$reference]);
                }
            }
        }
    }

    /**
     * Counts the number of elements from the table $matchTable. If $matchTable is blank, all tables (except '_FILE' of course) is counted.
     *
     * @param string $matchTable Table to match/count for.
     * @param string $padIdentifier Can optionally be used to set another pad than the current.
     * @return array Array with keys from the CB.
     */
    public function elFromTable(string $matchTable = '', string $padIdentifier = ''): array
    {
        $padIdentifier = $padIdentifier ?: $this->current;

        if (!is_array($this->clipData[$padIdentifier]['el'] ?? false)) {
            return [];
        }

        $elements = [];
        foreach ($this->clipData[$padIdentifier]['el'] as $reference => $value) {
            if (!$value) {
                continue;
            }
            [$table, $uid] = explode('|', $reference);
            if ($table !== '_FILE') {
                if ((!$matchTable || $table === $matchTable) && ($GLOBALS['TCA'][$table] ?? false)) {
                    $elements[$reference] = $padIdentifier === 'normal' ? $value : $uid;
                }
            } elseif ($table === $matchTable) {
                $elements[$reference] = $value;
            }
        }
        return $elements;
    }

    /**
     * Verifies if the item $table/$uid is on the current pad.
     * If the pad is "normal" and the element exists, the mode value is returned.
     * Thus you'll know if the item was copied or cut.
     *
     * @param string $table Table name, (_FILE for files...)
     * @param string|int $identifier Either the records' uid or a filepath
     * @return string If selected the current mode is returned, otherwise an empty string
     */
    public function isSelected(string $table, $identifier): string
    {
        $key = $table . '|' . $identifier;
        $mode = $this->current === 'normal' ? $this->currentMode() : 'any';
        return !empty($this->clipData[$this->current]['el'][$key]) ? $mode : '';
    }

    /**
     * Returns the first element on the current clipboard
     * Makes sense only for DB records - not files!
     *
     * @return array Element record with extra field _RECORD_TITLE set to the title of the record
     */
    public function getSelectedRecord(): array
    {
        $elements = $this->elFromTable();
        reset($elements);
        [$table, $uid] = explode('|', (string)key($elements));
        if (!$this->isSelected($table, (int)$uid)) {
            return [];
        }
        $selectedRecord = BackendUtility::getRecordWSOL($table, (int)$uid);
        $selectedRecord['_RECORD_TITLE'] = BackendUtility::getRecordTitle($table, $selectedRecord);
        return $selectedRecord;
    }

    /**
     * Reports if the current pad has elements (does not check file/DB type OR if file/DBrecord exists or not. Only counting array)
     *
     * @return bool TRUE if elements exist.
     */
    protected function isElements(): bool
    {
        return is_array($this->clipData[$this->current]['el'] ?? null) && !empty($this->clipData[$this->current]['el']);
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Builds a URL to the current module with the received
     * parameters, merged / replaced by additional parameters.
     *
     * @param array $parameters
     * @return string
     */
    protected function buildUrl(array $parameters = []): string
    {
        if ($this->request === null) {
            throw new \RuntimeException(
                'Request object must be set to generate clipboard URL\'s',
                1633604720
            );
        }
        return (string)$this->uriBuilder->buildUriFromRoute(
            $this->request->getAttribute('route')->getOption('_identifier'),
            array_replace($this->request->getQueryParams(), $parameters)
        );
    }
}
