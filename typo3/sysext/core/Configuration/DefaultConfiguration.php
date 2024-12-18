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
        'globalDriverMiddlewares' => [
            'typo3/core/custom-platform-driver-middleware' => [
                'target' => \TYPO3\CMS\Core\Database\Middleware\CustomPlatformDriverMiddleware::class,
                'before' => [
                    'typo3/core/custom-pdo-driver-result-middleware',
                ],
            ],
            'typo3/core/custom-pdo-driver-result-middleware' => [
                'target' => \TYPO3\CMS\Core\Database\Middleware\CustomPdoDriverResultMiddleware::class,
                'after' => [
                    'typo3/core/custom-platform-driver-middleware',
                ],
            ],
        ],
    ],
    'GFX' => [ // Configuration of the image processing features in TYPO3. 'IM' and 'GD' are short for ImageMagick and GD library respectively.
        'thumbnails' => true,
        'imagefile_ext' => 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai,svg,webp,avif',
        'processor_enabled' => true,
        'processor_path' => '/usr/bin/',
        'processor' => 'ImageMagick',
        'processor_effects' => false,
        'processor_allowUpscaling' => true,
        'processor_allowFrameSelection' => true,
        'processor_stripColorProfileByDefault' => true,
        'processor_stripColorProfileParameters' => ['+profile', '*'],
        'processor_colorspace' => '',
        'processor_interlace' => 'None',
        'jpg_quality' => 85,
        'webp_quality' => 85,
        'avif_quality' => 85,
    ],
    'SYS' => [
        // System related concerning both frontend and backend.
        'lang' => [
            'requireApprovedLocalizations' => true,
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
            'frontend.cache.autoTagging' => false,
            'redirects.hitCount' => false,
            'security.backend.htmlSanitizeRte' => false,
            'security.backend.enforceReferrer' => true,
            'security.frontend.enforceContentSecurityPolicy' => false,
            'security.frontend.reportContentSecurityPolicy' => false,
            'security.frontend.allowInsecureSiteResolutionByQueryParameters' => false,
            'security.frontend.allowInsecureFrameOptionInShowImageController' => false,
        ],
        'createGroup' => '',
        'sitename' => 'TYPO3',
        'cookieDomain' => '',
        'trustedHostsPattern' => 'SERVER_NAME',
        'devIPmask' => '127.0.0.1,::1',
        'ddmmyy' => 'Y-m-d',
        'hhmm' => 'H:i',
        'loginCopyrightWarrantyProvider' => '',
        'loginCopyrightWarrantyURL' => '',
        'textfile_ext' => 'txt,ts,typoscript,html,htm,css,tmpl,js,sql,xml,csv,xlf,yaml,yml',
        'mediafile_ext' => 'gif,jpg,jpeg,bmp,png,webp,pdf,svg,ai,mp3,wav,mp4,ogg,flac,opus,webm,youtube,vimeo',
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
                // The core cache is is for core php code only and must
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
                    'frontend' => \TYPO3\CMS\Fluid\Core\Cache\FluidTemplateCache::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
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
                'typoscript' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                    'groups' => ['pages'],
                ],
                'database_schema' => [
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
        'displayErrors' => -1,
        'productionExceptionHandler' => \TYPO3\CMS\Core\Error\ProductionExceptionHandler::class,
        'debugExceptionHandler' => \TYPO3\CMS\Core\Error\DebugExceptionHandler::class,
        'errorHandler' => \TYPO3\CMS\Core\Error\ErrorHandler::class,
        // @todo: Remove 2048 (deprecated E_STRICT) in v14, as this value is no longer used by PHP itself
        //        and only kept here here because possible custom PHP extensions may still use it.
        //        See https://wiki.php.net/rfc/deprecations_php_8_4#remove_e_strict_error_level_and_deprecate_e_strict_constant
        'errorHandlerErrors' => E_ALL & ~(2048 /* deprecated E_STRICT */ | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR),
        'exceptionalErrors' => E_ALL & ~(2048 /* deprecated E_STRICT */ | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR | E_DEPRECATED | E_USER_DEPRECATED | E_WARNING | E_USER_ERROR | E_USER_NOTICE | E_USER_WARNING),
        'belogErrorReporting' => E_ALL & ~(2048 /* deprecated E_STRICT */ | E_NOTICE),
        'allowedPhpDisableFunctions' => [],
        'locallangXMLOverride' => [], // For extension/overriding of the arrays in 'locallang' files in frontend  and backend.
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
                        'DeferredBackendImageProcessor',
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
                'avif' => 'image/avif',
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
                \TYPO3Fluid\Fluid\Core\Parser\TemplateProcessor\RemoveCommentsTemplateProcessor::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaJson::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaUuid::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaJson::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaUuid::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFolder::class => [
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
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFolder::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTablePermission::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCategory::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTablePermission::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFiles::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDateTimeFields::class => [],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDateTimeFields::class,
                        ],
                    ],
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFolder::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaJson::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaUuid::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaJson::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFiles::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaJson::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaJson::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFolder::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFolder::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTablePermission::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCategory::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTablePermission::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFiles::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class,
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
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFolder::class => [
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
        'passwordPolicies' => [
            'default' => [
                'validators' => [
                    \TYPO3\CMS\Core\PasswordPolicy\Validator\CorePasswordValidator::class => [
                        'options' => [
                            'minimumLength' => 8,
                            'upperCaseCharacterRequired' => true,
                            'lowerCaseCharacterRequired' => true,
                            'digitCharacterRequired' => true,
                            'specialCharacterRequired' => true,
                        ],
                        'excludeActions' => [],
                    ],
                    \TYPO3\CMS\Core\PasswordPolicy\Validator\NotCurrentPasswordValidator::class => [
                        'options' => [],
                        'excludeActions' => [
                            \TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyAction::NEW_USER_PASSWORD,
                            \TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyAction::UPDATE_USER_PASSWORD_SWITCH_USER_MODE,
                        ],
                    ],
                ],
            ],
        ],
        'messenger' => [
            'routing' => [
                '*' => 'default',
            ],
        ],
    ],
    'EXT' => [ // Options related to the Extension Management
        'excludeForPackaging' => '(?:\\.(?!htaccess$).*|.*~|.*\\.swp|.*\\.bak|node_modules|bower_components)',
    ],
    'BE' => [
        // Backend Configuration.
        'entryPoint' => '/typo3',
        'fileadminDir' => 'fileadmin/',
        'lockRootPath' => '',
        'lockBackendFile' => '',
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
        'contentSecurityPolicyReportingUrl' => '',
        'defaultUserTSconfig' => '', // @deprecated since TYPO3 v13.0, will be removed in TYPO3 v14.0. Add to SilentConfigurationUpgradeService.
        'defaultPageTSconfig' => '', // @deprecated since TYPO3 v13.0, will be removed in TYPO3 v14.0. Add to SilentConfigurationUpgradeService.
        // String (exclude).Enter lines of default page TSconfig.
        'defaultPermissions' => [],
        'defaultUC' => [],
        'customPermOptions' => [], // Array with sets of custom permission options. Syntax is; 'key' => array('header' => 'header string, language split', 'items' => array('key' => array('label, language split','icon reference', 'Description text, language split'))). Keys cannot contain ":|," characters.
        'versionNumberInFilename' => false,
        'debug' => false,
        'HTTP' => [
            'Response' => [
                'Headers' => [
                    'clickJackingProtection' => 'X-Frame-Options: SAMEORIGIN',
                    'strictTransportSecurity' => 'Strict-Transport-Security: max-age=31536000',
                    'avoidMimeTypeSniffing' => 'X-Content-Type-Options: nosniff',
                    'referrerPolicy' => 'Referrer-Policy: strict-origin-when-cross-origin',
                ],
            ],
        ],
        'passwordHashing' => [
            'className' => \TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash::class,
            'options' => [],
        ],
        'passwordPolicy' => 'default',
        'stylesheets' => [
            'backend' => 'EXT:backend/Resources/Public/Css',
        ],
    ],
    'FE' => [ // Configuration for the TypoScript frontend (FE). Nothing here relates to the administration backend!
        'addAllowedPaths' => '',
        'debug' => false,
        'compressionLevel' => 0,
        'pageNotFoundOnCHashError' => true,
        'pageUnavailable_force' => false,
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
        'contentSecurityPolicyReportingUrl' => '',
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
            'excludedParameters' => [
                'L',
                // Matomo
                'mtm_campaign',
                'mtm_keyword',
                'mtm_kwd',
                'mtm_source',
                'mtm_medium',
                'mtm_content',
                'mtm_cid',
                'mtm_group',
                'mtm_placement',
                // Piwik
                'pk_campaign',
                'pk_kwd',
                '_stg_debug',
                // Google
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_term',
                'utm_content',
                'utm_id',
                'utm_source_platform',
                'utm_creative_format',
                'utm_marketing_tactic',
                'gtm_debug',
                '_ga',
                '_gl',
                // Google ads
                'gad',
                'gad_source',
                'gbraid',
                'gclid',
                'dclid',
                'wbraid',
                // Facebook
                'fbclid',
                // Microsoft Bing Ads.
                'msclkid',
                // HubSpot Ad Tracking Parameters
                'hsa_acc',
                'hsa_ad',
                'hsa_cam',
                'hsa_grp',
                'hsa_kw',
                'hsa_mt',
                'hsa_net',
                'hsa_src',
                'hsa_tgt',
                'hsa_ver',
                'hsa_ol',
                'hsa_la',
                // HubSpot Email Tracking Parameters
                '_hsenc',
                '_hsmi',
                // HubSpot CTA Tracking Parameters
                '__hssc',
                '__hstc',
                '__hsfp',
                'hsCtaTracking',
                // HubSpot Form Tracking Parameters
                'submissionGuid',
            ],
            'requireCacheHashPresenceParameters' => [],
            'excludeAllEmptyParameters' => false,
            'excludedParametersIfEmpty' => [],
            'enforceValidation' => false,
        ],
        'additionalCanonicalizedUrlParameters' => [],
        'workspacePreviewLogoutTemplate' => '',
        'versionNumberInFilename' => false,
        'contentRenderingTemplates' => [], // Array to define the TypoScript parts that define the main content rendering. Extensions like "fluid_styled_content" provide content rendering templates. Other extensions like "felogin" or "indexed search" extend these templates and their TypoScript parts are added directly after the content templates.
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
            'className' => \TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash::class,
            'options' => [],
        ],
        'passwordPolicy' => 'default',
        'exposeRedirectInformation' => false,
    ],
    'MAIL' => [ // Mail configurations to tune how \TYPO3\CMS\Core\Mail\ classes will send their mails.
        'transport' => 'sendmail',
        'transport_smtp_server' => 'localhost:25',
        'transport_smtp_encrypt' => false,
        'transport_smtp_username' => '',
        'transport_smtp_password' => '',
        'transport_smtp_domain' => '',
        'transport_smtp_restart_threshold' => 0,
        'transport_smtp_restart_threshold_sleep' => 0,
        'transport_smtp_ping_threshold' => 0,
        'transport_smtp_stream_options' => null,
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
            \Psr\Log\LogLevel::WARNING => [
                \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [],
            ],
        ],
        'TYPO3' => [
            'CMS' => [
                'Core' => [
                    'Resource' => [
                        'ResourceStorage' => [
                            'writerConfiguration' => [
                                \Psr\Log\LogLevel::ERROR => [
                                    \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [],
                                    \TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class => [],
                                ],
                            ],
                        ],
                    ],
                ],
                'deprecations' => [
                    'writerConfiguration' => [
                        \Psr\Log\LogLevel::NOTICE => [
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
    // supported depends on the script.  Keys in the array are the relative
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
