<?php

use TYPO3\CMS\Styleguide\Controller\BackendController;

/**
 * Definitions for modules provided by EXT:styleguide
 */
return [
    'help_StyleguideStyleguide' => [
        'parent' => 'help',
        'access' => 'user',
        'iconIdentifier' => 'module-styleguide',
        'labels' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang_styleguide.xlf',
        'extensionName' => 'Styleguide',
        'controllerActions' => [
            BackendController::class => 'index, typography, trees, tables, buttons, infobox, avatar, flashMessages, tca, tcaCreate, tcaDelete, icons, tab, modal, accordion, pagination, frontendCreate, frontendDelete',
        ],
    ],
];
