<?php
return [
    'dashboard' => [
        'path' => '/ext/dashboard',
        'target' => \TYPO3\CMS\Dashboard\Controller\DashboardController::class . '::handleRequest',
    ],
];
