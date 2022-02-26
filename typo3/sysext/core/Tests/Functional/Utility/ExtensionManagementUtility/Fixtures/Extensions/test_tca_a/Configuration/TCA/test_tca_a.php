<?php

/* @see ExtensionManagementUtilityTcaRequireTest::extensionManagementUtilityBuildBaseTcaFromSingleFiles */
$TCAScopeTest ??= 'ext:test_tca_a';
if ($TCAScopeTest !== 'ext:test_tca_a') {
    throw new \RuntimeException(
        sprintf(
            'TCA scoped require test failed. %s given, expected %s',
            $TCAScopeTest,
            'ext:test_tca_a'
        ),
        1645916594
    );
}

// no TCA to return, we are testing if local variables do not interfere with other tca requirements.
return [];
