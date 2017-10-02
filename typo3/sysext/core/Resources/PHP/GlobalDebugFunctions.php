<?php
// Short-hand debug function
// If you wish to use the debug()-function, and it does not output something,
// please edit the IP mask in TYPO3_CONF_VARS
function debug($variable = '', $title = null, $group = null)
{
    if (!\TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP(
        \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'),
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
    )
    ) {
        return;
    }
    \TYPO3\CMS\Core\Utility\DebugUtility::debug($variable, $title, $group);
}
