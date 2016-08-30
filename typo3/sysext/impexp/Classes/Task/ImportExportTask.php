<?php
namespace TYPO3\CMS\Impexp\Task;

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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Taskcenter\Controller\TaskModuleController;
use TYPO3\CMS\Taskcenter\TaskInterface;

/**
 * This class provides a textarea to save personal notes
 */
class ImportExportTask implements TaskInterface
{
    /**
     * Back-reference to the calling reports module
     *
     * @var TaskModuleController $taskObject
     */
    protected $taskObject;

    /**
     * Constructor
     *
     * @param TaskModuleController $taskObject
     */
    public function __construct(TaskModuleController $taskObject)
    {
        $this->taskObject = $taskObject;
        $this->getLanguageService()->includeLLFile('EXT:impexp/Resources/Private/Language/locallang_csh.xlf');
    }

    /**
     * This method renders the report
     *
     * @return string The status report as HTML
     */
    public function getTask()
    {
        return $this->main();
    }

    /**
     * Render an optional additional information for the 1st view in taskcenter.
     * Empty for this task
     *
     * @return string Overview as HTML
     */
    public function getOverview()
    {
        return '';
    }

    /**
     * Main Task center module
     *
     * @return string HTML content.
     */
    public function main()
    {
        $content = '';
        $id = (int)GeneralUtility::_GP('display');
        // If a preset is found, it is rendered using an iframe
        if ($id > 0) {
            $url = BackendUtility::getModuleUrl(
                'xMOD_tximpexp',
                [
                    'tx_impexp[action]' => 'export',
                    'preset[load]' => 1,
                    'preset[select]' => $id]
            );
            return $this->taskObject->urlInIframe($url);
        } else {
            // Header
            $lang = $this->getLanguageService();
            $content .= $this->taskObject->description($lang->getLL('.alttitle'), $lang->getLL('.description'));
            $clause = $this->getBackendUser()->getPagePermsClause(1);
            $usernames = BackendUtility::getUserNames();
            // Create preset links:
            $presets = $this->getPresets();
            // If any presets found
            if (is_array($presets) && !empty($presets)) {
                $lines = [];
                foreach ($presets as $key => $presetCfg) {
                    $configuration = unserialize($presetCfg['preset_data']);
                    $title = strlen($presetCfg['title']) ? $presetCfg['title'] : '[' . $presetCfg['uid'] . ']';
                    $icon = 'EXT:impexp/Resources/Public/Images/export.gif';
                    $description = [];
                    // Is public?
                    if ($presetCfg['public']) {
                        $description[] = $lang->getLL('task.public') . ': ' . $lang->sL('LLL:EXT:lang/locallang_common.xlf:yes');
                    }
                    // Owner
                    $description[] = $lang->getLL('task.owner') . ': '
                                     . ($presetCfg['user_uid'] === $GLOBALS['BE_USER']->user['uid']
                            ? $lang->getLL('task.own')
                            : '[' . htmlspecialchars($usernames[$presetCfg['user_uid']]['username']) . ']'
                                     );
                    // Page & path
                    if ($configuration['pagetree']['id']) {
                        $description[] = $lang->getLL('task.page') . ': ' . $configuration['pagetree']['id'];
                        $description[] = $lang->getLL('task.path') . ': ' . htmlspecialchars(
                                BackendUtility::getRecordPath($configuration['pagetree']['id'], $clause, 20));
                    } else {
                        $description[] = $lang->getLL('single-record');
                    }
                    // Meta information
                    if ($configuration['meta']['title'] || $configuration['meta']['description'] || $configuration['meta']['notes']) {
                        $metaInformation = '';
                        if ($configuration['meta']['title']) {
                            $metaInformation .= '<strong>' . htmlspecialchars($configuration['meta']['title']) . '</strong><br />';
                        }
                        if ($configuration['meta']['description']) {
                            $metaInformation .= htmlspecialchars($configuration['meta']['description']);
                        }
                        if ($configuration['meta']['notes']) {
                            $metaInformation .= '<br /><br />
												<strong>' . $lang->getLL('notes') . ': </strong>
												<em>' . htmlspecialchars($configuration['meta']['notes']) . '</em>';
                        }
                        $description[] = '<br />' . $metaInformation;
                    }
                    // Collect all preset information
                    $lines[$key] = [
                        'icon' => $icon,
                        'title' => $title,
                        'descriptionHtml' => implode('<br />', $description),
                        'link' => BackendUtility::getModuleUrl('user_task') . '&SET[function]=impexp.TYPO3\\CMS\\Impexp\\Task\\ImportExportTask&display=' . $presetCfg['uid']
                    ];
                }
                // Render preset list
                $content .= $this->taskObject->renderListMenu($lines);
            } else {
                // No presets found
                $flashMessage = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $lang->getLL('no-presets'),
                    $lang->getLL('.alttitle'),
                    FlashMessage::NOTICE
                );
                /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
        }
        return $content;
    }

    /**
     * Select presets for this user
     *
     * @return array Array of preset records
     */
    protected function getPresets()
    {
        $db = $this->getDatabase();
        $presets = $db->exec_SELECTgetRows(
            '*',
            'tx_impexp_presets',
            '(public > 0 OR user_uid=' . $this->getBackendUser()->user['uid'] . ')',
            '',
            'item_uid DESC, title'
        );
        return $presets;
    }

    /**
     * Returns a \TYPO3\CMS\Core\Resource\Folder object for saving export files
     * to the server and is also used for uploading import files.
     *
     * @throws \InvalidArgumentException
     * @return NULL|\TYPO3\CMS\Core\Resource\Folder
     */
    protected function getDefaultImportExportFolder()
    {
        $defaultImportExportFolder = null;

        $defaultTemporaryFolder = $this->getBackendUser()->getDefaultUploadTemporaryFolder();
        if ($defaultTemporaryFolder !== null) {
            $importExportFolderName = 'importexport';
            $createFolder = !$defaultTemporaryFolder->hasFolder($importExportFolderName);
            if ($createFolder === true) {
                try {
                    $defaultImportExportFolder = $defaultTemporaryFolder->createFolder($importExportFolderName);
                } catch (Exception $folderAccessException) {
                }
            } else {
                $defaultImportExportFolder = $defaultTemporaryFolder->getSubfolder($importExportFolderName);
            }
        }

        return $defaultImportExportFolder;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return mixed
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return mixed
     */
    protected function getDatabase()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
