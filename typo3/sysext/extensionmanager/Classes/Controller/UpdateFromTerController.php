<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Controller for actions relating to update of full extension list from TER
 */
class UpdateFromTerController extends AbstractController
{
    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper
     */
    protected $repositoryHelper;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository
     */
    protected $repositoryRepository;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
     */
    protected $listUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @var JsonView
     */
    protected $defaultViewObjectName = JsonView::class;

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper $repositoryHelper
     */
    public function injectRepositoryHelper(\TYPO3\CMS\Extensionmanager\Utility\Repository\Helper $repositoryHelper)
    {
        $this->repositoryHelper = $repositoryHelper;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository $repositoryRepository
     */
    public function injectRepositoryRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository $repositoryRepository)
    {
        $this->repositoryRepository = $repositoryRepository;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
     */
    public function injectListUtility(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility)
    {
        $this->listUtility = $listUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository)
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
        /** @var $repository \TYPO3\CMS\Extensionmanager\Domain\Model\Repository */
        $repository = $this->repositoryRepository->findByUid((int)$this->settings['repositoryUid']);

        $timeFormat = $this->getLanguageService()->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.updateFromTer.lastUpdate.fullTimeFormat');
        $lastUpdateTime = $repository ? $repository->getLastUpdate() : null;
        if (null === $lastUpdateTime) {
            $lastUpdatedSince = $this->getLanguageService()->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.updateFromTer.never');
            $lastUpdateTime = date($timeFormat);
        } else {
            $lastUpdatedSince = \TYPO3\CMS\Backend\Utility\BackendUtility::calcAge(
                time() - $lastUpdateTime->format('U'),
                $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
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
