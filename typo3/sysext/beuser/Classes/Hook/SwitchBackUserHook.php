<?php
namespace TYPO3\CMS\Beuser\Hook;

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

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Backend user switchback, for logoff_pre_processing hook within
 * \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication class
 * @internal This class is a TYPO3 core-internal hook implementation and is not considered part of the Public TYPO3 API.
 */
class SwitchBackUserHook
{
    /**
     * Switch backend user session.
     *
     * @param array $params
     * @param AbstractUserAuthentication $authentication
     * @see AbstractUserAuthentication
     */
    public function switchBack($params, AbstractUserAuthentication $authentication)
    {
        if ($this->isAHandledBackendSession($authentication)) {
            $objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
            $backendUserSessionRepository = $objectManager->get(\TYPO3\CMS\Beuser\Domain\Repository\BackendUserSessionRepository::class);
            $backendUserSessionRepository->switchBackToOriginalUser($authentication);
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
            HttpUtility::redirect((string)$uriBuilder->buildUriFromRoute('main'));
        }
    }

    /**
     * Check if the given authentication object is a backend session and
     * contains all necessary information to allow switching.
     *
     * @param AbstractUserAuthentication $authentication
     * @return bool
     */
    protected function isAHandledBackendSession(AbstractUserAuthentication $authentication)
    {
        return ($authentication instanceof BackendUserAuthentication)
            && is_array($authentication->user)
            && (int)$authentication->user['uid'] > 0
            && (int)$authentication->user['ses_backuserid'] > 0;
    }
}
