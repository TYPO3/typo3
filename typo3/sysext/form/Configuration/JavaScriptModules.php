<?php

return [
    'dependencies' => [
        'backend',
        'core',
    ],
    'tags' => [
        'backend.contextmenu',
    ],
    'imports' => [
        '@typo3/form/Backend/' => 'EXT:form/Resources/Public/JavaScript/Backend/',
    ],
];
