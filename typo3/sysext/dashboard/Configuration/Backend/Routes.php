<?php

return [
    'dashboard' => [
        'path' => '/dashboard',
        'target' => \TYPO3\CMS\Dashboard\Controller\DashboardController::class . '::handleRequest',
    ],
];
