<?php

defined('TYPO3') or die();

/* @see ExtensionManagementUtilityTcaOverrideRequireTest::extensionManagementUtilityBuildBaseTcaFromSingleFiles */
$TCAOverrideScopeTest ??= 'ext:test_tcaoverride_b';
if ($TCAOverrideScopeTest !== 'ext:test_tcaoverride_b') {
    throw new \RuntimeException(
        sprintf(
            'TCAOverride scoped require test failed. %s given, expected %s',
            $TCAOverrideScopeTest,
            'ext:test_tcaoverride_b'
        ),
        1645916622
    );
}
