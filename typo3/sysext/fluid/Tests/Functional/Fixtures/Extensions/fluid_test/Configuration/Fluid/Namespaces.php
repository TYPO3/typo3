<?php

return [
    // Extend instance namespace (from settings/additional.php)
    'instance_legacy' => ['TYPO3Tests\\FluidTest\\NamespacesPhp'],
    // Extend existing 3rd-party namespace (from ext_localconf.php)
    'thirdparty_legacy' => ['TYPO3Tests\\FluidTest\\NamespacesPhp'],
    // Extend core namespace (from Namespaces.php)
    'f' => ['TYPO3Tests\\FluidTest\\NamespacesPhp'],
    // Extend existing 3rd-party namespace (from Namespaces.php)
    'thirdparty' => ['TYPO3Tests\\FluidTest\\NamespacesPhp'],
    // Modified by event
    'event' => ['TYPO3Tests\\FluidTest\\NamespacesPhp'],
];
