<?php
namespace TYPO3\CMS\Install\FolderStructure;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Core\Bootstrap;

/**
 * Factory returns default folder structure object hierarchy
 */
class DefaultFactory
{
    /**
     * Get default structure object hierarchy
     *
     * @throws Exception
     * @return RootNode
     * @TODO: Use objectManager instead of new (will be injected)
     */
    public function getStructure()
    {
        $rootNode = new RootNode($this->getDefaultStructureDefinition(), null);
        if (!($rootNode instanceof RootNodeInterface)) {
            throw new Exception(
                'Root node must implement RootNodeInterface',
                1366139176
            );
        }
        $structureFacade = new StructureFacade($rootNode);
        if (!($structureFacade instanceof StructureFacadeInterface)) {
            throw new Exception(
                'Structure facade must implement StructureFacadeInterface',
                1366535827
            );
        }
        return $structureFacade;
    }

    /**
     * Default definition of folder and file structure with dynamic
     * permission settings
     *
     * @return array
     */
    protected function getDefaultStructureDefinition()
    {
        $filePermission = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'];
        $directoryPermission = $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'];

        if (Bootstrap::usesComposerClassLoading()) {
            // In composer mode the links are configurable and might even be actual files
            // Ignore this structure in this case
            $structureAdditional = [];
        } else {
            $structureAdditional = [
                [
                    'name' => 'index.php',
                    'type' => LinkNode::class,
                    'target' => 'typo3_src/index.php',
                ],
                [
                    'name' => 'typo3',
                    'type' => LinkNode::class,
                    'target' => 'typo3_src/typo3',
                ],
                [
                    'name' => 'typo3_src',
                    'type' => LinkNode::class,
                ],
            ];
        }
        $structureBase = [
            [
                'name' => 'typo3temp',
                'type' => DirectoryNode::class,
                'targetPermission' => $directoryPermission,
                'children' => [
                    [
                        'name' => 'index.html',
                        'type' => FileNode::class,
                        'targetPermission' => $filePermission,
                        'targetContent' => '',
                    ],
                    [
                        'name' => 'compressor',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                    ],
                    [
                        'name' => 'cs',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                    ],
                    [
                        'name' => 'Cache',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                    ],
                    [
                        'name' => 'GB',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                    ],
                    [
                        'name' => 'llxml',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                    ],
                    [
                        'name' => 'locks',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                    ],
                    [
                        'name' => 'pics',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                    ],
                    [
                        'name' => 'sprites',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                    ],
                    [
                        'name' => 'temp',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                    ],
                    [
                        'name' => '_processed_',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                    ],
                ],
            ],
            [
                'name' => 'typo3conf',
                'type' => DirectoryNode::class,
                'targetPermission' => $directoryPermission,
                'children' => [
                    [
                        'name' => 'ext',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                    ],
                    [
                        'name' => 'l10n',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                    ],
                ],
            ],
            [
                'name' => 'uploads',
                'type' => DirectoryNode::class,
                'targetPermission' => $directoryPermission,
                'children' => [
                    [
                        'name' => 'index.html',
                        'type' => FileNode::class,
                        'targetPermission' => $filePermission,
                        'targetContentFile' => PATH_site . 'typo3/sysext/install/Resources/Private/FolderStructureTemplateFiles/uploads-index.html',
                    ],
                    [
                        'name' => 'media',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                        'children' => [
                            [
                                'name' => 'index.html',
                                'type' => FileNode::class,
                                'targetPermission' => $filePermission,
                                'targetContent' => '',
                            ],
                        ],
                    ],
                    [
                        'name' => 'pics',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                        'children' => [
                            [
                                'name' => 'index.html',
                                'type' => FileNode::class,
                                'targetPermission' => $filePermission,
                                'targetContent' => '',
                            ],
                        ],
                    ],
                    [
                        'name' => 'tx_felogin',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                    ],
                ],
            ],
            [
                'name' => !empty($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']) ? rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/') : 'fileadmin',
                'type' => DirectoryNode::class,
                'targetPermission' => $directoryPermission,
                'children' => [
                    [
                        'name' => '_temp_',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                        'children' => [
                            [
                                'name' => '.htaccess',
                                'type' => FileNode::class,
                                'targetPermission' => $filePermission,
                                'targetContentFile' => PATH_site . 'typo3/sysext/install/Resources/Private/FolderStructureTemplateFiles/fileadmin-temp-htaccess',
                            ],
                            [
                                'name' => 'index.html',
                                'type' => FileNode::class,
                                'targetPermission' => $filePermission,
                                'targetContentFile' => PATH_site . 'typo3/sysext/install/Resources/Private/FolderStructureTemplateFiles/fileadmin-temp-index.html',
                            ],
                        ],
                    ],
                    [
                        'name' => 'user_upload',
                        'type' => DirectoryNode::class,
                        'targetPermission' => $directoryPermission,
                        'children' => [
                            [
                                'name' => '_temp_',
                                'type' => DirectoryNode::class,
                                'targetPermission' => $directoryPermission,
                                'children' => [
                                    [
                                        'name' => 'index.html',
                                        'type' => FileNode::class,
                                        'targetPermission' => $filePermission,
                                        'targetContent' => '',
                                    ],
                                    [
                                        'name' => 'importexport',
                                        'type' => DirectoryNode::class,
                                        'targetPermission' => $directoryPermission,
                                        'children' => [
                                            [
                                                'name' => '.htaccess',
                                                'type' => FileNode::class,
                                                'targetPermission' => $filePermission,
                                                'targetContentFile' => PATH_site . 'typo3/sysext/install/Resources/Private/FolderStructureTemplateFiles/fileadmin-user_upload-temp-importexport-htaccess',
                                            ],
                                            [
                                                'name' => 'index.html',
                                                'type' => FileNode::class,
                                                'targetPermission' => $filePermission,
                                                'targetContentFile' => PATH_site . 'typo3/sysext/install/Resources/Private/FolderStructureTemplateFiles/fileadmin-temp-index.html',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'name' => 'index.html',
                                'type' => FileNode::class,
                                'targetPermission' => $filePermission,
                                'targetContent' => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return [
            // Cut off trailing forward / from PATH_site, so root node has no trailing slash like all others
            'name' => substr(PATH_site, 0, -1),
            'targetPermission' => $directoryPermission,
            'children' => array_merge($structureAdditional, $structureBase)
        ];
    }
}
