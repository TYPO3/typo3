<?php
return [
    // Get the content of a widgets
    'ext-dashboard-get-widget-content' => [
        'path' => '/ext/dashboard/widget/content',
        'target' => TYPO3\CMS\Dashboard\Controller\WidgetAjaxController::class . '::getContent'
    ],
    // Save positions of the widgets
    'ext-dashboard-save-widget-positions' => [
        'path' => '/ext/dashboard/widget/positions/save',
        'target' => TYPO3\CMS\Dashboard\Controller\WidgetAjaxController::class . '::savePositions'
    ],
];
