<?php
namespace TYPO3\CMS\Backend\Clipboard;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * TYPO3 clipboard for records and files
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
class Clipboard
{
    /**
     * @var int
     */
    public $numberTabs = 3;

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
     * FILE: keys = '_FILE|[shortmd5 of path]'	eg. '_FILE|9ebc7e5c74'
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
    public $clipData = [];

    /**
     * @var int
     */
    public $changed = 0;

    /**
     * @var string
     */
    public $current = '';

    /**
     * @var int
     */
    public $lockToNormal = 0;

    /**
     * If set, clipboard is displaying files.
     *
     * @var bool
     */
    public $fileMode = false;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * Construct
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     * @throws \InvalidArgumentException
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->view = $this->getStandaloneView();
    }

    /*****************************************
     *
     * Initialize
     *
     ****************************************/
    /**
     * Initialize the clipboard from the be_user session
     */
    public function initializeClipboard()
    {
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        // Get data
        $clipData = $this->getBackendUser()->getModuleData('clipboard', $userTsConfig['options.']['saveClipboard'] ? '' : 'ses');
        $this->numberTabs = MathUtility::forceIntegerInRange((int)($userTsConfig['options.']['clipboardNumberPads'] ?? 3), 0, 20);
        // Resets/reinstates the clipboard pads
        $this->clipData['normal'] = is_array($clipData['normal']) ? $clipData['normal'] : [];
        for ($a = 1; $a <= $this->numberTabs; $a++) {
            $this->clipData['tab_' . $a] = is_array($clipData['tab_' . $a]) ? $clipData['tab_' . $a] : [];
        }
        // Setting the current pad pointer ($this->current))
        $this->clipData['current'] = ($this->current = isset($this->clipData[$clipData['current']]) ? $clipData['current'] : 'normal');
    }

    /**
     * Call this method after initialization if you want to lock the clipboard to operate on the normal pad only.
     * Trying to switch pad through ->setCmd will not work.
     * This is used by the clickmenu since it only allows operation on single elements at a time (that is the "normal" pad)
     */
    public function lockToNormal()
    {
        $this->lockToNormal = 1;
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
    public function setCmd($cmd)
    {
        if (is_array($cmd['el'])) {
            foreach ($cmd['el'] as $k => $v) {
                if ($this->current === 'normal') {
                    unset($this->clipData['normal']);
                }
                if ($v) {
                    $this->clipData[$this->current]['el'][$k] = $v;
                } else {
                    $this->removeElement($k);
                }
                $this->changed = 1;
            }
        }
        // Change clipboard pad (if not locked to normal)
        if ($cmd['setP']) {
            $this->setCurrentPad($cmd['setP']);
        }
        // Remove element	(value = item ident: DB; '[tablename]|[uid]'    FILE: '_FILE|[shortmd5 hash of path]'
        if ($cmd['remove']) {
            $this->removeElement($cmd['remove']);
            $this->changed = 1;
        }
        // Remove all on current pad (value = pad-ident)
        if ($cmd['removeAll']) {
            $this->clipData[$cmd['removeAll']] = [];
            $this->changed = 1;
        }
        // Set copy mode of the tab
        if (isset($cmd['setCopyMode'])) {
            $this->clipData[$this->current]['mode'] = $cmd['setCopyMode'] ? 'copy' : '';
            $this->changed = 1;
        }
    }

    /**
     * Setting the current pad on clipboard
     *
     * @param string $padIdent Key in the array $this->clipData
     */
    public function setCurrentPad($padIdent)
    {
        // Change clipboard pad (if not locked to normal)
        if (!$this->lockToNormal && $this->current != $padIdent) {
            if (isset($this->clipData[$padIdent])) {
                $this->clipData['current'] = ($this->current = $padIdent);
            }
            if ($this->current !== 'normal' || !$this->isElements()) {
                $this->clipData[$this->current]['mode'] = '';
            }
            // Setting mode to default (move) if no items on it or if not 'normal'
            $this->changed = 1;
        }
    }

    /**
     * Call this after initialization and setCmd in order to save the clipboard to the user session.
     * The function will check if the internal flag ->changed has been set and if so, save the clipboard. Else not.
     */
    public function endClipboard()
    {
        if ($this->changed) {
            $this->saveClipboard();
        }
        $this->changed = 0;
    }

    /**
     * Cleans up an incoming element array $CBarr (Array selecting/deselecting elements)
     *
     * @param array $CBarr Element array from outside ("key" => "selected/deselected")
     * @param string $table The 'table which is allowed'. Must be set.
     * @param bool|int $removeDeselected Can be set in order to remove entries which are marked for deselection.
     * @return array Processed input $CBarr
     */
    public function cleanUpCBC($CBarr, $table, $removeDeselected = 0)
    {
        if (is_array($CBarr)) {
            foreach ($CBarr as $k => $v) {
                $p = explode('|', $k);
                if ((string)$p[0] != (string)$table || $removeDeselected && !$v) {
                    unset($CBarr[$k]);
                }
            }
        }
        return $CBarr;
    }

    /*****************************************
     *
     * Clipboard HTML renderings
     *
     ****************************************/
    /**
     * Prints the clipboard
     *
     * @return string HTML output
     * @throws \BadFunctionCallException
     */
    public function printClipboard()
    {
        $languageService = $this->getLanguageService();
        $elementCount = count($this->elFromTable($this->fileMode ? '_FILE' : ''));
        // Copymode Selector menu
        $copymodeUrl = GeneralUtility::linkThisScript();

        $this->view->assign('actionCopyModeUrl', htmlspecialchars(GeneralUtility::quoteJSvalue($copymodeUrl . '&CB[setCopyMode]=')));
        $this->view->assign('actionCopyModeUrl1', htmlspecialchars(GeneralUtility::quoteJSvalue($copymodeUrl . '&CB[setCopyMode]=1')));
        $this->view->assign('currentMode', $this->currentMode());
        $this->view->assign('elementCount', $elementCount);

        if ($elementCount) {
            $removeAllUrl = GeneralUtility::linkThisScript(['CB' => ['removeAll' => $this->current]]);
            $this->view->assign('removeAllUrl', $removeAllUrl);

            // Selector menu + clear button
            $optionArray = [];
            // Import / Export link:
            if (ExtensionManagementUtility::isLoaded('impexp')) {
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $url = $uriBuilder->buildUriFromRoute('xMOD_tximpexp', $this->exportClipElementParameters());
                $optionArray[] = [
                    'label' => $this->clLabel('export', 'rm'),
                    'uri' => (string)$url
                ];
            }
            // Edit:
            if (!$this->fileMode) {
                $optionArray[] = [
                    'label' => $this->clLabel('edit', 'rm'),
                    'uri' => '#',
                    'additionalAttributes' => [
                        'onclick' => htmlspecialchars('window.location.href=' . GeneralUtility::quoteJSvalue($this->editUrl() . '&returnUrl=') . '+top.rawurlencode(window.location.href);'),
                    ]
                ];
            }

            // Delete referenced elements:
            $confirmationCheck = false;
            if ($this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE)) {
                $confirmationCheck = true;
            }

            $confirmationMessage = sprintf(
                $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.deleteClip'),
                $elementCount
            );
            $title = $languageService
                ->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clipboard.delete_elements');
            $returnUrl = $this->deleteUrl(true, $this->fileMode);
            $btnOkText = $languageService
                ->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:buttons.confirm.delete_elements.yes');
            $btnCancelText = $languageService
                ->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:buttons.confirm.delete_elements.no');
            $optionArray[] = [
                'label' => htmlspecialchars($title),
                'uri' => $returnUrl,
                'additionalAttributes' => [
                    'class' => $confirmationCheck ? 't3js-modal-trigger' : '',
                ],
                'data' => [
                    'severity' => 'warning',
                    'button-close-text' => htmlspecialchars($btnCancelText),
                    'button-ok-text' => htmlspecialchars($btnOkText),
                    'content' => htmlspecialchars($confirmationMessage),
                    'title' => htmlspecialchars($title)
                ]
            ];

            // Clear clipboard
            $optionArray[] = [
                'label' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clipboard.clear_clipboard'),
                'uri' => $removeAllUrl . '#clip_head'
            ];
            $this->view->assign('optionArray', $optionArray);
        }

        // Print header and content for the NORMAL tab:
        $this->view->assign('current', $this->current);
        $tabArray = [];
        $tabArray['normal'] = [
            'id' => 'normal',
            'number' => 0,
            'url' => GeneralUtility::linkThisScript(['CB' => ['setP' => 'normal']]),
            'description' => 'labels.normal-description',
            'label' => 'labels.normal',
            'padding' => $this->padTitle('normal')
        ];
        if ($this->current === 'normal') {
            $tabArray['normal']['content'] = $this->getContentFromTab('normal');
        }
        // Print header and content for the NUMERIC tabs:
        for ($a = 1; $a <= $this->numberTabs; $a++) {
            $tabArray['tab_' . $a] = [
                'id' => 'tab_' . $a,
                'number' => $a,
                'url' => GeneralUtility::linkThisScript(['CB' => ['setP' => 'tab_' . $a]]),
                'description' => 'labels.cliptabs-description',
                'label' => 'labels.cliptabs-name',
                'padding' => $this->padTitle('tab_' . $a)
            ];
            if ($this->current === 'tab_' . $a) {
                $tabArray['tab_' . $a]['content'] = $this->getContentFromTab('tab_' . $a);
            }
        }
        $this->view->assign('clipboardHeader', BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_clipboard', $this->clLabel('clipboard', 'buttons')));
        $this->view->assign('tabArray', $tabArray);
        return $this->view->render();
    }

    /**
     * Print the content on a pad. Called from ->printClipboard()
     *
     * @internal
     * @param string $pad Pad reference
     * @return array Array with table rows for the clipboard.
     */
    public function getContentFromTab($pad)
    {
        $lines = [];
        if (is_array($this->clipData[$pad]['el'] ?? false)) {
            foreach ($this->clipData[$pad]['el'] as $k => $v) {
                if ($v) {
                    list($table, $uid) = explode('|', $k);
                    // Rendering files/directories on the clipboard
                    if ($table === '_FILE') {
                        $fileObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($v);
                        if ($fileObject) {
                            $thumb = [];
                            $folder = $fileObject instanceof \TYPO3\CMS\Core\Resource\Folder;
                            $size = $folder ? '' : '(' . GeneralUtility::formatSize($fileObject->getSize()) . 'bytes)';
                            if (
                                !$folder
                                && GeneralUtility::inList(
                                    $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                                    $fileObject->getExtension()
                                )
                            ) {
                                $thumb = [
                                    'image' => $fileObject->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, [])->getPublicUrl(true),
                                    'title' => htmlspecialchars($fileObject->getName())
                                ];
                            }
                            $lines[] = [
                                'icon' => '<span title="' . htmlspecialchars($fileObject->getName() . ' ' . $size) . '">' . $this->iconFactory->getIconForResource(
                                    $fileObject,
                                    Icon::SIZE_SMALL
                                )->render() . '</span>',
                                'title' => $this->linkItemText(htmlspecialchars(GeneralUtility::fixed_lgd_cs(
                                    $fileObject->getName(),
                                    $this->getBackendUser()->uc['titleLen']
                                )), $fileObject->getName()),
                                'thumb' => $thumb,
                                'infoLink' => htmlspecialchars('top.TYPO3.InfoWindow.showItem(' . GeneralUtility::quoteJSvalue($table) . ', ' . GeneralUtility::quoteJSvalue($v) . '); return false;'),
                                'removeLink' => $this->removeUrl('_FILE', GeneralUtility::shortMD5($v))
                            ];
                        } else {
                            // If the file did not exist (or is illegal) then it is removed from the clipboard immediately:
                            unset($this->clipData[$pad]['el'][$k]);
                            $this->changed = 1;
                        }
                    } else {
                        // Rendering records:
                        $rec = BackendUtility::getRecordWSOL($table, $uid);
                        if (is_array($rec)) {
                            $lines[] = [
                                'icon' => $this->linkItemText($this->iconFactory->getIconForRecord(
                                    $table,
                                    $rec,
                                    Icon::SIZE_SMALL
                                )->render(), $rec, $table),
                                'title' => $this->linkItemText(htmlspecialchars(GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle(
                                    $table,
                                    $rec
                                ), $this->getBackendUser()->uc['titleLen'])), $rec, $table),
                                'infoLink' => htmlspecialchars('top.TYPO3.InfoWindow.showItem(' . GeneralUtility::quoteJSvalue($table) . ', \'' . (int)$uid . '\'); return false;'),
                                'removeLink' => $this->removeUrl($table, $uid)
                            ];

                            $localizationData = $this->getLocalizations($table, $rec);
                            if (!empty($localizationData)) {
                                $lines = array_merge($lines, $localizationData);
                            }
                        } else {
                            unset($this->clipData[$pad]['el'][$k]);
                            $this->changed = 1;
                        }
                    }
                }
            }
        }
        $this->endClipboard();
        return $lines;
    }

    /**
     * Returns true if the clipboard contains elements
     *
     * @return bool
     */
    public function hasElements()
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
     * @param array $parentRec The current record
     * @return array HTML table rows
     */
    public function getLocalizations($table, $parentRec)
    {
        $lines = [];
        $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];
        if (BackendUtility::isTableLocalizable($table)) {
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
                        $queryBuilder->createNamedParameter($parentRec['uid'], \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq(
                        $tcaCtrl['languageField'],
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gt(
                        'pid',
                        $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
                    )
                );

            if (isset($tcaCtrl['versioningWS']) && $tcaCtrl['versioningWS']) {
                $queryBuilder
                    ->andWhere(
                        $queryBuilder->expr()->eq(
                            't3ver_wsid',
                            $queryBuilder->createNamedParameter($parentRec['t3ver_wsid'], \PDO::PARAM_INT)
                        )
                    );
            }
            $rows = $queryBuilder->execute()->fetchAll();
            if (is_array($rows)) {
                foreach ($rows as $rec) {
                    $lines[] = [
                        'icon' => $this->iconFactory->getIconForRecord($table, $rec, Icon::SIZE_SMALL)->render(),
                        'title' => htmlspecialchars(GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($table, $rec), $this->getBackendUser()->uc['titleLen']))
                    ];
                }
            }
        }
        return $lines;
    }

    /**
     * Warps title with number of elements if any.
     *
     * @param string  $pad Pad reference
     * @return string padding
     */
    public function padTitle($pad)
    {
        $el = count($this->elFromTable($this->fileMode ? '_FILE' : '', $pad));
        if ($el) {
            return ' (' . ($pad === 'normal' ? ($this->clipData['normal']['mode'] === 'copy' ? $this->clLabel('copy', 'cm') : $this->clLabel('cut', 'cm')) : htmlspecialchars($el)) . ')';
        }
        return '';
    }

    /**
     * Wraps the title of the items listed in link-tags. The items will link to the page/folder where they originate from
     *
     * @param string $str Title of element - must be htmlspecialchar'ed on beforehand.
     * @param mixed $rec If array, a record is expected. If string, its a path
     * @param string $table Table name
     * @return string
     */
    public function linkItemText($str, $rec, $table = '')
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        if (is_array($rec) && $table) {
            if ($this->fileMode) {
                $str = '<span class="text-muted">' . $str . '</span>';
            } else {
                $str = '<a href="' . htmlspecialchars((string)$uriBuilder->buildUriFromRoute('web_list', ['id' => $rec['pid']])) . '">' . $str . '</a>';
            }
        } elseif (file_exists($rec)) {
            if (!$this->fileMode) {
                $str = '<span class="text-muted">' . $str . '</span>';
            } elseif (ExtensionManagementUtility::isLoaded('filelist')) {
                $str = '<a href="' . htmlspecialchars((string)$uriBuilder->buildUriFromRoute('file_list', ['id' => PathUtility::dirname($rec)])) . '">' . $str . '</a>';
            }
        }
        return $str;
    }

    /**
     * Returns the select-url for database elements
     *
     * @param string $table Table name
     * @param int $uid Uid of record
     * @param bool|int $copy If set, copymode will be enabled
     * @param bool|int $deselect If set, the link will deselect, otherwise select.
     * @param array $baseArray The base array of GET vars to be sent in addition. Notice that current GET vars WILL automatically be included.
     * @return string URL linking to the current script but with the CB array set to select the element with table/uid
     */
    public function selUrlDB($table, $uid, $copy = 0, $deselect = 0, $baseArray = [])
    {
        $CB = ['el' => [rawurlencode($table . '|' . $uid) => $deselect ? 0 : 1]];
        if ($copy) {
            $CB['setCopyMode'] = 1;
        }
        $baseArray['CB'] = $CB;
        return GeneralUtility::linkThisScript($baseArray);
    }

    /**
     * Returns the select-url for files
     *
     * @param string $path Filepath
     * @param bool|int $copy If set, copymode will be enabled
     * @param bool|int $deselect If set, the link will deselect, otherwise select.
     * @param array $baseArray The base array of GET vars to be sent in addition. Notice that current GET vars WILL automatically be included.
     * @return string URL linking to the current script but with the CB array set to select the path
     */
    public function selUrlFile($path, $copy = 0, $deselect = 0, $baseArray = [])
    {
        $CB = ['el' => [rawurlencode('_FILE|' . GeneralUtility::shortMD5($path)) => $deselect ? '' : $path]];
        if ($copy) {
            $CB['setCopyMode'] = 1;
        }
        $baseArray['CB'] = $CB;
        return GeneralUtility::linkThisScript($baseArray);
    }

    /**
     * pasteUrl of the element (database and file)
     * For the meaning of $table and $uid, please read from ->makePasteCmdArray!!!
     * The URL will point to tce_file or tce_db depending in $table
     *
     * @param string $table Tablename (_FILE for files)
     * @param mixed $uid "destination": can be positive or negative indicating how the paste is done (paste into / paste after)
     * @param bool $setRedirect If set, then the redirect URL will point back to the current script, but with CB reset.
     * @param array|null $update Additional key/value pairs which should get set in the moved/copied record (via DataHandler)
     * @return string
     */
    public function pasteUrl($table, $uid, $setRedirect = true, array $update = null)
    {
        $urlParameters = [
            'CB[paste]' => $table . '|' . $uid,
            'CB[pad]' => $this->current
        ];
        if ($setRedirect) {
            $urlParameters['redirect'] = GeneralUtility::linkThisScript(['CB' => '']);
        }
        if (is_array($update)) {
            $urlParameters['CB[update]'] = $update;
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute($table === '_FILE' ? 'tce_file' : 'tce_db', $urlParameters);
    }

    /**
     * deleteUrl for current pad
     *
     * @param bool $setRedirect If set, then the redirect URL will point back to the current script, but with CB reset.
     * @param bool $file If set, then the URL will link to the tce_file.php script in the typo3/ dir.
     * @return string
     */
    public function deleteUrl($setRedirect = true, $file = false)
    {
        $urlParameters = [
            'CB[delete]' => 1,
            'CB[pad]' => $this->current
        ];
        if ($setRedirect) {
            $urlParameters['redirect'] = GeneralUtility::linkThisScript(['CB' => '']);
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute($file ? 'tce_file' : 'tce_db', $urlParameters);
    }

    /**
     * editUrl of all current elements
     * ONLY database
     * Links to FormEngine
     *
     * @return string The URL to FormEngine with parameters.
     */
    public function editUrl()
    {
        $parameters = [];
        // All records
        $elements = $this->elFromTable('');
        foreach ($elements as $tP => $value) {
            list($table, $uid) = explode('|', $tP);
            $parameters['edit[' . $table . '][' . $uid . ']'] = 'edit';
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('record_edit', $parameters);
    }

    /**
     * Returns the remove-url (file and db)
     * for file $table='_FILE' and $uid = shortmd5 hash of path
     *
     * @param string $table Tablename
     * @param string $uid Uid integer/shortmd5 hash
     * @return string URL
     */
    public function removeUrl($table, $uid)
    {
        return GeneralUtility::linkThisScript(['CB' => ['remove' => $table . '|' . $uid]]);
    }

    /**
     * Returns confirm JavaScript message
     *
     * @param string $table Table name
     * @param mixed $rec For records its an array, for files its a string (path)
     * @param string $type Type-code
     * @param array $clElements Array of selected elements
     * @param string $columnLabel Name of the content column
     * @return string the text for a confirm message
     */
    public function confirmMsgText($table, $rec, $type, $clElements, $columnLabel = '')
    {
        if ($this->getBackendUser()->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE)) {
            $labelKey = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.' . ($this->currentMode() === 'copy' ? 'copy' : 'move') . ($this->current === 'normal' ? '' : 'cb') . '_' . $type;
            $msg = $this->getLanguageService()->sL($labelKey . ($columnLabel ? '_colPos' : ''));
            if ($table === '_FILE') {
                $thisRecTitle = PathUtility::basename($rec);
                if ($this->current === 'normal') {
                    $selItem = reset($clElements);
                    $selRecTitle = PathUtility::basename($selItem);
                } else {
                    $selRecTitle = count($clElements);
                }
            } else {
                $thisRecTitle = $table === 'pages' && !is_array($rec) ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] : BackendUtility::getRecordTitle($table, $rec);
                if ($this->current === 'normal') {
                    $selItem = $this->getSelectedRecord();
                    $selRecTitle = $selItem['_RECORD_TITLE'];
                } else {
                    $selRecTitle = count($clElements);
                }
            }
            // @TODO
            // This can get removed as soon as the "_colPos" label is translated
            // into all available locallang languages.
            if (!$msg && $columnLabel) {
                $thisRecTitle .= ' | ' . $columnLabel;
                $msg = $this->getLanguageService()->sL($labelKey);
            }

            // Message
            $conf = sprintf(
                $msg,
                GeneralUtility::fixed_lgd_cs($selRecTitle, 30),
                GeneralUtility::fixed_lgd_cs($thisRecTitle, 30),
                GeneralUtility::fixed_lgd_cs($columnLabel, 30)
            );
        } else {
            $conf = '';
        }
        return $conf;
    }

    /**
     * Clipboard label - getting from "EXT:core/Resources/Private/Language/locallang_core.xlf:"
     *
     * @param string $key Label Key
     * @param string $Akey Alternative key to "labels
     * @return string
     */
    public function clLabel($key, $Akey = 'labels')
    {
        return htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:' . $Akey . '.' . $key));
    }

    /**
     * Creates GET parameters for linking to the export module.
     *
     * @return array GET parameters for current clipboard content to be exported
     */
    protected function exportClipElementParameters()
    {
        // Init
        $pad = $this->current;
        $params = [];
        $params['tx_impexp']['action'] = 'export';
        // Traverse items:
        if (is_array($this->clipData[$pad]['el'] ?? false)) {
            foreach ($this->clipData[$pad]['el'] as $k => $v) {
                if ($v) {
                    list($table, $uid) = explode('|', $k);
                    // Rendering files/directories on the clipboard
                    if ($table === '_FILE') {
                        $file = ResourceFactory::getInstance()->getObjectFromCombinedIdentifier($v);
                        if ($file instanceof AbstractFile) {
                            $params['tx_impexp']['record'][] = 'sys_file:' . $file->getUid();
                        }
                    } else {
                        // Rendering records:
                        $rec = BackendUtility::getRecord($table, $uid);
                        if (is_array($rec)) {
                            $params['tx_impexp']['record'][] = $table . ':' . $uid;
                        }
                    }
                }
            }
        }
        return $params;
    }

    /*****************************************
     *
     * Helper functions
     *
     ****************************************/
    /**
     * Removes element on clipboard
     *
     * @param string $el Key of element in ->clipData array
     */
    public function removeElement($el)
    {
        unset($this->clipData[$this->current]['el'][$el]);
        $this->changed = 1;
    }

    /**
     * Saves the clipboard, no questions asked.
     * Use ->endClipboard normally (as it checks if changes has been done so saving is necessary)
     *
     * @internal
     */
    public function saveClipboard()
    {
        $this->getBackendUser()->pushModuleData('clipboard', $this->clipData);
    }

    /**
     * Returns the current mode, 'copy' or 'cut'
     *
     * @return string "copy" or "cut
     */
    public function currentMode()
    {
        return ($this->clipData[$this->current]['mode'] ?? '') === 'copy' ? 'copy' : 'cut';
    }

    /**
     * This traverses the elements on the current clipboard pane
     * and unsets elements which does not exist anymore or are disabled.
     */
    public function cleanCurrent()
    {
        if (is_array($this->clipData[$this->current]['el'] ?? false)) {
            foreach ($this->clipData[$this->current]['el'] as $k => $v) {
                list($table, $uid) = explode('|', $k);
                if ($table !== '_FILE') {
                    if (!$v || !is_array(BackendUtility::getRecord($table, $uid, 'uid'))) {
                        unset($this->clipData[$this->current]['el'][$k]);
                        $this->changed = 1;
                    }
                } else {
                    if (!$v) {
                        unset($this->clipData[$this->current]['el'][$k]);
                        $this->changed = 1;
                    } else {
                        try {
                            ResourceFactory::getInstance()->retrieveFileOrFolderObject($v);
                        } catch (\TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException $e) {
                            // The file has been deleted in the meantime, so just remove it silently
                            unset($this->clipData[$this->current]['el'][$k]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Counts the number of elements from the table $matchTable. If $matchTable is blank, all tables (except '_FILE' of course) is counted.
     *
     * @param string $matchTable Table to match/count for.
     * @param string $pad Can optionally be used to set another pad than the current.
     * @return array Array with keys from the CB.
     */
    public function elFromTable($matchTable = '', $pad = '')
    {
        $pad = $pad ? $pad : $this->current;
        $list = [];
        if (is_array($this->clipData[$pad]['el'] ?? false)) {
            foreach ($this->clipData[$pad]['el'] as $k => $v) {
                if ($v) {
                    list($table, $uid) = explode('|', $k);
                    if ($table !== '_FILE') {
                        if ((!$matchTable || (string)$table == (string)$matchTable) && $GLOBALS['TCA'][$table]) {
                            $list[$k] = $pad === 'normal' ? $v : $uid;
                        }
                    } else {
                        if ((string)$table == (string)$matchTable) {
                            $list[$k] = $v;
                        }
                    }
                }
            }
        }
        return $list;
    }

    /**
     * Verifies if the item $table/$uid is on the current pad.
     * If the pad is "normal", the mode value is returned if the element existed. Thus you'll know if the item was copy or cut moded...
     *
     * @param string $table Table name, (_FILE for files...)
     * @param int $uid Element uid (path for files)
     * @return string
     */
    public function isSelected($table, $uid)
    {
        $k = $table . '|' . $uid;
        return !empty($this->clipData[$this->current]['el'][$k]) ? ($this->current === 'normal' ? $this->currentMode() : 1) : '';
    }

    /**
     * Returns item record $table,$uid if selected on current clipboard
     * If table and uid is blank, the first element is returned.
     * Makes sense only for DB records - not files!
     *
     * @param string $table Table name
     * @param int|string $uid Element uid
     * @return array Element record with extra field _RECORD_TITLE set to the title of the record
     */
    public function getSelectedRecord($table = '', $uid = '')
    {
        if (!$table && !$uid) {
            $elArr = $this->elFromTable('');
            reset($elArr);
            list($table, $uid) = explode('|', key($elArr));
        }
        if ($this->isSelected($table, $uid)) {
            $selRec = BackendUtility::getRecordWSOL($table, $uid);
            $selRec['_RECORD_TITLE'] = BackendUtility::getRecordTitle($table, $selRec);
            return $selRec;
        }
        return [];
    }

    /**
     * Reports if the current pad has elements (does not check file/DB type OR if file/DBrecord exists or not. Only counting array)
     *
     * @return bool TRUE if elements exist.
     */
    public function isElements()
    {
        return is_array($this->clipData[$this->current]['el']) && !empty($this->clipData[$this->current]['el']);
    }

    /**
     * Applies the proper paste configuration in the $cmd array send to SimpleDataHandlerController (tce_db route)
     * $ref is the target, see description below.
     * The current pad is pasted
     *
     * $ref: [tablename]:[paste-uid].
     * Tablename is the name of the table from which elements *on the current clipboard* is pasted with the 'pid' paste-uid.
     * No tablename means that all items on the clipboard (non-files) are pasted. This requires paste-uid to be positive though.
     * so 'tt_content:-3'	means 'paste tt_content elements on the clipboard to AFTER tt_content:3 record
     * 'tt_content:30'	means 'paste tt_content elements on the clipboard into page with id 30
     * ':30'	means 'paste ALL database elements on the clipboard into page with id 30
     * ':-30'	not valid.
     *
     * @param string $ref [tablename]:[paste-uid], see description
     * @param array $CMD Command-array
     * @param array|null $update If additional values should get set in the copied/moved record this will be an array containing key=>value pairs
     * @return array Modified Command-array
     */
    public function makePasteCmdArray($ref, $CMD, array $update = null)
    {
        list($pTable, $pUid) = explode('|', $ref);
        $pUid = (int)$pUid;
        // pUid must be set and if pTable is not set (that means paste ALL elements)
        // the uid MUST be positive/zero (pointing to page id)
        if ($pTable || $pUid >= 0) {
            $elements = $this->elFromTable($pTable);
            // So the order is preserved.
            $elements = array_reverse($elements);
            $mode = $this->currentMode() === 'copy' ? 'copy' : 'move';
            // Traverse elements and make CMD array
            foreach ($elements as $tP => $value) {
                list($table, $uid) = explode('|', $tP);
                if (!is_array($CMD[$table])) {
                    $CMD[$table] = [];
                }
                if (is_array($update)) {
                    $CMD[$table][$uid][$mode] = [
                        'action' => 'paste',
                        'target' => $pUid,
                        'update' => $update,
                    ];
                } else {
                    $CMD[$table][$uid][$mode] = $pUid;
                }
                if ($mode === 'move') {
                    $this->removeElement($tP);
                }
            }
            $this->endClipboard();
        }
        return $CMD;
    }

    /**
     * Delete record entries in CMD array
     *
     * @param array $CMD Command-array
     * @return array Modified Command-array
     */
    public function makeDeleteCmdArray($CMD)
    {
        // all records
        $elements = $this->elFromTable('');
        foreach ($elements as $tP => $value) {
            list($table, $uid) = explode('|', $tP);
            if (!is_array($CMD[$table])) {
                $CMD[$table] = [];
            }
            $CMD[$table][$uid]['delete'] = 1;
            $this->removeElement($tP);
        }
        $this->endClipboard();
        return $CMD;
    }

    /*****************************************
     *
     * FOR USE IN tce_file.php:
     *
     ****************************************/
    /**
     * Applies the proper paste configuration in the $file array send to tce_file.php.
     * The current pad is pasted
     *
     * @param string $ref Reference to element (splitted by "|")
     * @param array $FILE Command-array
     * @return array Modified Command-array
     */
    public function makePasteCmdArray_file($ref, $FILE)
    {
        list($pTable, $pUid) = explode('|', $ref);
        $elements = $this->elFromTable('_FILE');
        $mode = $this->currentMode() === 'copy' ? 'copy' : 'move';
        // Traverse elements and make CMD array
        foreach ($elements as $tP => $path) {
            $FILE[$mode][] = ['data' => $path, 'target' => $pUid];
            if ($mode === 'move') {
                $this->removeElement($tP);
            }
        }
        $this->endClipboard();
        return $FILE;
    }

    /**
     * Delete files in CMD array
     *
     * @param array $FILE Command-array
     * @return array Modified Command-array
     */
    public function makeDeleteCmdArray_file($FILE)
    {
        $elements = $this->elFromTable('_FILE');
        // Traverse elements and make CMD array
        foreach ($elements as $tP => $path) {
            $FILE['delete'][] = ['data' => $path];
            $this->removeElement($tP);
        }
        $this->endClipboard();
        return $FILE;
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
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

    /**
     * returns a new standalone view, shorthand function
     *
     * @return StandaloneView
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
    protected function getStandaloneView()
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Layouts')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/Clipboard/Main.html'));

        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }
}
