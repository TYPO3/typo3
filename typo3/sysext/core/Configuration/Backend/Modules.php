<?php

/**
 * Configuration of the main modules (having no parent and no path)
 */
return [
    'content' => [
        'labels' => 'core.modules.content',
        'iconIdentifier' => 'modulegroup-web',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'aliases' => ['web'],
    ],
    'media' => [
        'labels' => 'core.modules.media',
        'iconIdentifier' => 'modulegroup-file',
        'navigationComponent' => '@typo3/backend/tree/file-storage-tree-container',
        'aliases' => ['file'],
    ],
    'site' => [
        'labels' => 'core.modules.site',
        'workspaces' => 'live',
        'iconIdentifier' => 'modulegroup-site',
    ],
    'user' => [
        'labels' => 'core.modules.user',
        'iconIdentifier' => 'modulegroup-user',
        'workspaces' => '*',
        'appearance' => [
            'renderInModuleMenu' => false,
        ],
    ],
    'tools' => [
        'labels' => 'core.modules.tools',
        'iconIdentifier' => 'modulegroup-tools',
    ],
    'system' => [
        'labels' => 'core.modules.system',
        'iconIdentifier' => 'modulegroup-system',
    ],
    'integrations' => [
        'parent' => 'tools',
        'position' => ['after' => 'permissions_pages'],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/integrations',
        'iconIdentifier' => 'module-webhooks',
        'labels' => 'core.modules.integrations',
        'appearance' => [
            'dependsOnSubmodules' => true,
        ],
        'showSubmoduleOverview' => true,
    ],
    'help' => [
        'labels' => 'core.modules.help',
        'iconIdentifier' => 'modulegroup-help',
        'appearance' => [
            'renderInModuleMenu' => false,
        ],
    ],
];
