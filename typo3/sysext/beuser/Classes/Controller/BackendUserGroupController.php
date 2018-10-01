<?php
namespace TYPO3\CMS\Beuser\Controller;

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

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Backend module user group administration controller
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendUserGroupController extends ActionController
{
    /**
     * @var \TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository
     */
    protected $backendUserGroupRepository;

    /**
     * @param \TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository $backendUserGroupRepository
     */
    public function injectBackendUserGroupRepository(\TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository $backendUserGroupRepository)
    {
        $this->backendUserGroupRepository = $backendUserGroupRepository;
    }

    /**
     * Displays all BackendUserGroups
     */
    public function indexAction()
    {
        $this->view->assignMultiple(
            [
                'shortcutLabel' => 'backendUserGroupsMenu',
                'backendUserGroups' => $this->backendUserGroupRepository->findAll(),
            ]
        );
    }
}
