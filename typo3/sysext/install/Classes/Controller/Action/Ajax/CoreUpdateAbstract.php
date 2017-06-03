<?php
declare(strict_types=1);
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\Exception;
use TYPO3\CMS\Install\Service\CoreUpdateService;
use TYPO3\CMS\Install\Service\CoreVersionService;
use TYPO3\CMS\Install\Status\StatusUtility;
use TYPO3\CMS\Install\View\JsonView;

/**
 * Abstract core update class contains general core update
 * related methods
 */
abstract class CoreUpdateAbstract extends AbstractAjaxAction
{
    /**
     * @var CoreUpdateService
     */
    protected $coreUpdateService;

    /**
     * @var StatusUtility
     */
    protected $statusUtility;

    /**
     * @var CoreVersionService
     */
    protected $coreVersionService;

    /**
     * @param JsonView $view
     * @param CoreUpdateService $coreUpdateService
     * @param StatusUtility $statusUtility
     * @param CoreVersionService $coreVersionService
     */
    public function __construct(
        JsonView $view = null,
        CoreUpdateService $coreUpdateService = null,
        StatusUtility $statusUtility = null,
        CoreVersionService $coreVersionService = null)
    {
        parent::__construct($view);
        $this->coreUpdateService = $coreUpdateService ?: GeneralUtility::makeInstance(CoreUpdateService::class);
        $this->statusUtility = $statusUtility ?: GeneralUtility::makeInstance(StatusUtility::class);
        $this->coreVersionService = $coreVersionService ?: GeneralUtility::makeInstance(CoreVersionService::class);
    }

    /**
     * Initialize the handle action, sets up fluid stuff and assigns default variables.
     *
     * @throws Exception
     */
    protected function initializeHandle()
    {
        if (!$this->coreUpdateService->isCoreUpdateEnabled()) {
            throw new Exception(
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
     * @throws Exception
     * @return string Version to handle, eg. 6.2.2
     */
    protected function getVersionToHandle(): string
    {
        $getVars = GeneralUtility::_GET('install');
        if (!isset($getVars['type'])) {
            throw new Exception(
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
