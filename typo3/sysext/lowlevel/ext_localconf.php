<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    // Setting up scripts that can be run from the cli_dispatch.phpsh script.
    // Using cliKeys is deprecated as of TYPO3 v8 and will be removed in TYPO3 v9, use Configuration/Commands.php instead
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['lowlevel_cleaner'] = [
        function () {
            $cleanerObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Lowlevel\CleanerCommand::class);
            $cleanerObj->cli_main($_SERVER['argv']);
        }
    ];
}
