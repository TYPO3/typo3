<?php
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

/**
 * This file contains the default array definition that is
 * later populated as $GLOBALS['TYPO3_CONF_VARS']
 *
 * The description of the various options is stored in the DefaultConfigurationDescription.php file
 */
return [
    'DB' => [
        'additionalQueryRestrictions' => [],
    ],
    'GFX' => [ // Configuration of the image processing features in TYPO3. 'IM' and 'GD' are short for ImageMagick and GD library respectively.
        'thumbnails' => true,
        'thumbnails_png' => true,
        'gif_compress' => true,
        'imagefile_ext' => 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai,svg',
        'gdlib' => true,
        'gdlib_png' => false,
        'processor_enabled' => true,
        'processor_path' => '/usr/bin/',
        'processor_path_lzw' => '/usr/bin/',
        'processor' => 'ImageMagick',
        'processor_effects' => 0,
        'processor_allowUpscaling' => true,
        'processor_allowFrameSelection' => true,
        'processor_allowTemporaryMasksAsPng' => false,
        'processor_stripColorProfileByDefault' => true,
        'processor_stripColorProfileCommand' => '+profile \'*\'',
        'processor_colorspace' => 'RGB',
        'jpg_quality' => 70,
        'png_truecolor' => true,
    ],
    'SYS' => [
        // System related concerning both frontend and backend.
        'lang' => [
            'format' => [
                'priority' => 'xlf,xml'
            ],
            'parser' => [
                'xml' => \TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser::class,
                'xlf' => \TYPO3\CMS\Core\Localization\Parser\XliffParser::class
            ]
        ],
        'session' => [
            'BE' => [
                'backend' => \TYPO3\CMS\Core\Session\Backend\DatabaseSessionBackend::class,
                'options' => [
                    'table' => 'be_sessions'
                ]
            ],
            'FE' => [
                'backend' => \TYPO3\CMS\Core\Session\Backend\DatabaseSessionBackend::class,
                'options' => [
                    'table' => 'fe_sessions',
                    'has_anonymous' => true,
                ]
            ]
        ],
        'fileCreateMask' => '0664',
        'folderCreateMask' => '2775',
        'createGroup' => '',
        'sitename' => 'TYPO3',
        'encryptionKey' => '',
        'cookieDomain' => '',
        'cookieSecure' => 0,
        'doNotCheckReferer' => false,
        'recursiveDomainSearch' => false,
        'trustedHostsPattern' => 'SERVER_NAME',
        'devIPmask' => '127.0.0.1,::1',
        'ipAnonymization' => 1,
        'sqlDebug' => 0,
        'enable_DLOG' => false,
        'ddmmyy' => 'd-m-y',
        'hhmm' => 'H:i',
        'USdateFormat' => false,
        'loginCopyrightWarrantyProvider' => '',
        'loginCopyrightWarrantyURL' => '',
        'textfile_ext' => 'txt,ts,typoscript,html,htm,css,tmpl,js,sql,xml,csv,xlf,yaml,yml',
        'mediafile_ext' => 'gif,jpg,jpeg,bmp,png,pdf,svg,ai,mp3,wav,mp4,ogg,flac,opus,webm,youtube,vimeo',
        'binPath' => '',
        'binSetup' => '',
        'no_pconnect' => true,
        'dbClientCompress' => false,
        'setDBinit' => '',
        'setMemoryLimit' => 0,
        'phpTimeZone' => '',
        'systemLog' => '',
        'systemLogLevel' => 0,
        'enableDeprecationLog' => '',
        'UTF8filesystem' => false,
        'systemLocale' => '',
        'reverseProxyIP' => '',
        'reverseProxyHeaderMultiValue' => 'none',
        'reverseProxyPrefix' => '',
        'reverseProxySSL' => '',
        'reverseProxyPrefixSSL' => '',
        'caching' => [
            'cacheConfigurations' => [
                // The cache_core cache is is for core php code only and must
                // not be abused by third party extensions.
                'cache_core' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system']
                ],
                'cache_hash' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [],
                    'groups' => ['pages']
                ],
                'cache_pages' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'compression' => true
                    ],
                    'groups' => ['pages']
                ],
                'cache_pagesection' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'compression' => true,
                        'defaultLifetime' => 2592000, // 30 days; set this to a lower value in case your cache gets too big
                    ],
                    'groups' => ['pages']
                ],
                'cache_phpcode' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\FileBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system']
                ],
                'cache_runtime' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class,
                    'options' => [],
                    'groups' => []
                ],
                'cache_rootline' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'defaultLifetime' => 2592000, // 30 days; set this to a lower value in case your cache gets too big
                    ],
                    'groups' => ['pages']
                ],
                'cache_imagesizes' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['lowlevel'],
                ],
                'assets' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system']
                ],
                'l10n' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system']
                ],
                'fluid_template' => [
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                    'frontend' => \TYPO3\CMS\Fluid\Core\Cache\FluidTemplateCache::class,
                    'groups' => ['system'],
                ],
                'extbase_object' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system']
                ],
                'extbase_reflection' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system']
                ],
                'extbase_datamapfactory_datamap' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'groups' => ['system'],
                ],
            ],
        ],
        'defaultCategorizedTables' => '',
        'displayErrors' => -1,
        'productionExceptionHandler' => \TYPO3\CMS\Core\Error\ProductionExceptionHandler::class,
        'debugExceptionHandler' => \TYPO3\CMS\Core\Error\DebugExceptionHandler::class,
        'errorHandler' => \TYPO3\CMS\Core\Error\ErrorHandler::class,
        'errorHandlerErrors' => E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR),
        'exceptionalErrors' => E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR | E_DEPRECATED | E_WARNING | E_USER_ERROR | E_USER_NOTICE | E_USER_WARNING),
        'enable_errorDLOG' => 0,
        'enable_exceptionDLOG' => 0, // Boolean: If set,
        'syslogErrorReporting' => E_ALL & ~(E_STRICT | E_NOTICE),
        'belogErrorReporting' => E_ALL & ~(E_STRICT | E_NOTICE),
        'locallangXMLOverride' => [], // For extension/overriding of the arrays in 'locallang' files in frontend  and backend. See 'Inside TYPO3' for more information.
        'generateApacheHtaccess' => 1,
        'Objects' => [],
        'fal' => [
            'registeredDrivers' => [
                'Local' => [
                    'class' => \TYPO3\CMS\Core\Resource\Driver\LocalDriver::class,
                    'shortName' => 'Local',
                    'flexFormDS' => 'FILE:EXT:core/Configuration/Resource/Driver/LocalDriverFlexForm.xml',
                    'label' => 'Local filesystem'
                ]
            ],
            'defaultFilterCallbacks' => [
                [
                    \TYPO3\CMS\Core\Resource\Filter\FileNameFilter::class,
                    'filterHiddenFilesAndFolders'
                ]
            ],
            'processingTaskTypes' => [
                'Image.Preview' => \TYPO3\CMS\Core\Resource\Processing\ImagePreviewTask::class,
                'Image.CropScaleMask' => \TYPO3\CMS\Core\Resource\Processing\ImageCropScaleMaskTask::class
            ],
            'registeredCollections' => [
                'static' => \TYPO3\CMS\Core\Resource\Collection\StaticFileCollection::class,
                'folder' => \TYPO3\CMS\Core\Resource\Collection\FolderBasedFileCollection::class,
                'category' => \TYPO3\CMS\Core\Resource\Collection\CategoryBasedFileCollection::class,
            ],
            'onlineMediaHelpers' => [
                'youtube' => \TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\YouTubeHelper::class,
                'vimeo' => \TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\VimeoHelper::class,
            ],
        ],
        'IconFactory' => [
            'recordStatusMapping' => [
                'hidden' => 'overlay-hidden',
                'fe_group' => 'overlay-restricted',
                'starttime' => 'overlay-scheduled',
                'endtime' => 'overlay-endtime',
                'futureendtime' => 'overlay-scheduled',
                'readonly' => 'overlay-readonly',
                'deleted' => 'overlay-deleted',
                'missing' => 'overlay-missing',
                'translated' => 'overlay-translated',
                'protectedSection' => 'overlay-includes-subpages'
            ],
            'overlayPriorities' => [
                'hidden',
                'starttime',
                'endtime',
                'futureendtime',
                'protectedSection',
                'fe_group'
            ]
        ],
        'FileInfo' => [
            // Static mapping for file extensions to mime types.
            // In special cases the mime type is not detected correctly.
            // Use this array only if the automatic detection does not work correct!
            'fileExtensionToMimeType' => [
                'svg' => 'image/svg+xml',
                'youtube' => 'video/youtube',
                'vimeo' => 'video/vimeo',
            ]
        ],
        'fluid' => [
            'interceptors' => [],
            'preProcessors' => [
                \TYPO3Fluid\Fluid\Core\Parser\TemplateProcessor\EscapingModifierTemplateProcessor::class,
                \TYPO3Fluid\Fluid\Core\Parser\TemplateProcessor\PassthroughSourceModifierTemplateProcessor::class,
                \TYPO3Fluid\Fluid\Core\Parser\TemplateProcessor\NamespaceDetectionTemplateProcessor::class
            ],
            'expressionNodeTypes' => [
                \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\CastingExpressionNode::class,
                \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\MathExpressionNode::class,
                \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\TernaryExpressionNode::class
            ],
            'namespaces' => [
                'core' => [
                    'TYPO3\\CMS\\Core\\ViewHelpers'
                ],
                'f' => [
                    'TYPO3Fluid\\Fluid\\ViewHelpers',
                    'TYPO3\\CMS\\Fluid\\ViewHelpers'
                ]
            ]
        ],
        'linkHandler' => [ // Array: Available link types, class which implement the LinkHandling interface
            'page'   => \TYPO3\CMS\Core\LinkHandling\PageLinkHandler::class,
            'file'   => \TYPO3\CMS\Core\LinkHandling\FileLinkHandler::class,
            'folder' => \TYPO3\CMS\Core\LinkHandling\FolderLinkHandler::class,
            'url'    => \TYPO3\CMS\Core\LinkHandling\UrlLinkHandler::class,
            'email'  => \TYPO3\CMS\Core\LinkHandling\EmailLinkHandler::class,
            'record' => \TYPO3\CMS\Core\LinkHandling\RecordLinkHandler::class,
        ],
        'livesearch' => [],  // Array: keywords used for commands to search for specific tables
        'isInitialInstallationInProgress' => false,
        'isInitialDatabaseImportDone' => true,
        'formEngine' => [
            'nodeRegistry' => [], // Array: Registry to add or overwrite FormEngine nodes. Main key is a timestamp of the date when an entry is added, sub keys type, priority and class are required. Class must implement TYPO3\CMS\Backend\Form\NodeInterface.
            'nodeResolver' => [], // Array: Additional node resolver. Main key is a timestamp of the date when an entry is added, sub keys type, priority and class are required. Class must implement TYPO3\CMS\Backend\Form\NodeResolverInterface.
            'formDataGroup' => [ // Array: Registry of form data providers for form data groups
                'tcaDatabaseRecord' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\ReturnUrl::class => [],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\ReturnUrl::class,
                        ]
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\ParentPageTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\ParentPageTca::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUniqueUidNewRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDateTimeFields::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUniqueUidNewRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDateTimeFields::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows::class => [
                        'depends' => [
                            // Language stuff depends on user ts, but it *may* also depend on new row defaults
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows::class,
                            // As the ctrl.type can hold a nested key we need to resolve all relations
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineIsOnSymmetricSide::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineIsOnSymmetricSide::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle::class,
                        ],
                    ],
                ],
                'tcaSelectTreeAjaxFieldData' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class => [],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\ParentPageTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\ParentPageTca::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUniqueUidNewRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUniqueUidNewRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows::class => [
                        'depends' => [
                            // Language stuff depends on user ts, but it *may* also depend on new row defaults
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class,
                        ],
                    ],
                ],
                'flexFormSegment' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ]
                    ]
                ],
                'tcaInputPlaceholderRecord' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class => [],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
                        ],
                    ],
                ],
            ],
        ],
    ],
    'EXT' => [ // Options related to the Extension Management
        'allowGlobalInstall' => false,
        'allowLocalInstall' => true,
        'allowSystemInstall' => false,
        'excludeForPackaging' => '(?:\\..*(?!htaccess)|.*~|.*\\.swp|.*\\.bak|\\.sass-cache|node_modules|bower_components)',
        'extConf' => [
            'saltedpasswords' => serialize([
                'BE.' => [
                    'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::class,
                    'forceSalted' => 0,
                    'onlyAuthService' => 0,
                    'updatePasswd' => 1,
                ],
                'FE.' => [
                    'enabled' => 0,
                    'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::class,
                    'forceSalted' => 0,
                    'onlyAuthService' => 0,
                    'updatePasswd' => 1,
                ],
            ]),
        ],
        'runtimeActivatedPackages' => [],
    ],
    'BE' => [
        // Backend Configuration.
        'languageDebug' => false,
        'fileadminDir' => 'fileadmin/',
        'RTE_imageStorageDir' => 'uploads/',
        'lockRootPath' => '',
        'userHomePath' => '',
        'groupHomePath' => '',
        'userUploadDir' => '',
        'warning_email_addr' => '',
        'warning_mode' => '',
        'lockIP' => 4,
        'sessionTimeout' => 3600,
        'IPmaskList' => '',
        'lockBeUserToDBmounts' => true,
        'lockSSL' => false,
        'lockSSLPort' => 0,
        'enabledBeUserIPLock' => true,
        'cookieDomain' => '',
        'cookieName' => 'be_typo_user',
        'loginSecurityLevel' => '',
        'showRefreshLoginPopup' => false,
        'adminOnly' => 0,
        'disable_exec_function' => false,
        'compressionLevel' => 0,
        'installToolPassword' => '',
        'checkStoredRecords' => true,
        'checkStoredRecordsLoose' => true,
        'pageTree' => [
            'preloadLimit' => 50
        ],
        'defaultUserTSconfig' => 'options.enableBookmarks=1
			options.file_list.enableDisplayBigControlPanel=selectable
			options.file_list.enableDisplayThumbnails=selectable
			options.file_list.enableClipBoard=selectable
			options.pageTree {
				doktypesToShowInNewPageDragArea = 1,6,4,7,3,254,255,199
			}

			options.contextMenu {
				table {
					pages {
						disableItems =
						tree.disableItems =
					}
					sys_file {
						disableItems =
						tree.disableItems =
					}
					sys_filemounts {
						disableItems =
						tree.disableItems =
					}
				}
			}
		',
        // String (exclude). Enter lines of default backend user/group TSconfig.
        'defaultPageTSconfig' => 'mod.web_list.enableDisplayBigControlPanel=selectable
			mod.web_list.enableClipBoard=selectable
			mod.web_list.enableLocalizationView=selectable
			mod.web_list.tableDisplayOrder {
				be_users.after = be_groups
				sys_filemounts.after = be_users
				sys_file_storage.after = sys_filemounts
				sys_language.after = sys_file_storage
				pages_language_overlay.before = pages
				fe_users.after = fe_groups
				fe_users.before = pages
				sys_template.after = pages
				backend_layout.after = pages
				sys_domain.after = sys_template
				tt_content.after = pages,backend_layout,sys_template
				sys_category.after = tt_content
			}
			mod.web_list.searchLevel.items {
				-1 = EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.searchLevel.infinite
				0 = EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.searchLevel.0
				1 = EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.searchLevel.1
				2 = EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.searchLevel.2
				3 = EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.searchLevel.3
				4 = EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.searchLevel.4
			}
			mod.wizards.newRecord.pages.show.pageInside=1
			mod.wizards.newRecord.pages.show.pageAfter=1
			mod.wizards.newRecord.pages.show.pageSelectPosition=1
			mod.web_view.previewFrameWidths {
				1280.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:computer
				1024.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:tablet
				960.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:mobile
				800.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:computer
				768.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:tablet
				600.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:tablet
				640.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:mobile
				480.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:mobile
				400.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:mobile
				360.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:mobile
				300.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:mobile
			}
		',
        // String (exclude).Enter lines of default Page TSconfig.
        'defaultPermissions' => [],
        'defaultUC' => [],
        // The control of file extensions goes in two categories. Webspace and Ftpspace. Webspace is folders accessible from a webbrowser (below TYPO3_DOCUMENT_ROOT) and ftpspace is everything else.
        // The control is done like this: If an extension matches 'allow' then the check returns TRUE. If not and an extension matches 'deny' then the check return FALSE. If no match at all, returns TRUE.
        // You list extensions comma-separated. If the value is a '*' every extension is matched
        // If no file extension, TRUE is returned if 'allow' is '*', FALSE if 'deny' is '*' and TRUE if none of these matches
        // This configuration below accepts everything in ftpspace and everything in webspace except php3,php4,php5 or php files
        'fileExtensions' => [
            'webspace' => ['allow' => '', 'deny' => PHP_EXTENSIONS_DEFAULT]
        ],
        'customPermOptions' => [], // Array with sets of custom permission options. Syntax is; 'key' => array('header' => 'header string, language split', 'items' => array('key' => array('label, language split','icon reference', 'Description text, language split'))). Keys cannot contain ":|," characters.
        'fileDenyPattern' => FILE_DENY_PATTERN_DEFAULT,
        'interfaces' => 'backend',
        'explicitADmode' => 'explicitDeny',
        'flexformForceCDATA' => 0,
        'explicitConfirmationOfTranslation' => false,
        'versionNumberInFilename' => false,
        'debug' => false,
        'AJAX' => [], // array of key-value pairs for a unified use of AJAX calls in the TYPO3 backend. Keys are the unique ajaxIDs where the value will be resolved to call a method in an object. See the AjaxRequestHandler class for more information.
        'toolbarItems' => [], // Array: Registered toolbar items classes
        'HTTP' => [
            'Response' => [
                'Headers' => ['clickJackingProtection' => 'X-Frame-Options: SAMEORIGIN']
            ]
        ],
    ],
    'FE' => [ // Configuration for the TypoScript frontend (FE). Nothing here relates to the administration backend!
        'addAllowedPaths' => '',
        'debug' => false,
        'noPHPscriptInclude' => false,
        'compressionLevel' => 0,
        'pageNotFound_handling' => '',
        'pageNotFound_handling_statheader' => 'HTTP/1.0 404 Not Found',
        'pageNotFound_handling_accessdeniedheader' => 'HTTP/1.0 403 Access denied',
        'pageNotFoundOnCHashError' => true,
        'pageUnavailable_handling' => '',
        'pageUnavailable_handling_statheader' => 'HTTP/1.0 503 Service Temporarily Unavailable',
        'pageUnavailable_force' => false,
        'addRootLineFields' => '',
        'checkFeUserPid' => true,
        'lockIP' => 2,
        'loginSecurityLevel' => '',
        'lifetime' => 0,
        'sessionDataLifetime' => 86400,
        'maxSessionDataSize' => 10000,
        'permalogin' => 0,
        'cookieDomain' => '',
        'cookieName' => 'fe_typo_user',
        'defaultUserTSconfig' => '',
        'defaultTypoScript_constants' => '',
        'defaultTypoScript_constants.' => [], // Lines of TS to include after a static template with the uid = the index in the array (Constants)
        'defaultTypoScript_setup' => '',
        'defaultTypoScript_setup.' => [], // Lines of TS to include after a static template with the uid = the index in the array (Setup)
        'additionalAbsRefPrefixDirectories' => '',
        'IPmaskMountGroups' => [ // This allows you to specify an array of IPmaskLists/fe_group-uids. If the REMOTE_ADDR of the user matches an IPmaskList,
            // array('IPmaskList_1','fe_group uid'), array('IPmaskList_2','fe_group uid')
        ],
        'get_url_id_token' => '#get_URL_ID_TOK#',
        'content_doktypes' => '1,2,5,7',
        'enable_mount_pids' => true,
        'hidePagesIfNotTranslatedByDefault' => false,
        'eID_include' => [], // Array of key/value pairs where key is "tx_[ext]_[optional suffix]" and value is relative filename of class to include. Key is used as "?eID=" for \TYPO3\CMS\Frontend\Http\RequestHandlerRequestHandler to include the code file which renders the page from that point. (Useful for functionality that requires a low initialization footprint, eg. frontend ajax applications)
        'disableNoCacheParameter' => false,
        'cacheHash' => [], // Array: Processed values of the cHash* parameters, handled by core bootstrap internally
        'cHashExcludedParameters' => 'L, pk_campaign, pk_kwd, utm_source, utm_medium, utm_campaign, utm_term, utm_content, gclid',
        'cHashOnlyForParameters' => '',
        'cHashRequiredParameters' => '',
        'cHashExcludedParametersIfEmpty' => '',
        'workspacePreviewLogoutTemplate' => '',
        'versionNumberInFilename' => 'querystring',
        'contentRenderingTemplates' => [], // Array to define the TypoScript parts that define the main content rendering. Extensions like "css_styled_content" provide content rendering templates. Other extensions like "felogin" or "indexed search" extend these templates and their TypoScript parts are added directly after the content templates. See EXT:css_styled_content/ext_localconf.php and EXT:frontend/Classes/TypoScript/TemplateService.php
        'ContentObjects' => [], // Array to register ContentObject (cObjects) like TEXT or HMENU within ext_localconf.php, see EXT:frontend/ext_localconf.php
        'typolinkBuilder' => [  // Matches the LinkService implementations for generating URL, link text via typolink
            'page' => \TYPO3\CMS\Frontend\Typolink\PageLinkBuilder::class,
            'file' => \TYPO3\CMS\Frontend\Typolink\FileOrFolderLinkBuilder::class,
            'folder' => \TYPO3\CMS\Frontend\Typolink\FileOrFolderLinkBuilder::class,
            'url' => \TYPO3\CMS\Frontend\Typolink\ExternalUrlLinkBuilder::class,
            'email' => \TYPO3\CMS\Frontend\Typolink\EmailLinkBuilder::class,
            'record' => \TYPO3\CMS\Frontend\Typolink\DatabaseRecordLinkBuilder::class,
            'unknown' => \TYPO3\CMS\Frontend\Typolink\LegacyLinkBuilder::class,
        ],
    ],
    'MAIL' => [ // Mail configurations to tune how \TYPO3\CMS\Core\Mail\ classes will send their mails.
        'transport' => 'mail',
        'transport_smtp_server' => 'localhost:25',
        'transport_smtp_encrypt' => '',
        'transport_smtp_username' => '',
        'transport_smtp_password' => '',
        'transport_sendmail_command' => '',
        'transport_mbox_file' => '',
        'defaultMailFromAddress' => '',
        'defaultMailFromName' => ''
    ],
    'HTTP' => [ // HTTP configuration to tune how TYPO3 behaves on HTTP requests made by TYPO3. Have a look at http://docs.guzzlephp.org/en/latest/request-options.html for some background information on those settings.
        'allow_redirects' => [ // Mixed, set to false if you want to allow redirects, or use it as an array to add more values,
            'max' => 5, // Integer: Maximum number of tries before an exception is thrown.
            'strict' => false // Boolean: Whether to keep request method on redirects via status 301 and 302 (TRUE, needed for compatibility with <a href="http://www.faqs.org/rfcs/rfc2616">RFC 2616</a>) or switch to GET (FALSE, needed for compatibility with most browsers).
        ],
        'cert' => null,
        'connect_timeout' => 10,
        'proxy' => null,
        'ssl_key' => null,
        'timeout' => 0,
        'verify' => true,
        'version' => '1.1',
        'headers' => [ // Additional HTTP headers sent by every request TYPO3 executes.
            'User-Agent' => 'TYPO3' // String: Default user agent. This sets the constant <em>TYPO3_user_agent</em>.
        ]
    ],
    'LOG' => [
        'writerConfiguration' => [
            \TYPO3\CMS\Core\Log\LogLevel::WARNING => [
                \TYPO3\CMS\Core\Log\Writer\FileWriter::class => []
            ]
        ],
        'TYPO3' => [
            'CMS' => [
                'Core' => [
                    'Resource' => [
                        'ResourceStorage' => [
                            'writerConfiguration' => [
                                \TYPO3\CMS\Core\Log\LogLevel::ERROR => [
                                    \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [],
                                    \TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class => []
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    'USER' => [],
    'SC_OPTIONS' => [ // Here you can more or less freely define additional configuration for scripts in TYPO3. Of course the features supported depends on the script. See documentation "Inside TYPO3" for examples. Keys in the array are the relative path of a script (for output scripts it should be the "script ID" as found in a comment in the HTML header ) and values can then be anything that scripts wants to define for itself. The key "GLOBAL" is reserved.
        'GLOBAL' => [
            'softRefParser' => [
                'substitute' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'notify' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'images' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'typolink' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'typolink_tag' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'ext_fileref' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'email' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'url' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
            ],
            // cliKeys have been deprecated and will be removed in TYPO3 v9
            'cliKeys' => []
        ],
    ],
    'SVCONF' => []
];
