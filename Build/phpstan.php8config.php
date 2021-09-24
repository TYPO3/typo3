<?php

declare(strict_types=1);

$config = [];

if (PHP_MAJOR_VERSION === 8) {
    $config['parameters']['ignoreErrors'] = [
        [
            'message' => '#^Parameter \\#1 \\$image of function imagedestroy expects GdImage, GdImage\\|false given\\.$#',
            'path' => '%currentWorkingDirectory%/typo3/sysext/install/Classes/SystemEnvironment/Check.php',
            'count' => 3,
        ],
        [
            'message' => '#^Parameter \\#6 \\$color of function imagefilledrectangle expects int, int\\|false given\\.$#',
            'path' => '%currentWorkingDirectory%/typo3/sysext/install/Classes/Controller/EnvironmentController.php',
            'count' => 4,
        ],
        [
            'message' => '#^Parameter \\#1 \\$image of function imagefilledrectangle expects GdImage, GdImage\\|false given\\.$#',
            'path' => '%currentWorkingDirectory%/',
            'count' => 4,
        ],
        [
            'message' => '#^Parameter \\#1 \\$image of function imagegif expects GdImage, GdImage\\|false given\\.$#',
            'path' => '%currentWorkingDirectory%/',
            'count' => 1,
        ],
        [
            'message' => '#^Parameter \\#6 \\$color of function imagettftext expects int, int\\|false given\\.$#',
            'path' => '%currentWorkingDirectory%/',
            'count' => 1,
        ],
        [
            'message' => '#^Parameter \\#1 \\$image of function imagettftext expects GdImage, GdImage\\|false given\\.$#',
            'path' => '%currentWorkingDirectory%/',
            'count' => 1,
        ],
        [
            'message' => '#^Parameter \\#1 \\$image of function imagecolorallocate expects GdImage, GdImage\\|false given\\.$#',
            'path' => '%currentWorkingDirectory%/',
            'count' => 6,
        ],
        [
            'message' => '#^Parameter \\#1 \\$semaphore of function sem_release expects SysvSemaphore, resource\\|SysvSemaphore given\\.$#',
            'path' => '%currentWorkingDirectory%/typo3/sysext/core/Classes/Locking/SemaphoreLockStrategy.php',
            'count' => 1,
        ],
        [
            'message' => '#^Parameter \\#1 \\$semaphore of function sem_remove expects SysvSemaphore, resource\\|SysvSemaphore given\\.$#',
            'path' => '%currentWorkingDirectory%/typo3/sysext/core/Classes/Locking/SemaphoreLockStrategy.php',
            'count' => 1,
        ],
        [
            'message' => '#^Parameter \\#1 \\$parser of function xml_parse expects XMLParser, resource given\\.$#',
            'path' => '%currentWorkingDirectory%/typo3/sysext/extensionmanager/Classes/Parser/ExtensionXmlParser.php',
            'count' => 1,
        ],
        [
            'message' => '#^Parameter \\#1 \\$parser of function xml_parser_free expects XMLParser, resource given\\.$#',
            'path' => '%currentWorkingDirectory%/typo3/sysext/extensionmanager/Classes/Parser/ExtensionXmlParser.php',
            'count' => 1,
        ],
        [
            'message' => '#^Parameter \\#1 \\$parser of function xml_parser_set_option expects XMLParser, resource given\\.$#',
            'path' => '%currentWorkingDirectory%/typo3/sysext/extensionmanager/Classes/Parser/ExtensionXmlParser.php',
            'count' => 3,
        ],
        [
            'message' => '#^Parameter \\#1 \\$parser of function xml_set_character_data_handler expects XMLParser, resource given\\.$#',
            'path' => '%currentWorkingDirectory%/typo3/sysext/extensionmanager/Classes/Parser/ExtensionXmlParser.php',
            'count' => 1,
        ],
        [
            'message' => '#^Parameter \\#1 \\$parser of function xml_set_element_handler expects XMLParser, resource given\\.$#',
            'path' => '%currentWorkingDirectory%/typo3/sysext/extensionmanager/Classes/Parser/ExtensionXmlParser.php',
            'count' => 1,
        ],
        [
            'message' => '#^Parameter \\#1 \\$separator of function explode expects non-empty-string, string given\\.$#',
            'path' => '%currentWorkingDirectory%/',
            'count' => 8,
        ],
        [
            'message' => '#^Ternary operator condition is always true.$#',
            'path' => '%currentWorkingDirectory%/typo3/sysext/core/Classes/Utility/GeneralUtility.php',
            'count' => 3,
        ],

    ];
}

return $config;
