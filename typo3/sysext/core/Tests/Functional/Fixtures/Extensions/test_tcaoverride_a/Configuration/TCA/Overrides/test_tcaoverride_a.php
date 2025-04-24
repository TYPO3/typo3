<?php

defined('TYPO3') or die();

/* @see ExtensionManagementUtilityTcaOverrideRequireTest::extensionManagementUtilityBuildBaseTcaFromSingleFiles */
$TCAOverrideScopeTest ??= 'ext:test_tcaoverride_a';
if ($TCAOverrideScopeTest !== 'ext:test_tcaoverride_a') {
    throw new \RuntimeException(
        sprintf(
            'TCAOverride scoped require test failed. %s given, expected %s',
            $TCAOverrideScopeTest,
            'ext:test_tcaoverride_a'
        ),
        1645916610
    );
}
