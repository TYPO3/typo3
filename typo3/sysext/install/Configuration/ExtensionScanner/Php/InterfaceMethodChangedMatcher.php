<?php
return [
    // Arguments removed from interface methods
    // @todo: Add the interface name to the definition and refactor matcher
    'like' => [
        'newNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'getHashedPassword' => [
        'newNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85804-SaltedPasswordHashClassDeprecations.rst'
        ],
    ]
];
