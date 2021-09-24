<?php

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

namespace TYPO3\CMS\Extensionmanager\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

/**
 * Controller for actions relating to update of full extension list from TER
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class UpdateFromTerController extends AbstractController
{
    protected RemoteRegistry $remoteRegistry;
    protected ListUtility $listUtility;
    protected ExtensionRepository $extensionRepository;

    /**
     * @var string
     */
    protected $defaultViewObjectName = JsonView::class;

    public function __construct(
        RemoteRegistry $remoteRegistry,
        ListUtility $listUtility,
        ExtensionRepository $extensionRepository
    ) {
        $this->remoteRegistry = $remoteRegistry;
        $this->listUtility = $listUtility;
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * Update extension list from TER
     *
     * @param bool $forceUpdateCheck
     */
    public function updateExtensionListFromTerAction($forceUpdateCheck = false): ResponseInterface
    {
        $updated = false;
        $errorMessage = '';
        $lastUpdate = null;

        $emptyExtensionList = $this->extensionRepository->countAll() === 0;
        try {
            foreach ($this->remoteRegistry->getListableRemotes() as $remote) {
                if ((!$updated && $emptyExtensionList) || $forceUpdateCheck) {
                    $remote->getAvailablePackages($forceUpdateCheck);
                    $updated = $forceUpdateCheck;
                }
                if ($lastUpdate === null || $lastUpdate < $remote->getLastUpdate()) {
                    $lastUpdate = $remote->getLastUpdate();
                }
            }
        } catch (ExtensionManagerException $e) {
            $errorMessage = $e->getMessage();
        }

        $timeFormat = $this->getLanguageService()->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.updateFromTer.lastUpdate.fullTimeFormat');
        if ($lastUpdate === null) {
            $lastUpdatedSince = $this->getLanguageService()->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.updateFromTer.never');
            $lastUpdateTime = date($timeFormat);
        } else {
            $lastUpdatedSince = BackendUtility::calcAge(
                $GLOBALS['EXEC_TIME'] - $lastUpdate->format('U'),
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
            );
            $lastUpdateTime = $lastUpdate->format($timeFormat);
        }
        $this->view->assign('value', [
            'updated' => $updated,
            'lastUpdateTime' => $lastUpdateTime,
            'timeSinceLastUpdate' => $lastUpdatedSince,
            'errorMessage' => $errorMessage,
        ]);

        return $this->jsonResponse();
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
