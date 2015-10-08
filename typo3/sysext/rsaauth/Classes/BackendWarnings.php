<?php
namespace TYPO3\CMS\Rsaauth;

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
 * This class contains a hook to the backend warnings collection. It checks
 * RSA configuration and create a warning if the configuration is wrong.
 */
class BackendWarnings
{
    /**
     * Checks RSA configuration and creates warnings if necessary.
     *
     * @param array $warnings Warnings
     * @return void
     */
    public function displayWarningMessages_postProcess(array &$warnings)
    {
        $backend = \TYPO3\CMS\Rsaauth\Backend\BackendFactory::getBackend();
        if ($backend instanceof \TYPO3\CMS\Rsaauth\Backend\CommandLineBackend) {
            // Not using the PHP extension!
            $lang = $this->getLanguageService();
            $warnings['rsaauth_cmdline'] = $lang->sL('LLL:EXT:rsaauth/Resources/Private/Language/locallang.xlf:hook_using_cmdline');
            // Check the path
            $extconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['rsaauth']);
            $path = trim($extconf['temporaryDirectory']);
            if ($path == '') {
                // Path is empty
                $warnings['rsaauth'] = $lang->sL('LLL:EXT:rsaauth/Resources/Private/Language/locallang.xlf:hook_empty_directory');
            } elseif (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($path)) {
                // Path is not absolute
                $warnings['rsaauth'] = $lang->sL('LLL:EXT:rsaauth/Resources/Private/Language/locallang.xlf:hook_directory_not_absolute');
            } elseif (!@is_dir($path)) {
                // Path does not represent a directory
                $warnings['rsaauth'] = $lang->sL('LLL:EXT:rsaauth/Resources/Private/Language/locallang.xlf:hook_directory_not_exist');
            } elseif (!@is_writable($path)) {
                // Directory is not writable
                $warnings['rsaauth'] = $lang->sL('LLL:EXT:rsaauth/Resources/Private/Language/locallang.xlf:hook_directory_not_writable');
            } elseif (substr($path, 0, strlen(PATH_site)) == PATH_site) {
                // Directory is inside the site root
                $warnings['rsaauth'] = $lang->sL('LLL:EXT:rsaauth/Resources/Private/Language/locallang.xlf:hook_directory_inside_siteroot');
            }
        }
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
