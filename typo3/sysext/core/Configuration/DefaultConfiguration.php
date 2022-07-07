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
 * The description of the various options is stored in the DefaultConfigurationDescription.yaml file
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
        'processor_effects' => false,
        'processor_allowUpscaling' => true,
        'processor_allowFrameSelection' => true,
        'processor_allowTemporaryMasksAsPng' => false,
        'processor_stripColorProfileByDefault' => true,
        'processor_stripColorProfileCommand' => '+profile \'*\'',
        'processor_colorspace' => 'RGB',
        'processor_interlace' => 'None',
        'jpg_quality' => 85,
    ],
    'SYS' => [
        // System related concerning both frontend and backend.
        'lang' => [
            'format' => [
                'priority' => 'xlf',
            ],
            'parser' => [
                'xlf' => \TYPO3\CMS\Core\Localization\Parser\XliffParser::class,
            ],
        ],
        'session' => [
            'BE' => [
                'backend' => \TYPO3\CMS\Core\Session\Backend\DatabaseSessionBackend::class,
                'options' => [
                    'table' => 'be_sessions',
                ],
            ],
            'FE' => [
                'backend' => \TYPO3\CMS\Core\Session\Backend\DatabaseSessionBackend::class,
                'options' => [
                    'table' => 'fe_sessions',
                    'has_anonymous' => true,
                ],
            ],
        ],
        'fileCreateMask' => '0664',
        'folderCreateMask' => '2775',
        'features' => [
            'redirects.hitCount' => false,
            'runtimeDbQuotingOfTcaConfiguration' => true,
            'unifiedPageTranslationHandling' => false,
            'security.frontend.htmlSanitizeParseFuncDefault' => true,
            'security.backend.htmlSanitizeRte' => false,
            'security.backend.enforceReferrer' => true,
            'yamlImportsFollowDeclarationOrder' => false,
            'subrequestPageErrors' => false,
        ],
        'createGroup' => '',
        'sitename' => 'TYPO3',
        'encryptionKey' => '',
        'cookieDomain' => '',
        'trustedHostsPattern' => 'SERVER_NAME',
        'devIPmask' => '127.0.0.1,::1',
        'ddmmyy' => 'd-m-y',
        'hhmm' => 'H:i',
        'USdateFormat' => false,
        'loginCopyrightWarrantyProvider' => '',
        'loginCopyrightWarrantyURL' => '',
        'textfile_ext' => 'txt,ts,typoscript,html,htm,css,tmpl,js,sql,xml,csv,xlf,yaml,yml',
        'mediafile_ext' => 'gif,jpg,jpeg,bmp,png,pdf,svg,ai,mp3,wav,mp4,ogg,flac,opus,webm,youtube,vimeo',
        'binPath' => '',
        'binSetup' => '',
        'setMemoryLimit' => 0,
        'phpTimeZone' => '',
        'UTF8filesystem' => false,
        'systemLocale' => '',
        'systemMaintainers' => null,    // @todo: This will be set up as an empty array once the installer can define a system maintainers
        'reverseProxyIP' => '',
        'reverseProxyHeaderMultiValue' => 'none',
        'reverseProxyPrefix' => '',
        'reverseProxySSL' => '',
        'reverseProxyPrefixSSL' => '',
        'availablePasswordHashAlgorithms' => [
            \TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash::class,
            \TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash::class,
            \TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash::class,
            \TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash::class,
            \TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash::class,
            \TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash::class,
            \TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash::class,
        ],
        'routing' => [
            'enhancers' => [
                'Simple' => \TYPO3\CMS\Core\Routing\Enhancer\SimpleEnhancer::class,
                'Plugin' => \TYPO3\CMS\Core\Routing\Enhancer\PluginEnhancer::class,
                'PageType' => \TYPO3\CMS\Core\Routing\Enhancer\PageTypeDecorator::class,
                'Extbase' => \TYPO3\CMS\Extbase\Routing\ExtbasePluginEnhancer::class,
            ],
            'aspects' => [
                'LocaleModifier' => \TYPO3\CMS\Core\Routing\Aspect\LocaleModifier::class,
                'PersistedAliasMapper' => \TYPO3\CMS\Core\Routing\Aspect\PersistedAliasMapper::class,
                'PersistedPatternMapper' => \TYPO3\CMS\Core\Routing\Aspect\PersistedPatternMapper::class,
                'StaticRangeMapper' => \TYPO3\CMS\Core\Routing\Aspect\StaticRangeMapper::class,
                'StaticValueMapper' => \TYPO3\CMS\Core\Routing\Aspect\StaticValueMapper::class,
            ],
        ],
        'locking' => [
            'strategies' => [
                \TYPO3\CMS\Core\Locking\FileLockStrategy::class => [
                    // if not set: use default priority of FileLockStrategy
                    //'priority' => 75,

                    // if not set: use default path of FileLockStrategy
                    // If you change this, directory must exist!
                    // 'lockFileDir' => 'typo3temp/var'
                ],
                \TYPO3\CMS\Core\Locking\SemaphoreLockStrategy::class => [
                    // if not set: use default priority of SemaphoreLockStrategy
                    // 'priority' => 50

                    // empty: use default path of SemaphoreLockStrategy
                    // If you change this, directory must exist!
                    // 'lockFileDir' => 'typo3temp/var'
                ],
                \TYPO3\CMS\Core\Locking\SimpleLockStrategy::class => [
                    // if not set: use default priority of SimpleLockStrategy
                    //'priority' => 25,

                    // empty: use default path of SimpleLockStrategy
                    // If you change this, directory must exist!
                    // 'lockFileDir' => 'typo3temp/var'
                ],
            ],
        ],
        'caching' => [
            'cacheConfigurations' => [
                // The cache_core cache is is for core php code only and must
                // not be abused by third party extensions.
                'core' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system'],
                ],
                'hash' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [],
                    'groups' => ['pages'],
                ],
                'pages' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'compression' => true,
                    ],
                    'groups' => ['pages'],
                ],
                'pagesection' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'compression' => true,
                        'defaultLifetime' => 2592000, // 30 days; set this to a lower value in case your cache gets too big
                    ],
                    'groups' => ['pages'],
                ],
                'runtime' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class,
                    'options' => [],
                    'groups' => [],
                ],
                'rootline' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'defaultLifetime' => 2592000, // 30 days; set this to a lower value in case your cache gets too big
                    ],
                    'groups' => ['pages'],
                ],
                'imagesizes' => [
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
                    'groups' => ['system'],
                ],
                'l10n' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system'],
                ],
                'fluid_template' => [
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                    'frontend' => \TYPO3\CMS\Fluid\Core\Cache\FluidTemplateCache::class,
                    'groups' => ['system'],
                ],
                'extbase' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system'],
                ],
                'ratelimiter' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                    'groups' => ['system'],
                ],
            ],
        ],
        'htmlSanitizer' => [
            'default' => \TYPO3\CMS\Core\Html\DefaultSanitizerBuilder::class,
            'i18n' => \TYPO3\CMS\Core\Html\I18nSanitizerBuilder::class,
        ],
        'defaultCategorizedTables' => '',
        'displayErrors' => -1,
        'productionExceptionHandler' => \TYPO3\CMS\Core\Error\ProductionExceptionHandler::class,
        'debugExceptionHandler' => \TYPO3\CMS\Core\Error\DebugExceptionHandler::class,
        'errorHandler' => \TYPO3\CMS\Core\Error\ErrorHandler::class,
        'errorHandlerErrors' => E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR),
        'exceptionalErrors' => E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR | E_DEPRECATED | E_USER_DEPRECATED | E_WARNING | E_USER_ERROR | E_USER_NOTICE | E_USER_WARNING),
        'belogErrorReporting' => E_ALL & ~(E_STRICT | E_NOTICE),
        'locallangXMLOverride' => [], // For extension/overriding of the arrays in 'locallang' files in frontend  and backend. See 'Inside TYPO3' for more information.
        'generateApacheHtaccess' => 1,
        'ipAnonymization' => 1,
        'Objects' => [],
        'fal' => [
            'registeredDrivers' => [
                'Local' => [
                    'class' => \TYPO3\CMS\Core\Resource\Driver\LocalDriver::class,
                    'shortName' => 'Local',
                    'flexFormDS' => 'FILE:EXT:core/Configuration/Resource/Driver/LocalDriverFlexForm.xml',
                    'label' => 'Local filesystem',
                ],
            ],
            'defaultFilterCallbacks' => [
                [
                    \TYPO3\CMS\Core\Resource\Filter\FileNameFilter::class,
                    'filterHiddenFilesAndFolders',
                ],
            ],
            'processors' => [
                'SvgImageProcessor' => [
                    'className' => \TYPO3\CMS\Core\Resource\Processing\SvgImageProcessor::class,
                    'before' => [
                        'LocalImageProcessor',
                    ],
                ],
                'DeferredBackendImageProcessor' => [
                    'className' => \TYPO3\CMS\Backend\Resource\Processing\DeferredBackendImageProcessor::class,
                    'before' => [
                        'LocalImageProcessor',
                        'OnlineMediaPreviewProcessor',
                    ],
                    'after' => [
                        'SvgImageProcessor',
                    ],
                ],
                'OnlineMediaPreviewProcessor' => [
                    'className' => \TYPO3\CMS\Core\Resource\OnlineMedia\Processing\PreviewProcessing::class,
                    'after' => [
                        'SvgImageProcessor',
                    ],
                    'before' => [
                        'LocalImageProcessor',
                    ],
                ],
                'LocalImageProcessor' => [
                    'className' => \TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor::class,
                ],
            ],
            'processingTaskTypes' => [
                'Image.Preview' => \TYPO3\CMS\Core\Resource\Processing\ImagePreviewTask::class,
                'Image.CropScaleMask' => \TYPO3\CMS\Core\Resource\Processing\ImageCropScaleMaskTask::class,
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
                'protectedSection' => 'overlay-includes-subpages',
            ],
            'overlayPriorities' => [
                'hidden',
                'starttime',
                'endtime',
                'futureendtime',
                'protectedSection',
                'fe_group',
            ],
        ],
        'FileInfo' => [
            // Static mapping for file extensions to mime types.
            // In special cases the mime type is not detected correctly.
            // Use this array only if the automatic detection does not work correct!
            'fileExtensionToMimeType' => [
                'svg' => 'image/svg+xml',
                'youtube' => 'video/youtube',
                'vimeo' => 'video/vimeo',
            ],
        ],
        'fluid' => [
            'interceptors' => [],
            'preProcessors' => [
                \TYPO3Fluid\Fluid\Core\Parser\TemplateProcessor\EscapingModifierTemplateProcessor::class,
                \TYPO3Fluid\Fluid\Core\Parser\TemplateProcessor\PassthroughSourceModifierTemplateProcessor::class,
                \TYPO3Fluid\Fluid\Core\Parser\TemplateProcessor\NamespaceDetectionTemplateProcessor::class,
            ],
            'expressionNodeTypes' => [
                \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\CastingExpressionNode::class,
                \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\MathExpressionNode::class,
                \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\TernaryExpressionNode::class,
            ],
            'namespaces' => [
                'core' => [
                    'TYPO3\\CMS\\Core\\ViewHelpers',
                ],
                'f' => [
                    'TYPO3Fluid\\Fluid\\ViewHelpers',
                    'TYPO3\\CMS\\Fluid\\ViewHelpers',
                ],
            ],
        ],
        'defaultScheme' => \TYPO3\CMS\Core\LinkHandling\LinkHandlingInterface::DEFAULT_SCHEME,
        'linkHandler' => [ // Array: Available link types, class which implement the LinkHandling interface
            'page'   => \TYPO3\CMS\Core\LinkHandling\PageLinkHandler::class,
            'file'   => \TYPO3\CMS\Core\LinkHandling\FileLinkHandler::class,
            'folder' => \TYPO3\CMS\Core\LinkHandling\FolderLinkHandler::class,
            'url'    => \TYPO3\CMS\Core\LinkHandling\UrlLinkHandler::class,
            'email'  => \TYPO3\CMS\Core\LinkHandling\EmailLinkHandler::class,
            'record' => \TYPO3\CMS\Core\LinkHandling\RecordLinkHandler::class,
            'telephone' => \TYPO3\CMS\Core\LinkHandling\TelephoneLinkHandler::class,
        ],
        'livesearch' => [],  // Array: keywords used for commands to search for specific tables
        'formEngine' => [
            'nodeRegistry' => [], // Array: Registry to add or overwrite FormEngine nodes. Main key is a timestamp of the date when an entry is added, sub keys type, priority and class are required. Class must implement TYPO3\CMS\Backend\Form\NodeInterface.
            'nodeResolver' => [], // Array: Additional node resolver. Main key is a timestamp of the date when an entry is added, sub keys type, priority and class are required. Class must implement TYPO3\CMS\Backend\Form\NodeResolverInterface.
            'formDataGroup' => [ // Array: Registry of form data providers for form data groups
                'tcaDatabaseRecord' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\ReturnUrl::class => [],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\ReturnUrl::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseDefaultLanguagePageRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseDefaultLanguagePageRow::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class,
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
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class,
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
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDateTimeFields::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseDefaultLanguagePageRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows::class => [
                        'depends' => [
                            // Language stuff depends on user ts, but it *may* also depend on new row defaults
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultAsReadonly::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultAsReadonly::class,
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
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaLanguage::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaLanguage::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldDescriptions::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldDescriptions::class,
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
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSlug::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCategory::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCategory::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseDefaultLanguagePageRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseDefaultLanguagePageRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class,
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
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseDefaultLanguagePageRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows::class => [
                        'depends' => [
                            // Language stuff depends on user ts, but it *may* also depend on new row defaults
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultAsReadonly::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultAsReadonly::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCategory::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
                        ],
                    ],
                ],
                'flexFormSegment' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldDescriptions::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldDescriptions::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCategory::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCategory::class,
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
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class,
                        ],
                    ],
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
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
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCategory::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCategory::class,
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
                'siteConfiguration' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class => [],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\SiteDatabaseEditRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteDatabaseEditRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseDefaultLanguagePageRow::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class,
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
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class,
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
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDateTimeFields::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class,
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
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldDescriptions::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldDescriptions::class,
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
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\SiteTcaSelectItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
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
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteTcaSelectItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\SiteTcaInline::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSiteLanguage::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteTcaInline::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSiteLanguage::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle::class,
                        ],
                    ],
                ],
            ],
        ],
        'yamlLoader' => [
            'placeholderProcessors' => [
                \TYPO3\CMS\Core\Configuration\Processor\Placeholder\EnvVariableProcessor::class => [],
                \TYPO3\CMS\Core\Configuration\Processor\Placeholder\ValueFromReferenceArrayProcessor::class => [
                    'after' => [
                        \TYPO3\CMS\Core\Configuration\Processor\Placeholder\EnvVariableProcessor::class,
                    ],
                ],
            ],
        ],
    ],
    'EXT' => [ // Options related to the Extension Management
        'allowGlobalInstall' => false,
        'allowLocalInstall' => true,
        'excludeForPackaging' => '(?:\\.(?!htaccess$).*|.*~|.*\\.swp|.*\\.bak|node_modules|bower_components)',
    ],
    'BE' => [
        // Backend Configuration.
        'fluidPageModule' => true,
        'languageDebug' => false,
        'fileadminDir' => 'fileadmin/',
        'lockRootPath' => '',
        'userHomePath' => '',
        'groupHomePath' => '',
        'userUploadDir' => '',
        'warning_email_addr' => '',
        'warning_mode' => 0,
        'passwordReset' => true,
        'passwordResetForAdmins' => true,
        'requireMfa' => 0,
        'recommendedMfaProvider' => 'totp',
        'loginRateLimit' => 5,
        'loginRateLimitInterval' => '15 minutes',
        'loginRateLimitIpExcludeList' => '',
        'lockIP' => 0,
        'lockIPv6' => 0,
        'sessionTimeout' => 28800,  // a backend user logged in for 8 hours
        'IPmaskList' => '',
        'lockSSL' => false,
        'lockSSLPort' => 0,
        'cookieDomain' => '',
        'cookieName' => 'be_typo_user',
        'cookieSameSite' => 'strict',
        'showRefreshLoginPopup' => false,
        'adminOnly' => 0,
        'disable_exec_function' => false,
        'compressionLevel' => 0,
        'installToolPassword' => '',
        'checkStoredRecords' => true,
        'checkStoredRecordsLoose' => true,
        'defaultUserTSconfig' => 'options.enableBookmarks=1
            options.file_list.enableDisplayThumbnails=selectable
            options.file_list.enableClipBoard=selectable
            options.file_list.thumbnail {
                width = 64
                height = 64
            }
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
        'defaultPageTSconfig' => '
            mod.web_list.enableClipBoard=selectable
            mod.web_list.tableDisplayOrder {
                be_users.after = be_groups
                sys_filemounts.after = be_users
                sys_file_storage.after = sys_filemounts
                sys_language.after = sys_file_storage
                fe_users.after = fe_groups
                fe_users.before = pages
                sys_template.after = pages
                backend_layout.after = pages
                tt_content.after = pages,backend_layout,sys_template
                sys_category.after = tt_content
            }
            mod.web_list.searchLevel.items {
                -1 = EXT:core/Resources/Private/Language/locallang_core.xlf:labels.searchLevel.infinite
                0 = EXT:core/Resources/Private/Language/locallang_core.xlf:labels.searchLevel.0
                1 = EXT:core/Resources/Private/Language/locallang_core.xlf:labels.searchLevel.1
                2 = EXT:core/Resources/Private/Language/locallang_core.xlf:labels.searchLevel.2
                3 = EXT:core/Resources/Private/Language/locallang_core.xlf:labels.searchLevel.3
                4 = EXT:core/Resources/Private/Language/locallang_core.xlf:labels.searchLevel.4
            }
            mod.wizards.newRecord.pages.show.pageInside=1
            mod.wizards.newRecord.pages.show.pageAfter=1
            mod.wizards.newRecord.pages.show.pageSelectPosition=1
            mod.web_view.previewFrameWidths {

                1920.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:computer
                1920.type = desktop
                1920.width = 1920
                1920.height = 1080

                1366.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:computer
                1366.type = desktop
                1366.width = 1366
                1366.height = 768

                1280.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:computer
                1280.type = desktop
                1280.width = 1280
                1280.height = 1024

                1024.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:computer
                1024.type = desktop
                1024.width = 1024
                1024.height = 768

                nexus7.label = Nexus 7
                nexus7.type = tablet
                nexus7.width = 600
                nexus7.height = 960

                nexus6p.label = Nexus 6P
                nexus6p.type = mobile
                nexus6p.width = 411
                nexus6p.height = 731

                ipadpro.label = iPad Pro
                ipadpro.type = tablet
                ipadpro.width = 1024
                ipadpro.height = 1366

                ipadair.label = iPad Air
                ipadair.type = tablet
                ipadair.width = 768
                ipadair.height = 1024

                iphone7plus.label = iPhone 7 Plus
                iphone7plus.type = mobile
                iphone7plus.width = 414
                iphone7plus.height = 736

                iphone6.label = iPhone 6
                iphone6.type = mobile
                iphone6.width = 375
                iphone6.height = 667

                iphone5.label = iPhone 5
                iphone5.type = mobile
                iphone5.width = 320
                iphone5.height = 568

                iphone4.label = iPhone 4
                iphone4.type = mobile
                iphone4.width = 320
                iphone4.height = 480

            }
            mod.web_info.fieldDefinitions {
                0 {
                    label = LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:pages_0
                    fields = title,uid,slug,starttime,endtime,fe_group,target,url,shortcut,shortcut_mode
                }
                1 {
                    label = LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:pages_1
                    fields = title,uid,###ALL_TABLES###
                }
                2 {
                    label = LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:pages_2
                    fields = title,uid,lastUpdated,newUntil,cache_timeout,php_tree_stop,TSconfig,is_siteroot,fe_login_mode
                }
                3 {
                    label = LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:pages_layouts
                    fields = title,uid,actual_backend_layout,backend_layout,backend_layout_next_level,layout
                }
            }
        ',
        // String (exclude).Enter lines of default Page TSconfig.
        'defaultPermissions' => [],
        'defaultUC' => [],
        'customPermOptions' => [], // Array with sets of custom permission options. Syntax is; 'key' => array('header' => 'header string, language split', 'items' => array('key' => array('label, language split','icon reference', 'Description text, language split'))). Keys cannot contain ":|," characters.
        'interfaces' => 'backend',
        'explicitADmode' => 'explicitAllow',
        'flexformForceCDATA' => 0,
        'versionNumberInFilename' => false,
        'debug' => false,
        'toolbarItems' => [], // Array: Registered toolbar items classes
        'HTTP' => [
            'Response' => [
                'Headers' => [
                    'clickJackingProtection' => 'X-Frame-Options: SAMEORIGIN',
                    // 'csp-report' => "Content-Security-Policy-Report-Only: default-src 'self'; style-src-attr 'unsafe-inline'; img-src 'self' data:",
                    // @todo later™: muuri.js is creating workers from `blob:` (?!?), <style> tags declare inline styles (?!?)
                    // 'csp-report' => "Content-Security-Policy-Report-Only: default-src 'self'; style-src-attr 'unsafe-inline'; style-src-elem 'self' 'unsafe-inline'; img-src 'self' data:; worker-src 'self' blob:;",
                ],
            ],
        ],
        'passwordHashing' => [
            'className' => \TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash::class,
            'options' => [],
        ],
    ],
    'FE' => [ // Configuration for the TypoScript frontend (FE). Nothing here relates to the administration backend!
        'addAllowedPaths' => '',
        'debug' => false,
        'compressionLevel' => 0,
        'pageNotFoundOnCHashError' => true,
        'pageUnavailable_force' => false,
        'addRootLineFields' => '',
        'checkFeUserPid' => true,
        'loginRateLimit' => 10,
        'loginRateLimitInterval' => '15 minutes',
        'loginRateLimitIpExcludeList' => '',
        'lockIP' => 0,
        'lockIPv6' => 0,
        'lifetime' => 0,
        'sessionTimeout' => 6000,
        'sessionDataLifetime' => 86400,
        'permalogin' => 0,
        'cookieDomain' => '',
        'cookieName' => 'fe_typo_user',
        'cookieSameSite' => 'lax',
        'defaultUserTSconfig' => '',
        'defaultTypoScript_constants' => '',
        'defaultTypoScript_constants.' => [], // Lines of TS to include after a static template with the uid = the index in the array (Constants)
        'defaultTypoScript_setup' => '',
        'defaultTypoScript_setup.' => [], // Lines of TS to include after a static template with the uid = the index in the array (Setup)
        'additionalAbsRefPrefixDirectories' => '',
        'enable_mount_pids' => true,
        'hidePagesIfNotTranslatedByDefault' => false,
        'eID_include' => [], // Array of key/value pairs where key is "tx_[ext]_[optional suffix]" and value is relative filename of class to include. Key is used as "?eID=" for \TYPO3\CMS\Frontend\Http\RequestHandlerRequestHandler to include the code file which renders the page from that point. (Useful for functionality that requires a low initialization footprint, eg. frontend ajax applications)
        'disableNoCacheParameter' => false,
        'cacheHash' => [
            'cachedParametersWhiteList' => [],
            'excludedParameters' => ['L', 'pk_campaign', 'pk_kwd', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'msclkid'],
            'requireCacheHashPresenceParameters' => [],
            'excludeAllEmptyParameters' => false,
            'excludedParametersIfEmpty' => [],
        ],
        'additionalCanonicalizedUrlParameters' => [],
        'workspacePreviewLogoutTemplate' => '',
        'versionNumberInFilename' => 'querystring',
        'contentRenderingTemplates' => [], // Array to define the TypoScript parts that define the main content rendering. Extensions like "fluid_styled_content" provide content rendering templates. Other extensions like "felogin" or "indexed search" extend these templates and their TypoScript parts are added directly after the content templates. See EXT:fluid_styled_content/ext_localconf.php and EXT:frontend/Classes/TypoScript/TemplateService.php
        'ContentObjects' => [], // Array to register ContentObject (cObjects) like TEXT or HMENU within ext_localconf.php, see EXT:frontend/ext_localconf.php
        'typolinkBuilder' => [  // Matches the LinkService implementations for generating URL, link text via typolink
            'page' => \TYPO3\CMS\Frontend\Typolink\PageLinkBuilder::class,
            'file' => \TYPO3\CMS\Frontend\Typolink\FileOrFolderLinkBuilder::class,
            'folder' => \TYPO3\CMS\Frontend\Typolink\FileOrFolderLinkBuilder::class,
            'url' => \TYPO3\CMS\Frontend\Typolink\ExternalUrlLinkBuilder::class,
            'email' => \TYPO3\CMS\Frontend\Typolink\EmailLinkBuilder::class,
            'record' => \TYPO3\CMS\Frontend\Typolink\DatabaseRecordLinkBuilder::class,
            'telephone' => \TYPO3\CMS\Frontend\Typolink\TelephoneLinkBuilder::class,
            'unknown' => \TYPO3\CMS\Frontend\Typolink\LegacyLinkBuilder::class,
        ],
        'passwordHashing' => [
            'className' => \TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash::class,
            'options' => [],
        ],
        'exposeRedirectInformation' => false,
    ],
    'MAIL' => [ // Mail configurations to tune how \TYPO3\CMS\Core\Mail\ classes will send their mails.
        'transport' => 'sendmail',
        'transport_smtp_server' => 'localhost:25',
        'transport_smtp_encrypt' => false,
        'transport_smtp_username' => '',
        'transport_smtp_password' => '',
        'transport_smtp_domain' => '',
        'transport_sendmail_command' => '',
        'transport_mbox_file' => '',
        'transport_spool_type' => '',
        'transport_spool_filepath' => '',
        'dsn' => '',
        'validators' => [
            \Egulias\EmailValidator\Validation\RFCValidation::class,
        ],
        'defaultMailFromAddress' => '',
        'defaultMailFromName' => '',
        'defaultMailReplyToAddress' => '',
        'defaultMailReplyToName' => '',
        'format' => 'both',
        'layoutRootPaths' => [
            0 => 'EXT:core/Resources/Private/Layouts/',
            10 => 'EXT:backend/Resources/Private/Layouts/',
        ],
        'partialRootPaths' => [
            0 => 'EXT:core/Resources/Private/Partials/',
            10 => 'EXT:backend/Resources/Private/Partials/',
        ],
        'templateRootPaths' => [
            0 => 'EXT:core/Resources/Private/Templates/Email/',
            10 => 'EXT:backend/Resources/Private/Templates/Email/',
        ],
    ],
    'HTTP' => [ // HTTP configuration to tune how TYPO3 behaves on HTTP requests made by TYPO3. Have a look at http://docs.guzzlephp.org/en/latest/request-options.html for some background information on those settings.
        'allow_redirects' => [ // Mixed, set to false if you want to allow redirects, or use it as an array to add more values,
            'max' => 5, // Integer: Maximum number of tries before an exception is thrown.
            'strict' => false, // Boolean: Whether to keep request method on redirects via status 301 and 302 (TRUE, needed for compatibility with <a href="http://www.faqs.org/rfcs/rfc2616">RFC 2616</a>) or switch to GET (FALSE, needed for compatibility with most browsers).
        ],
        'cert' => null,
        'connect_timeout' => 10,
        'proxy' => null,
        'ssl_key' => null,
        'timeout' => 0,
        'verify' => true,
        'version' => '1.1',
        'handler' => [], // Array of callables
        'headers' => [ // Additional HTTP headers sent by every request TYPO3 executes.
            'User-Agent' => 'TYPO3', // String: Default user agent. Defaults to TYPO3.
        ],
    ],
    'LOG' => [
        'writerConfiguration' => [
            \TYPO3\CMS\Core\Log\LogLevel::WARNING => [
                \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [],
            ],
        ],
        'TYPO3' => [
            'CMS' => [
                'Core' => [
                    'Resource' => [
                        'ResourceStorage' => [
                            'writerConfiguration' => [
                                \TYPO3\CMS\Core\Log\LogLevel::ERROR => [
                                    \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [],
                                    \TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class => [],
                                ],
                            ],
                        ],
                    ],
                ],
                'deprecations' => [
                    'writerConfiguration' => [
                        \TYPO3\CMS\Core\Log\LogLevel::NOTICE => [
                            \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                                'logFileInfix' => 'deprecations',
                                'disabled' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'USER' => [],
    // Here you can more or less freely define additional configuration for scripts in TYPO3. Of course the features
    // supported depends on the script. See documentation "Inside TYPO3" for examples. Keys in the array are the relative
    // path of a script (for output scripts it should be the "script ID" as found in a comment in the HTML header ) and
    // values can then be anything that scripts wants to define for itself. The key "GLOBAL" is reserved.
    'SC_OPTIONS' => [
        'ext/install' => [
            'update' => [],
        ],
    ],
    'SVCONF' => [],
    'EXTENSIONS' => [],
];
