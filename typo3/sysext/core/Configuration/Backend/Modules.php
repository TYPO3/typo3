<?php

/**
 * Configuration of the main modules (having no parent and no path)
 */
return [
    'content' => [
        'labels' => 'core.modules.content',
        'iconIdentifier' => 'module-web',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'aliases' => ['web'],
    ],
    'media' => [
        'position' => ['after' => 'content'],
        'labels' => 'core.modules.media',
        'iconIdentifier' => 'module-file',
        'navigationComponent' => '@typo3/backend/tree/file-storage-tree-container',
        'aliases' => ['file'],
        'appearance' => [
            'promotesSingleSubmoduleToStandalone' => true,
        ],
    ],
    'site' => [
        'labels' => 'core.modules.site',
        'workspaces' => 'live',
        'iconIdentifier' => 'module-site',
    ],
    'user' => [
        'labels' => 'core.modules.user',
        'iconIdentifier' => 'module-user',
        'workspaces' => '*',
        'appearance' => [
            'renderInModuleMenu' => false,
        ],
    ],
    'admin' => [
        'labels' => 'core.modules.admin',
        'iconIdentifier' => 'module-tools',
        'aliases' => ['tools'],
    ],
    'system' => [
        'labels' => 'core.modules.system',
        'iconIdentifier' => 'module-system',
    ],
    'integrations' => [
        'parent' => 'admin',
        'position' => ['after' => 'permissions_pages'],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/integrations',
        'iconIdentifier' => 'module-integrations',
        'labels' => 'core.modules.integrations',
        'appearance' => [
            'dependsOnSubmodules' => true,
        ],
        'showSubmoduleOverview' => true,
    ],
    'help' => [
        'labels' => 'core.modules.help',
        'iconIdentifier' => 'module-help',
        'appearance' => [
            'renderInModuleMenu' => false,
        ],
    ],
];
