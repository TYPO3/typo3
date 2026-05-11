<?php

return [
    // Abstract method implementation definitions
    'TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper::renderStatic' => [
        'restFiles' => [
            'Deprecation-104789-RenderStaticForFluidViewHelpers.rst',
            'Breaking-108148-Fluid50.rst',
        ],
    ],
    'TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper::postParseEvent' => [
        'restFiles' => [
            'Breaking-108148-Fluid50.rst',
        ],
    ],
    'TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper->validateArguments' => [
        'restFiles' => [
            'Breaking-108148-Fluid50.rst',
        ],
    ],
    'TYPO3\CMS\Scheduler\Task\AbstractTask->getTaskTitle' => [
        'restFiles' => [
            'Breaking-109783-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Scheduler\Task\AbstractTask->getTaskDescription' => [
        'restFiles' => [
            'Breaking-109783-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Scheduler\Task\AbstractTask->getTaskClassName' => [
        'restFiles' => [
            'Breaking-109783-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
];
