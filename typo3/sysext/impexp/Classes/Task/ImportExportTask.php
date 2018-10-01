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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Taskcenter\Controller\TaskModuleController;
use TYPO3\CMS\Taskcenter\TaskInterface;

/**
 * This class provides a textarea to save personal notes
 * @internal this is a internal TYPO3 Backend implementation and solely used for EXT:impexp and not part of TYPO3's Core API.
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
     * URL to task module
     *
     * @var string
     */
    protected $moduleUrl;

    /**
     * Constructor
     *
     * @param TaskModuleController $taskObject
     */
    public function __construct(TaskModuleController $taskObject)
    {
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $this->moduleUrl = (string)$uriBuilder->buildUriFromRoute('user_task');
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
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        // If a preset is found, it is rendered using an iframe
        if ($id > 0) {
            $url = (string)$uriBuilder->buildUriFromRoute(
                'xMOD_tximpexp',
                [
                    'tx_impexp[action]' => 'export',
                    'preset[load]' => 1,
                    'preset[select]' => $id,
                    'returnUrl' => $this->moduleUrl
                ]
            );
            \TYPO3\CMS\Core\Utility\HttpUtility::redirect($url);
        } else {
            // Header
            $lang = $this->getLanguageService();
            $content .= $this->taskObject->description($lang->getLL('.alttitle'), $lang->getLL('.description'));
            $clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
            $usernames = BackendUtility::getUserNames();
            // Create preset links:
            $presets = $this->getPresets();
            // If any presets found
            if (is_array($presets) && !empty($presets)) {
                $lines = [];
                foreach ($presets as $key => $presetCfg) {
                    $configuration = unserialize($presetCfg['preset_data'], ['allowed_classes' => false]);
                    $title = strlen($presetCfg['title']) ? $presetCfg['title'] : '[' . $presetCfg['uid'] . ']';
                    $icon = 'EXT:impexp/Resources/Public/Images/export.gif';
                    $description = [];
                    // Is public?
                    if ($presetCfg['public']) {
                        $description[] = $lang->getLL('task.public') . ': ' . $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:yes');
                    }
                    // Owner
                    $description[] = $lang->getLL('task.owner') . ': '
                        . (
                            $presetCfg['user_uid'] === $GLOBALS['BE_USER']->user['uid']
                            ? $lang->getLL('task.own')
                            : '[' . htmlspecialchars($usernames[$presetCfg['user_uid']]['username']) . ']'
                        );
                    // Page & path
                    if ($configuration['pagetree']['id']) {
                        $description[] = $lang->getLL('task.page') . ': ' . $configuration['pagetree']['id'];
                        $description[] = $lang->getLL('task.path') . ': ' . htmlspecialchars(
                            BackendUtility::getRecordPath($configuration['pagetree']['id'], $clause, 20)
                        );
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
                        'uid' => 'impexp' . $key,
                        'icon' => $icon,
                        'title' => $title,
                        'descriptionHtml' => implode('<br />', $description),
                        'link' => (string)$uriBuilder->buildUriFromRoute('user_task') . '&SET[function]=impexp.TYPO3\\CMS\\Impexp\\Task\\ImportExportTask&display=' . $presetCfg['uid']
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
                /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
        }
        return $content;
    }

    /**
     * Select presets for this user
     *
     * @return array|bool Array of preset records
     */
    protected function getPresets()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_impexp_presets');

        return $queryBuilder->select('*')
            ->from('tx_impexp_presets')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->gt(
                        'public',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'user_uid',
                        $queryBuilder->createNamedParameter($this->getBackendUser()->user['uid'], \PDO::PARAM_INT)
                    )
                )
            )
            ->orderBy('item_uid', 'DESC')
            ->addOrderBy('title')
            ->execute()
            ->fetchAll();
    }

    /**
     * Returns a \TYPO3\CMS\Core\Resource\Folder object for saving export files
     * to the server and is also used for uploading import files.
     *
     * @throws \InvalidArgumentException
     * @return \TYPO3\CMS\Core\Resource\Folder|null
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
}
