<?php

// This is a stub file for redirecting the user to the proper Install Tool URL

call_user_func(function () {

    // We leverage the class loader here to get the static functionality of GeneralUtility and HttpUtility.
    // This way we do not need to copy all the code here to cope with correct location header URL generation correctly
    // as those two classes can already correctly deal with all known edge cases.

    require __DIR__ . '/../../vendor/autoload.php';

    // We ensure that possible notices from Core code do not kill our redirect due to PHP output
    error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED));

    \TYPO3\CMS\Core\Utility\HttpUtility::redirect('../sysext/install/Start/Install.php', \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_307);
});
