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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\CMS\Extensionmanager\Utility\Repository\Helper;

/**
 * Controller for actions relating to update of full extension list from TER
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class UpdateFromTerController extends AbstractController
{
    /**
     * @var Helper
     */
    protected $repositoryHelper;

    /**
     * @var RepositoryRepository
     */
    protected $repositoryRepository;

    /**
     * @var ListUtility
     */
    protected $listUtility;

    /**
     * @var ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @var string
     */
    protected $defaultViewObjectName = JsonView::class;

    /**
     * @param Helper $repositoryHelper
     */
    public function injectRepositoryHelper(Helper $repositoryHelper)
    {
        $this->repositoryHelper = $repositoryHelper;
    }

    /**
     * @param RepositoryRepository $repositoryRepository
     */
    public function injectRepositoryRepository(RepositoryRepository $repositoryRepository)
    {
        $this->repositoryRepository = $repositoryRepository;
    }

    /**
     * @param ListUtility $listUtility
     */
    public function injectListUtility(ListUtility $listUtility)
    {
        $this->listUtility = $listUtility;
    }

    /**
     * @param ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * Update extension list from TER
     *
     * @param bool $forceUpdateCheck
     */
    public function updateExtensionListFromTerAction($forceUpdateCheck = false)
    {
        $updated = false;
        $errorMessage = '';

        if ($this->extensionRepository->countAll() === 0 || $forceUpdateCheck) {
            try {
                $updated = $this->repositoryHelper->updateExtList();
            } catch (\TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException $e) {
                $errorMessage = $e->getMessage();
            }
        }
        $repository = $this->repositoryRepository->findOneTypo3OrgRepository();

        $timeFormat = $this->getLanguageService()->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.updateFromTer.lastUpdate.fullTimeFormat');
        $lastUpdateTime = $repository ? $repository->getLastUpdate() : null;
        if (null === $lastUpdateTime) {
            $lastUpdatedSince = $this->getLanguageService()->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.updateFromTer.never');
            $lastUpdateTime = date($timeFormat);
        } else {
            $lastUpdatedSince = \TYPO3\CMS\Backend\Utility\BackendUtility::calcAge(
                time() - $lastUpdateTime->format('U'),
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
            );
            $lastUpdateTime = $lastUpdateTime->format($timeFormat);
        }
        $this->view->assign('value', [
            'updated' => $updated,
            'lastUpdateTime' => $lastUpdateTime,
            'timeSinceLastUpdate' => $lastUpdatedSince,
            'errorMessage' => $errorMessage
        ]);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
