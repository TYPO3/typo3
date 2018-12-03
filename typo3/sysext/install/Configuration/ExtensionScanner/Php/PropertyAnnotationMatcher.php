<?php
return [
    '@inject' => [
        'restFiles' => [
            'Feature-82869-ReplaceInjectWithTYPO3CMSExtbaseAnnotationInject.rst',
            'Deprecation-82869-ReplaceInjectWithTYPO3CMSExtbaseAnnotationInject.rst',
            'Deprecation-86907-DeprecateUsageOfDependencyInjectionWithNonPublicProperties.rst',
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
    '@cascade' => [
        'restFiles' => [
            'Feature-83093-ReplaceCascadeWithTYPO3CMSExtbaseAnnotationORMCascade.rst',
            'Deprecation-83093-ReplaceCascadeWithTYPO3CMSExtbaseAnnotationORMCascade.rst',
        ],
    ],
    '@validate' => [
        'restFiles' => [
            'Feature-83167-ReplaceValidateWithTYPO3CMSExtbaseAnnotationValidate.rst',
            'Deprecation-83167-ReplaceValidateWithTYPO3CMSExtbaseAnnotationValidate.rst',
        ],
    ],
];
