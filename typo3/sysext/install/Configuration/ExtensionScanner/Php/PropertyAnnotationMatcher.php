<?php
return [
    '@inject' => [
        'restFiles' => [
            'Feature-82869-ReplaceInjectWithTYPO3CMSExtbaseAnnotationInject.rst',
            'Deprecation-82869-ReplaceInjectWithTYPO3CMSExtbaseAnnotationInject.rst',
        ],
    ],
    '@lazy' => [
        'restFiles' => [
            'Feature-83078-ReplaceLazyWithTYPO3CMSExtbaseAnnotationORMLazy.rst',
            'Deprecation-83078-ReplaceLazyWithTYPO3CMSExtbaseAnnotationORMLazy.rst',
        ],
    ],
    '@transient' => [
        'restFiles' => [
            'Feature-83092-ReplaceTransientWithTYPO3CMSExtbaseAnnotationORMTransient.rst',
            'Deprecation-83092-ReplaceTransientWithTYPO3CMSExtbaseAnnotationORMTransient.rst',
        ],
    ],
];
