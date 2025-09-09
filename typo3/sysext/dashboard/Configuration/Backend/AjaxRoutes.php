<?php

use TYPO3\CMS\Dashboard\Controller\DashboardAjaxController;

return [
    // Dashboards
    'dashboard_dashboards_get' => [
        'path' => '/dashboard/dashboards/get',
        'target' => DashboardAjaxController::class . '::getDashboards',
        'methods' => ['GET'],
        'inheritAccessFromModule' => 'dashboard',
    ],
    'dashboard_dashboard_add' => [
        'path' => '/dashboard/dashboard/add',
        'target' => DashboardAjaxController::class . '::addDashboard',
        'methods' => ['POST'],
        'inheritAccessFromModule' => 'dashboard',
    ],
    'dashboard_dashboard_edit' => [
        'path' => '/dashboard/dashboard/edit',
        'target' => DashboardAjaxController::class . '::editDashboard',
        'methods' => ['POST'],
        'inheritAccessFromModule' => 'dashboard',
    ],
    'dashboard_dashboard_update' => [
        'path' => '/dashboard/dashboard/update',
        'target' => DashboardAjaxController::class . '::updateDashboard',
        'methods' => ['POST'],
        'inheritAccessFromModule' => 'dashboard',
    ],
    'dashboard_dashboard_delete' => [
        'path' => '/dashboard/dashboard/delete',
        'target' => DashboardAjaxController::class . '::deleteDashboard',
        'methods' => ['POST'],
        'inheritAccessFromModule' => 'dashboard',
    ],

    // Presets
    'dashboard_presets_get' => [
        'path' => '/dashboard/presets/get',
        'target' => DashboardAjaxController::class . '::getPresets',
        'methods' => ['GET'],
        'inheritAccessFromModule' => 'dashboard',
    ],

    // Categories
    'dashboard_categories_get' => [
        'path' => '/dashboard/categories/get',
        'target' => DashboardAjaxController::class . '::getCategories',
        'methods' => ['GET'],
        'inheritAccessFromModule' => 'dashboard',
    ],

    // Widgets
    'dashboard_widget_get' => [
        'path' => '/dashboard/widget/get',
        'target' => DashboardAjaxController::class . '::getWidget',
        'methods' => ['GET'],
        'inheritAccessFromModule' => 'dashboard',
    ],
    'dashboard_widget_add' => [
        'path' => '/dashboard/widget/add',
        'target' => DashboardAjaxController::class . '::addWidget',
        'methods' => ['POST'],
        'inheritAccessFromModule' => 'dashboard',
    ],
    'dashboard_widget_remove' => [
        'path' => '/dashboard/widget/remove',
        'target' => DashboardAjaxController::class . '::removeWidget',
        'methods' => ['POST'],
        'inheritAccessFromModule' => 'dashboard',
    ],
];
