<?php

/* @see ExtensionManagementUtilityTcaRequireTest::extensionManagementUtilityBuildBaseTcaFromSingleFiles */
$TCAScopeTest ??= 'ext:test_tca_b';
if ($TCAScopeTest !== 'ext:test_tca_b') {
    throw new \RuntimeException(
        sprintf(
            'TCA scoped require test failed. %s given, expected %s',
            $TCAScopeTest,
            'ext:test_tca_b'
        ),
        1645916633
    );
}

// no TCA to return, we are testing if local variables do not interfere with other tca requirements.
return [];
