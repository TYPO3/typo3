<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addTypoScriptSetup('
config.tx_extbase {
    mvc {
        throwPageNotFoundExceptionIfActionCantBeResolved = 0
    }
    persistence {
        enableAutomaticCacheClearing = 1
        updateReferenceIndex = 0
    }
}
');
