<?php
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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

/**
 * Abstract core update class contains general core update
 * related methods
 */
abstract class AbstractCoreUpdate extends AbstractAjaxAction
{
    /**
     * @var \TYPO3\CMS\Install\View\JsonView
     */
    protected $view = null;

    /**
     * @var \TYPO3\CMS\Install\Service\CoreUpdateService
     */
    protected $coreUpdateService;

    /**
     * @var \TYPO3\CMS\Install\Status\StatusUtility
     */
    protected $statusUtility;

    /**
     * @var \TYPO3\CMS\Install\Service\CoreVersionService
     */
    protected $coreVersionService;

    /**
     * @param \TYPO3\CMS\Install\View\JsonView $view
     */
    public function injectView(\TYPO3\CMS\Install\View\JsonView $view)
    {
        $this->view = $view;
    }

    /**
     * @param \TYPO3\CMS\Install\Service\CoreUpdateService $coreUpdateService
     */
    public function injectCoreUpdateService(\TYPO3\CMS\Install\Service\CoreUpdateService $coreUpdateService)
    {
        $this->coreUpdateService = $coreUpdateService;
    }

    /**
     * @param \TYPO3\CMS\Install\Status\StatusUtility $statusUtility
     */
    public function injectStatusUtility(\TYPO3\CMS\Install\Status\StatusUtility $statusUtility)
    {
        $this->statusUtility = $statusUtility;
    }

    /**
     * @param \TYPO3\CMS\Install\Service\CoreVersionService $coreVersionService
     */
    public function injectCoreVersionService(\TYPO3\CMS\Install\Service\CoreVersionService $coreVersionService)
    {
        $this->coreVersionService = $coreVersionService;
    }

    /**
     * Initialize the handle action, sets up fluid stuff and assigns default variables.
     *
     * @return void
     * @throws \TYPO3\CMS\Install\Controller\Exception
     */
    protected function initializeHandle()
    {
        if (!$this->coreUpdateService->isCoreUpdateEnabled()) {
            throw new \TYPO3\CMS\Install\Controller\Exception(
                'Core Update disabled in this environment',
                1381609294
            );
        }
        $this->loadExtLocalconfDatabaseAndExtTables();
    }

    /**
     * Find out which version upgrade should be handled. This may
     * be different depending on whether development or regular release.
     *
     * @throws \TYPO3\CMS\Install\Controller\Exception
     * @return string Version to handle, eg. 6.2.2
     */
    protected function getVersionToHandle()
    {
        $getVars = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('install');
        if (!isset($getVars['type'])) {
            throw new \TYPO3\CMS\Install\Controller\Exception(
                'Type must be set to either "regular" or "development"',
                1380975303
            );
        }
        $type = $getVars['type'];
        if ($type === 'development') {
            $versionToHandle = $this->coreVersionService->getYoungestPatchDevelopmentRelease();
        } else {
            $versionToHandle = $this->coreVersionService->getYoungestPatchRelease();
        }
        return $versionToHandle;
    }
}
