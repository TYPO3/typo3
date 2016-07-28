<?php
namespace TYPO3\CMS\Backend\Controller\Wizard;

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

use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class AbstractWizardController
 */
class AbstractWizardController extends AbstractModule
{
    /**
     * Checks access for element
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @return bool
     */
    protected function checkEditAccess($table, $uid)
    {
        $record = BackendUtility::getRecord($table, $uid);
        BackendUtility::fixVersioningPid($table, $record);
        if (is_array($record)) {
            // If pages:
            if ($table === 'pages') {
                $calculatedPermissions = $this->getBackendUserAuthentication()->calcPerms($record);
                $hasAccess = $calculatedPermissions & Permission::PAGE_EDIT;
            } else {
                // Fetching pid-record first.
                $calculatedPermissions = $this->getBackendUserAuthentication()->calcPerms(
                    BackendUtility::getRecord('pages', $record['pid'])
                );
                $hasAccess = $calculatedPermissions & Permission::CONTENT_EDIT;
            }
            // Check internals regarding access:
            if ($hasAccess) {
                $hasAccess = $this->getBackendUserAuthentication()->recordEditAccessInternals($table, $record);
            }
        } else {
            $hasAccess = false;
        }
        return (bool)$hasAccess;
    }

    /**
     * Returns an instance of BackendUserAuthentication
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns an instance of DocumentTemplate
     *
     * @return DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }
}
