<?php

return [
    // Get the content of a widget
    'dashboard_get_widget_content' => [
        'path' => '/dashboard/widget/content',
        'target' => TYPO3\CMS\Dashboard\Controller\WidgetAjaxController::class . '::getContent',
    ],
    // Save positions of the widgets
    'dashboard_save_widget_positions' => [
        'path' => '/dashboard/widget/positions/save',
        'target' => TYPO3\CMS\Dashboard\Controller\WidgetAjaxController::class . '::savePositions',
    ],
];
